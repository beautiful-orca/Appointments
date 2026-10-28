<?php
require __DIR__ . '/../lib/Backend/HtmlTemplate.php';
require __DIR__ . '/../lib/Backend/MailTemplateVariables.php';

use OCA\Appointments\Backend\HtmlTemplate;
use OCA\Appointments\Backend\MailTemplateVariables;

class TestLocalization {
    public function l($type, $date, $opts = []) {
        $d = $date instanceof \DateTimeInterface ? $date : \DateTimeImmutable::createFromFormat('Ymd\THis', $date, new \DateTimeZone('UTC'));
        return $type === 'date' ? $d->format('Y-m-d') : $d->format('H:i');
    }
    public function t($pattern, $args = []) {
        return sprintf($pattern, ...$args);
    }
}
function assertHas($needle, $haystack) {
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException("Expected " . $needle . " in " . $haystack);
    }
}

$vars = MailTemplateVariables::build(
    'Anna Mustermann', 'Testverein', '15.10.2026, 10:30',
    'anna@example.test', 'kontakt@example.test', '0203 123',
    new DateTimeImmutable('2026-10-15 08:30:00 UTC'),
    new DateTimeImmutable('2026-10-15 09:15:00 UTC'),
    'TEurope/Berlin', new TestLocalization(),
    'Gemeindehaus', '0170 1234567'
);
assert($vars['name'] === 'Anna Mustermann');
// Legacy templates must keep working, without guessing a first name.
assert($vars['firstName'] === 'Anna Mustermann');
assert($vars['date'] === '2026-10-15');
assert($vars['time'] === '10:30');
assert($vars['endTime'] === '11:15');
assert($vars['durationMinutes'] === '45');
assert($vars['location'] === 'Gemeindehaus');
assert($vars['attendeePhone'] === '0170 1234567');

// A missing name has an explicit marker in the calendar metadata.
$anonymous = MailTemplateVariables::build(
    'Guest', 'Testverein', '15.10.2026, 10:30',
    'anon@example.test', 'kontakt@example.test', '0203 123',
    new DateTimeImmutable('2026-10-15 08:30:00 UTC'),
    new DateTimeImmutable('2026-10-15 09:15:00 UTC'),
    'TEurope/Berlin', new TestLocalization(), '', '', true
);
assert($anonymous['name'] === '');
assert($anonymous['firstName'] === '');
assert($anonymous['salutation'] === 'Hello,');
assertHas('<p>Hello,</p>', HtmlTemplate::render('<p>{{salutation}}</p>', $anonymous));

// A person who really typed "Guest" retains their actual name.
$actualGuest = MailTemplateVariables::build(
    'Guest', 'Testverein', '15.10.2026, 10:30',
    'guest@example.test', 'kontakt@example.test', '0203 123',
    new DateTimeImmutable('2026-10-15 08:30:00 UTC'),
    new DateTimeImmutable('2026-10-15 09:15:00 UTC'),
    'TEurope/Berlin', new TestLocalization()
);
assert($actualGuest['name'] === 'Guest');
assert($actualGuest['salutation'] === 'Dear Guest,');

// Shared footer values use the same HTML sanitizer/placeholder engine.
$legalFooter = HtmlTemplate::render(
    '<p>Datenschutz: <a href="https://example.org/privacy">Info</a> – {{organization}}</p><script>alert(1)</script>',
    $vars
);
assertHas('Testverein', $legalFooter);
if (str_contains($legalFooter, '<script')) throw new RuntimeException('Unsafe footer HTML');

$html = HtmlTemplate::render('<p>Hallo {{name}}, Dein Termin am {{date}} um {{time}} in {{location}}.</p>', $vars);
assertHas('Hallo Anna Mustermann', $html);
$oldHtml = HtmlTemplate::render('<p>Hallo {{firstName}},</p>', $vars);
assertHas('Hallo Anna Mustermann', $oldHtml);
assertHas('am 2026-10-15 um 10:30 in Gemeindehaus', $html);

$vars['name'] = '<script>alert(1)</script> {{email}}';
$html = HtmlTemplate::render('<p>{{name}}</p>', $vars);
assertHas('&lt;script&gt;', $html);
assertHas('{{email}}', $html);
if (str_contains($html, '<script>')) throw new RuntimeException('unsafe HTML');
// Original buttons are a distinct rendering action, not user-supplied HTML.
$parts = HtmlTemplate::splitOnConfirmationButtons(
    '<p>Hallo {{name}},</p>{{confirmationButtons}}<p>Bitte bestätigen.</p>'
);
assert($parts === ['<p>Hallo {{name}},</p>', '<p>Bitte bestätigen.</p>']);
assert(HtmlTemplate::splitOnConfirmationButtons('<p>Keine Aktionen</p>') === null);

// Multiple markers may only insert the native action group once.
$parts = HtmlTemplate::splitOnConfirmationButtons(
    'Vorher {{confirmationButtons}} Nachher {{confirmationButtons}}'
);
assert($parts === ['Vorher ', ' Nachher ']);

// A user-controlled placeholder VALUE must not turn into actionable markup.
$vars['name'] = '{{confirmationButtons}}';
$raw = '<p>{{name}}</p>';
assert(HtmlTemplate::splitOnConfirmationButtons($raw) === null);
assertHas('{{confirmationButtons}}', HtmlTemplate::render($raw, $vars));

// A shared header is rendered through the same engine: placeholders are
// replaced, scripts are stripped, and the heading text survives in HTML.
$header = HtmlTemplate::render(
    '<h1 style="color:red">{{organization}}</h1><img src="https://example.org/logo.png" alt="logo"><script>alert(1)</script>',
    $vars
);
assertHas('<h1', $header);
assertHas('Testverein', $header);
assertHas('https://example.org/logo.png', $header);
if (str_contains($header, '<script')) throw new RuntimeException('Unsafe header HTML');

// In email context, local and protocol-relative image sources are stripped —
// they would resolve against the recipient's mail client. Booking pages keep them.
$emailHeader = HtmlTemplate::render('<img src="/local.png"><img src="//rel.png">', [], true);
if (str_contains($emailHeader, 'src=')) throw new RuntimeException('Local image source survived email sanitization');
$pageHtml = HtmlTemplate::render('<img src="/local.png">');
assertHas('<img src="/local.png"', $pageHtml);

echo "mail variable and action button smoke tests passed\n";
