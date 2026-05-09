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

## Direct therapist–patient conversations (in-app + optional WhatsApp)

These events use `snks_ai_notifications` (and optional WhatsApp via `snks_send_whatsapp_template_message`). Logic lives in `functions/direct-conversations/snks-direct-conversations.php`.

### In-app types (`snks_ai_notifications.type`)

| Type | When | Notes |
|------|------|--------|
| `direct_conversation_started` | Exactly when the **first** message in a thread is stored (conversation + first row in `wp_snks_direct_conversation_messages`) | Recipient is the **other** participant. **No** further immediate rows for later messages in the same `conversation_id`. |
| `direct_conversation_daily_digest` | Once per calendar day per user, at the scheduled digest hour, **only if** the user has **unread** messages whose `created_at` falls within the configured lookback window (default 3 days, option `snks_conversation_unread_summary_days`) | Older unread messages outside the window are **not** counted and do not trigger the digest. |

### WhatsApp (optional)

Requires `snks_ai_notifications_enabled`, a configured WhatsApp Cloud API, and a number on the user via `snks_get_user_whatsapp`.

**Templates (Jalsah AI → Direct conversations):** each option must be set to your approved Meta template name. If an option is **empty**, WhatsApp is **not** sent for that event (in-app notifications still apply).

| Option | Template name (example) | When | Body parameters from plugin |
|--------|-------------------------|------|-------------------------------|
| `snks_whatsapp_template_dc_therapist` | `chat_th` | Patient sends the **first** message in a thread (therapist notified), and therapist **daily digest** WhatsApp when old-unread threshold is met | None (static approved body in Meta) |
| `snks_whatsapp_template_dc_patient_first` | `chat_pt1` | Therapist sends the **first** message (patient notified) | `chat_link` (deep link to the conversation in the SPA) |
| `snks_whatsapp_template_dc_patient_digest` | `chat_pt2` | Patient **daily digest** WhatsApp when old-unread threshold is met | `chat_link` |

### Cron

- Hook: `snks_direct_conversations_daily_digest` (daily; next run derived from `snks_direct_conv_digest_hour` in site timezone).
- Handler: `snks_direct_conversations_run_daily_digest()`.

### Admin settings

- **Jalsah AI → Direct conversations:** window days, digest hour, max upload bytes, allowed MIME list, optional Jalsah frontend base URL (`snks_jalsah_ai_frontend_url`), WhatsApp template names for therapist (`chat_th`), patient first message (`chat_pt1`), and patient digest (`chat_pt2`) — no alternate fallback options.

### Live thread updates (low server load)

- `GET /api/ai/direct-conversations/feed?summary=1` — returns only `unread_count` and `newest_incoming_message_id` (COUNT + MAX). Clients poll this on an interval and call the full feed when either value changes.
- Full `GET .../feed` responses also include `newest_incoming_message_id` so the client can sync its poll signature after a full load.
- `GET /api/ai/direct-conversations/{id}/messages?since_id={lastMessageId}` — returns rows with `id > since_id` (incremental). Omit `since_id` for a full thread (up to the usual limit).
- Therapist hub (WordPress): AJAX action `snks_direct_conv_thread_since` with `conversation_id` and `since_id` for the same incremental fetch.

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

