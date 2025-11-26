# WhatsApp AI Notification Events

## Overview
This document lists all WhatsApp notification events in the DoctorAppointments plugin and where they are triggered.

---

## Notification Functions

### 1. **Patient New Session Notification** (`snks_send_new_session_notification`)
- **Template:** `new_session`
- **Parameters:** `doctor`, `day`, `date`, `time`
- **Trigger Hook:** `snks_appointment_created` (on `do_action('snks_appointment_created', $slot_id, $appointment_data)`)
- **Trigger Location:** `functions/helpers/ai-orders.php:201`
- **Handler:** `functions/helpers/whatsapp-ai-notifications.php:572`
- **When:** When an AI appointment is created after payment completion
- **Sent To:** Patient

### 2. **Doctor New Booking Notification** (`snks_send_doctor_new_booking_notification`)
- **Template:** `doctor_new`
- **Parameters:** `patient`, `day`, `date`, `time`
- **Trigger Hook:** `snks_appointment_created` (on `do_action('snks_appointment_created', $slot_id, $appointment_data)`)
- **Trigger Location:** `functions/helpers/ai-orders.php:201`
- **Handler:** `functions/helpers/whatsapp-ai-notifications.php:575`
- **When:** When an AI appointment is created after payment completion
- **Sent To:** Therapist

### 3. **Rochtah Activation Notification** (`snks_send_rosheta_activation_notification`)
- **Template:** `rosheta10`
- **Parameters:** `patient`, `doctor`
- **Trigger Location:** `functions/ai-prescription.php:260`
- **Handler:** Direct call
- **When:** When a therapist activates rochtah service for a patient (AI prescription notification sent)
- **Sent To:** Patient

### 4. **Rochtah Appointment Notification** (`snks_send_rosheta_appointment_notification`)
- **Template:** `rosheta_app`
- **Parameters:** `day`, `date`, `time`
- **Trigger Locations:**
  - `functions/ai-prescription.php:707` (AJAX handler)
  - `functions/ajax/timetable-ajax.php:951` (AJAX handler)
  - `functions/ai-integration.php:8407` (REST API handler)
- **Handler:** Direct call
- **When:** When a patient books a rochtah appointment slot
- **Sent To:** Patient

### 5. **Appointment Change Notification** (`snks_send_appointment_change_notification`)
- **Template:** `edit2`
- **Parameters:** `day`, `date`, `time`, `day2`, `date2`, `time2`
- **Trigger Location:** `functions/ai-integration.php:6076`
- **Handler:** Direct call
- **When:** When a patient reschedules an AI appointment
- **Sent To:** Patient

### 6. **Doctor Joined Notification** (`snks_send_doctor_joined_notification`)
- **Template:** `patient_rem_now`
- **Parameters:** None
- **Trigger Location:** `functions/ai-integration.php:6734`
- **Handler:** Direct call
- **When:** When a doctor/therapist joins a session (Jitsi meeting)
- **Sent To:** Patient

### 7. **Patient 24-Hour Reminder** (`patient_rem_24h`)
- **Template:** `patient_rem_24h`
- **Parameters:** `day`, `date`, `doctor`, `time`
- **Trigger:** Cron job - `snks_check_session_notifications` (runs every minute)
- **Handler:** `functions/crons/sms.php:89`
- **When:** 23-24 hours before session start time
- **Sent To:** Patient
- **Flags:** `notification_24hr_sent` in `wp_snks_provider_timetable`

### 7. **Patient 1-Hour Reminder** (`patient_rem_1h`)
- **Template:** `patient_rem_1h`
- **Parameters:** None
- **Trigger:** Cron job - `snks_check_session_notifications` (runs every minute)
- **Handler:** `functions/crons/sms.php:128`
- **When:** 1 hour before session start time
- **Sent To:** Patient
- **Flags:** `notification_1hr_sent` in `wp_snks_provider_timetable`

### 8. **Doctor Daily Reminder** (`doctor_rem`)
- **Template:** `doctor_rem`
- **Parameters:** `day`, `date`
- **Trigger:** Cron job - `snks_send_doctor_midnight_reminders` (runs daily at midnight)
- **Handler:** `functions/helpers/whatsapp-ai-notifications.php:642`
- **Scheduled:** `wp_schedule_event` at midnight with 'daily' recurrence
- **When:** Midnight reminder for therapist about tomorrow's sessions
- **Sent To:** Therapist
- **Flags:** `whatsapp_doctor_reminded` in `wp_snks_provider_timetable`

---

## Verification Checklist

- [x] **Patient New Session Notification** - Triggered via `snks_appointment_created` hook
- [x] **Doctor New Booking Notification** - Triggered via `snks_appointment_created` hook
- [x] **Rochtah Activation Notification** - Triggered in `snks_send_ai_prescription_notifications`
- [x] **Rochtah Appointment Notification** - Triggered in 3 locations (AJAX handlers + REST API)
- [x] **Doctor Joined Notification** - Triggered when doctor joins Jitsi meeting
- [x] **Patient 24-Hour Reminder** - Triggered by cron job every minute
- [x] **Patient 1-Hour Reminder** - Triggered by cron job every minute
- [x] **Doctor Daily Reminder** - Triggered by cron job every minute

---

## Notes

1. **Cron Job Schedule:** The reminder notifications run via `snks_check_session_notifications` cron that runs every minute.

2. **AI Session Detection:** Notifications only send for AI sessions. The system checks `$appointment_data['is_ai_session']` or uses `snks_is_ai_session()` function.

3. **WhatsApp Settings:** All notifications check `snks_get_whatsapp_notification_settings()` to ensure WhatsApp notifications are enabled.

4. **Database Flags:** Each notification type has a corresponding database flag to prevent duplicate sends:
   - `whatsapp_new_session_sent`
   - `whatsapp_doctor_notified`
   - `whatsapp_rosheta_activated`
   - `whatsapp_rosheta_booked`
   - `whatsapp_doctor_reminded`
   - `whatsapp_patient_now_sent`
   - `whatsapp_appointment_changed`
   - `notification_24hr_sent`
   - `notification_1hr_sent`

5. **Rochtah Notification Fix:** The rochtah appointment notification was recently added to `snks_book_rochtah_appointment` in `functions/ai-prescription.php:707` (commit `abfb63ee`).

---

## Summary

### Notification Flow Overview:

1. **Booking Created** → Patient & Doctor get "New Session" notifications (via `snks_appointment_created` hook)
2. **Rochtah Activated** → Patient gets "Rochtah Activation" notification
3. **Rochtah Booked** → Patient gets "Rochtah Appointment" notification
4. **24 Hours Before** → Patient gets "24-Hour Reminder" (via cron)
5. **1 Hour Before** → Patient gets "1-Hour Reminder" (via cron)
6. **Doctor Joins** → Patient gets "Doctor Joined" notification
7. **Midnight** → Doctor gets "Daily Reminder" for tomorrow's sessions (via cron)

### Critical Points:

- All AI session notifications require WhatsApp settings to be enabled
- Database flags prevent duplicate notifications
- Cron jobs run automatically (every minute for reminders, daily at midnight for doctor reminders)
- Patient reminders only send for AI sessions (`is_ai_session` check)
- Rochtah notifications have multiple trigger points to ensure delivery

