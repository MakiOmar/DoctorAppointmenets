# Session reminders vs. booking summary notifications

This document explains two separate cron-driven flows in `functions/crons/sms.php`:

| Function | Primary audience | Channel | Schedule |
|----------|------------------|---------|----------|
| `snks_send_session_notifications` | **Patient** (per session) | WhatsApp (AI) or SMS (legacy) | Every minute |
| `send_booking_notifications` | **Therapist / provider** (aggregated) | Firebase push (if available) | Hourly cron, logic runs only at **23:00–23:59** site time |

---

## `snks_send_session_notifications`

### Purpose

Sends **per-appointment reminders** to the **client (patient)** for sessions that are still **`session_status = 'open'`** in `wp_snks_provider_timetable`.

It covers two windows relative to **`date_time`** (WordPress local time):

1. **~24 hours before** the session (between **23 and 24 hours** before `date_time`).
2. **Up to 1 hour before** the session (between **now** and **1 hour** before `date_time`).

Each window is only acted on once per row, using flags:

- `notification_24hr_sent` — set to `1` after a successful 24h reminder.
- `notification_1hr_sent` — set to `1` after a successful 1h reminder.

### When it runs

- WordPress cron event: **`snks_check_session_notifications`**
- Registered interval: **`every_minute`**
- Hook: `add_action( 'snks_check_session_notifications', 'snks_send_session_notifications' );`

So in practice it runs roughly every minute (subject to site traffic and WP-Cron behavior).

### Who receives notifications

**The patient** associated with the row: `client_id`.

- Phone is taken from user meta **`billing_phone`**, or falls back to **`user_login`** if empty.
- There is special handling for users in the **`doctor`** role and numbers missing a `+2` prefix (Egypt-style normalization).

### How “AI session” is detected

For routing between WhatsApp templates and legacy SMS:

1. If `settings` contains **`ai_booking`**, the row is treated as an AI session.
2. Otherwise, if `order_id > 0`, the code loads the WooCommerce order and checks meta **`from_jalsah_ai`** or **`is_ai_session`**.

### What is sent

**24-hour reminder**

- **AI sessions** (and if `snks_send_whatsapp_template_message` + WhatsApp settings exist): WhatsApp template from `snks_get_whatsapp_notification_settings()['template_patient_rem_24h']` with parameters: Arabic day name, date, therapist name, time — **only if** WhatsApp notifications are **enabled** in settings.
- **Non-AI sessions**: **SMS** via `send_sms_via_whysms()` with Arabic copy; **online** sessions include a meeting short link; **offline** sessions omit the link.
- **Extra rule for 24h:** The reminder is only sent when the **current local hour is ≥ 9** (avoids sending “tomorrow” messages in the middle of the night).

**1-hour reminder**

- **AI + online + WhatsApp enabled**: WhatsApp template `template_patient_rem_1h` (no template parameters in code).
- **Otherwise** (non-AI or WhatsApp off): **SMS** with meeting link (code path is tied to **`attendance_type === 'online'`** for this block).

### Processing limits

- The SQL query uses **`LIMIT 20`** per run, so heavy days are spread across multiple cron ticks.

### Source file reference

- `functions/crons/sms.php` — function `snks_send_session_notifications()` (scheduled at top of same file).

---

## `send_booking_notifications`

### Purpose

Sends **one summary notification per therapist per calendar day**: how many **open** sessions they have **tomorrow** (`DATE(date_time)` = tomorrow’s date in site time).

This is **not** a per-patient SMS; it is aimed at the **provider user** (`user_id` on the timetable row = therapist).

### When it runs

- Cron event: **`send_hourly_booking_notifications`**
- Interval: **`hourly`** (scheduled from `schedule_hourly_booking_notifications()` on `wp` hook).
- **Important:** The function **returns immediately** unless the current local hour is **23** (11 PM–11:59 PM). So the meaningful work happens **at most once per day**, during the 11 PM hour, whenever hourly cron fires.

### Who receives notifications

**Therapists (providers)** grouped by `user_id` in `wp_snks_provider_timetable`:

- One row per therapist with `COUNT(*)` of open bookings for tomorrow.
- **Deduplication:** A transient `notified_user_{user_id}_{Y-m-d}` prevents sending more than once per therapist per **local calendar day** (24-hour transient).

### Channel

- If **`FbCloudMessaging\AnonyengineFirebase`** exists, it calls **`trigger_notifier( title, message, user_id, '' )`** — **Firebase push** to that user ID (therapist app / device registration as implemented by that class).
- If the class is missing, nothing is sent (no SMS fallback in this function).

### Source file reference

- `functions/crons/sms.php` — function `send_booking_notifications()` and `schedule_hourly_booking_notifications()`.

---

## Quick comparison

| | `snks_send_session_notifications` | `send_booking_notifications` |
|---|-----------------------------------|------------------------------|
| **Recipient** | Patient (`client_id`) | Therapist (`user_id` on slot) |
| **Granularity** | Each open session row | Count of open sessions tomorrow per therapist |
| **Typical channels** | WhatsApp (AI) / SMS | Firebase push |
| **Cron frequency** | Every minute | Hourly (effective send: ~11 PM hour only) |
| **DB flags / cache** | `notification_24hr_sent`, `notification_1hr_sent` | Transient per therapist per day |

---

## Related documentation

- WhatsApp AI reminder integration: `readme/README_WhatsApp_AI_Notifications.md` (references `snks_send_session_notifications`).
