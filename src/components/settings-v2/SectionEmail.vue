<script setup>
import {useSettingsStore} from "../../stores/settings";
import ComboCheckbox from "./ComboCheckbox.vue";
import LabelAccordion from "../LabelAccordion.vue";
import ComboInput from "./ComboInput.vue";
import ComboSelect from "./ComboSelect.vue";

const emit = defineEmits(['show-settings-modal'])
const settingsStore = useSettingsStore()
const settings = settingsStore.settings

const contribInterceptor = () => {
	if (settingsStore.k === true) {
		return false
	}
	settingsStore.setOne('notifyCancelPending', !settings.notifyCancelPending, false, (res) => {
		if (res.status === 202) {

			emit('show-settings-modal', {
				type: res.data.type,
				message: res.data.message
			})
		}
	})

	return true
}

const emailPlaceholders = [
    {key: 'salutation', description: t('appointments', 'Localized greeting with attendee name')},
    {key: 'name', description: t('appointments', 'Name exactly as entered in the booking form')},
    {key: 'email', description: t('appointments', 'Email address of the attendee')},
    {key: 'attendeePhone', description: t('appointments', 'Phone number of the attendee, if provided')},
    {key: 'date', description: t('appointments', 'Appointment date in the recipient language')},
    {key: 'time', description: t('appointments', 'Appointment start time')},
    {key: 'endTime', description: t('appointments', 'Appointment end time')},
    {key: 'dateTime', description: t('appointments', 'Full appointment date and time')},
    {key: 'durationMinutes', description: t('appointments', 'Appointment duration in minutes')},
    {key: 'location', description: t('appointments', 'Appointment location, if provided')},
    {key: 'organization', description: t('appointments', 'Organization name')},
    {key: 'organizationEmail', description: t('appointments', 'Organization email address')},
    {key: 'phone', description: t('appointments', 'Organization phone number')},
    {key: 'status', description: t('appointments', 'Appointment status (organizer notification only)')},
    {key: 'confirmationButtons', description: t('appointments', 'Original Confirm and Cancel buttons (booking request and confirmation reminder only)')},
]

const confirmationButtonsHelp = t('appointments', 'Place {{confirmationButtons}} on its own line between HTML blocks in the booking request template to choose where the original buttons appear. Without the marker, the buttons are appended automatically.')
const namePlaceholderHelp = t('appointments', 'Use {{name}} for the name exactly as entered. First and last names cannot be determined reliably from one field.')

const emailExample = '<p>Hallo ' + '{' + '{name}' + '}' + ',</p> <p>Dein Termin ist am ' + '{' + '{date}' + '}' + ' um ' + '{' + '{time}' + '}' + ' Uhr.</p>'

const cancelPendingOptions = [
	{value: 0, label: t('appointments', 'No auto-cancel')},
	{value: 2, label: t('appointments', '2 hours')},
	{value: 4, label: t('appointments', '4 hours')},
	{value: 8, label: t('appointments', '8 hours')},
	{value: 24, label: t('appointments', '24 hours')},
	{value: 48, label: t('appointments', '48 hours')},
	{value: 96, label: t('appointments', '96 hours')},
]

</script>

<template>
	<div class="ps-section-wrap">
		<ComboCheckbox
				class="ps-vert-spacing"
				prop-name="icsFile"
				:label="t('appointments', 'Attach .ics file to confirm/cancel emails')"
				:store="settingsStore">
			<template #help>
				{{ t('appointments', 'An .ics attachment will automatically be generated for notification emails, allowing better integration with email/calendar apps that support it.') }}
			</template>
		</ComboCheckbox>
		<LabelAccordion
				:label="t('appointments','Email Attendee when the appointment is:')">
			<template #helpPopover>
				{{ t('appointments', 'Attendees will be notified via email when their upcoming appointments are updated or deleted in the calendar app or through some other external mechanism. Only changes to Date/Time, Status, or Location will trigger the "Modified" notification.') }}
			</template>
		</LabelAccordion>
		<div class="srgdev-appt-sb-indent">
			<ComboCheckbox
					prop-name="attMod"
					:label="t('appointments', 'Modified (Time, Status, Location)')"
					:store="settingsStore"/>
			<ComboCheckbox
					prop-name="attDel"
					:label="t('appointments', 'Deleted')"
					:store="settingsStore"/>
		</div>
		<LabelAccordion
				:label="t('appointments','Email Me when an appointment is:')">
			<template #helpPopover>
				{{ t('appointments', 'A notification email will be sent to you when an appointment is booked via the public page or when an upcoming appointment is confirmed or canceled through the email links.') }}
			</template>
		</LabelAccordion>
		<div class="srgdev-appt-sb-indent">
			<ComboCheckbox
					prop-name="meReq"
					:label="t('appointments', 'Requested')"
					:store="settingsStore"/>
			<ComboCheckbox
					prop-name="meConfirm"
					:label="t('appointments', 'Confirmed')"
					:store="settingsStore"/>
			<ComboCheckbox
					prop-name="meCancel"
					:label="t('appointments', 'Canceled')"
					:store="settingsStore"/>
		</div>
		<ComboCheckbox
				class="ps-vert-spacing"
				prop-name="skipEVS"
				:label="t('appointments', 'Skip email validation step')"
				:store="settingsStore">
			<template #help>
				{{ t('appointments', 'When this option is selected, the "... action needed" validation email will NOT be sent to the attendee. Instead, the "... Appointment is confirmed" message will be sent right away, and the "All done" page will be shown when the form is submitted.') }}
			</template>
		</ComboCheckbox>

		<div
				v-if="settings.skipEVS===false"
				style="margin-bottom: 1.125em">
			<ComboSelect
					:label="t('appointments', 'Auto-cancel unconfirmed appointments after')"
					prop-name="cancelPendingHours"
					:default-value="0"
					:placeholder="t('appointments', 'No auto-cancel')"
					:options="cancelPendingOptions"
					:store="settingsStore"/>
			<ComboCheckbox
					:disabled="settings.cancelPendingHours===0"
					style="margin-top: -.75em"
					:label="t('appointments', 'Send a reminder before canceling')"
					:click-interceptor="contribInterceptor"
					prop-name="notifyCancelPending"
					:store="settingsStore"/>
		</div>

		<ComboInput
				v-if="settings.skipEVS===false"
				type="textarea"
				prop-name="vldNote"
				:label="t('appointments', 'Additional VALIDATION email text:')"
				:store="settingsStore"/>
		<ComboInput
				type="textarea"
				prop-name="cnfNote"
				:label="t('appointments', 'Additional CONFIRMATION email text:')"
				:store="settingsStore"/>
		<ComboInput
				type="textarea"
				prop-name="icsNote"
				:label="t('appointments', 'Additional ICS file description:')"
				:store="settingsStore">
			<template #help>
				{{ t('appointments', "This text will be appended to the end of the event's 'DESCRIPTION' property. Do NOT use HTML here, as most apps do NOT render it.") }}
			</template>
		</ComboInput>
		<LabelAccordion :label="t('appointments', 'Custom email templates')" :accordion="true">
            <p class="email-vars-intro">{{ t('appointments', 'Placeholders are replaced automatically in email subjects and HTML bodies. Write them exactly as shown:') }}</p>
            <p class="email-vars-intro">{{ namePlaceholderHelp }}</p>
            <p class="email-vars-intro">{{ t('appointments', 'Images in email HTML support HTTPS URLs. Mail clients may block images until recipients allow them.') }}</p>
            <div class="email-vars-list">
                <div v-for="item in emailPlaceholders" :key="item.key" class="email-vars-row">
                    <code>{{ '{' + '{' + item.key + '}' + '}' }}</code>
                    <span>{{ item.description }}</span>
                </div>
            </div>
            <p class="email-vars-example">
                {{ t('appointments', 'Example for an HTML email:') }}
                <code>{{ emailExample }}</code>
            </p>
            <p class="email-vars-intro">{{ confirmationButtonsHelp }}</p>
            <p class="email-vars-intro">{{ t('appointments', 'Unavailable values are left blank. Confirmation buttons appear only in emails requiring a response.') }}</p>
            <p class="email-vars-intro">{{ t('appointments', 'If no name was entered, the name placeholder is empty and the greeting has no name.') }}</p>
            <ComboInput
                    type="textarea"
                    prop-name="mailHeaderHtml"
                    :label="t('appointments', 'Shared email header – HTML')"
                    :store="settingsStore">
                <template #help>
                    {{ t('appointments', 'Automatically rendered at the top of all Appointments emails for this booking page, above the body text. Intended for a logo image, heading text or banner. Supports HTML (including h1–h4 headings and HTTPS images) and email placeholders. Mail clients may block images until recipients allow them.') }}
                </template>
            </ComboInput>
			<ComboInput prop-name="mailBookingSubject" :label="t('appointments', 'Booking request – Subject')" :store="settingsStore"/>
			<ComboInput type="textarea" prop-name="mailBookingHtml" :label="t('appointments', 'Booking request – HTML')" :store="settingsStore"/>
			<ComboInput prop-name="mailConfirmedSubject" :label="t('appointments', 'Confirmation – Subject')" :store="settingsStore"/>
			<ComboInput type="textarea" prop-name="mailConfirmedHtml" :label="t('appointments', 'Confirmation – HTML')" :store="settingsStore"/>
			<ComboInput prop-name="mailCancelledSubject" :label="t('appointments', 'Cancellation – Subject')" :store="settingsStore"/>
			<ComboInput type="textarea" prop-name="mailCancelledHtml" :label="t('appointments', 'Cancellation – HTML')" :store="settingsStore"/>
			<ComboInput prop-name="mailUpdatedSubject" :label="t('appointments', 'Appointment changes – Subject')" :store="settingsStore"/>
			<ComboInput type="textarea" prop-name="mailUpdatedHtml" :label="t('appointments', 'Appointment changes – HTML')" :store="settingsStore"/>
			<ComboInput prop-name="mailReminderSubject" :label="t('appointments', 'Reminder – Subject')" :store="settingsStore"/>
			<ComboInput type="textarea" prop-name="mailReminderHtml" :label="t('appointments', 'Reminder – HTML')" :store="settingsStore"/>
			<ComboInput prop-name="mailOrganizerSubject" :label="t('appointments', 'Organizer notification – Subject')" :store="settingsStore"/>
			<ComboInput type="textarea" prop-name="mailOrganizerHtml" :label="t('appointments', 'Organizer notification – HTML')" :store="settingsStore"/>
            <ComboInput
                    type="textarea"
                    prop-name="mailFooterHtml"
                    :label="t('appointments', 'Shared email footer – HTML')"
                    :store="settingsStore">
                <template #help>
                    {{ t('appointments', 'Automatically appended to all Appointments emails for this booking page, including booking requests, confirmations, cancellations, changes, reminders and organizer notifications. Intended for legal notices, privacy information or contact details. Supports HTML and email placeholders.') }}
                </template>
            </ComboInput>
		</LabelAccordion>
	</div>
</template>

<style scoped>
.email-vars-intro {
    margin: .65em 0;
}
.email-vars-list {
    display: grid;
    gap: .35em;
    margin: .75em 0 1em;
}
.email-vars-row {
    display: flex;
    gap: 1em;
    align-items: baseline;
    flex-wrap: wrap;
}
.email-vars-row code {
    display: inline-block;
    min-width: 11em;
    white-space: nowrap;
}
.email-vars-row span {
    flex: 1;
    min-width: 12em;
}
.email-vars-example code {
    display: block;
    margin-top: .4em;
    overflow-wrap: anywhere;
}
</style>