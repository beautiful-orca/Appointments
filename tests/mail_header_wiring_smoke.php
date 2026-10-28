<?php
// Static wiring checks complement the PHP smoke tests and Vue build.
$root = dirname(__DIR__);
$listener = file_get_contents($root . '/lib/Backend/DavListener.php');
$backend = file_get_contents($root . '/lib/Backend/BackendUtils.php');
$state = file_get_contents($root . '/lib/Controller/StateController.php');
$store = file_get_contents($root . '/src/stores/settings.js');
$ui = file_get_contents($root . '/src/components/settings-v2/SectionEmail.vue');
$tests = [
    'header in attendee and reminder paths' => substr_count($listener, 'prependSharedMailHeader($tmpl, $settings, $mailVars)') === 2,
    'header in organizer path' => str_contains($listener, 'prependSharedMailHeader($tmpl, $settings, $orgVars)'),
    'header rendered before the footer' => strpos($listener, 'prependSharedMailHeader(') < strpos($listener, 'appendSharedMailFooter($tmpl, $settings, $orgVars)'),
    'header strips confirmation buttons' => str_contains($listener, "str_replace('{{confirmationButtons}}', '', \$header)"),
    'header default' => str_contains($backend, 'self::MAILHEADERHTML => ""'),
    'header sanitized on save' => str_contains($state, 'BackendUtils::MAILHEADERHTML'),
    'header in the store' => str_contains($store, "mailHeaderHtml: ''"),
    'header in settings UI' => str_contains($ui, 'prop-name="mailHeaderHtml"'),
];
foreach ($tests as $reason => $passed) {
    if (!$passed) {
        throw new RuntimeException('Wiring missing: ' . $reason);
    }
}
echo "Mail header wiring smoke tests passed\n";
