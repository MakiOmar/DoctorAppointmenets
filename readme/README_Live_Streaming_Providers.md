# Live Streaming Providers (Jitsi + Google Meet)

## Overview

The plugin supports a **global live-stream provider** stored in WordPress options:

| Value | Behavior |
|-------|----------|
| `jitsi` (default) | Existing Jitsi embeds, shortlinks, timers, and wait-for-therapist flows |
| `google_meet` | Google Meet **fully replaces** Jitsi — no embeds, no `meet.jit.si` fallback |

Admin UI: **Jalsah AI → Google Meet URLs** (`functions/admin/google-meet-urls-manager.php`).

## Database

Table `{prefix}snks_google_meet_urls` (see `includes/google-meet-urls-table.php`):

- `meet_url` — unique pool entry
- `status` — `available` | `assigned`
- `assigned_timetable_id` or `assigned_rochtah_booking_id` (exclusive)

## Core API (`functions/helpers/meeting-service.php`)

| Function | Purpose |
|----------|---------|
| `snks_is_google_meet_active()` | Provider is `google_meet` |
| `snks_should_use_jitsi_meeting_timers()` | `false` when Meet is active |
| `snks_assign_google_meet_url( $type, $id )` | Assign first available URL (`timetable` or `rochtah`) |
| `snks_assign_google_meet_url_manual( $url_id, $type, $session_id )` | Assign a specific pool row to a session (admin); replaces existing URL on that session |
| `snks_validate_google_meet_assign_target( $type, $id )` | Validate timetable (online, booked) or Rochtah (`confirmed`) |
| `snks_release_google_meet_url( $type, $id )` | Release on cancel/reschedule (by timetable/Rochtah ID) |
| `snks_unassign_google_meet_url( $url_id )` | Return a pool row to `available` (admin or code); fires `snks_google_meet_unassigned` |

Hooks: `snks_google_meet_assigned`, `snks_google_meet_unassigned`.

Admin **Manual assignment** section: assign by URL ID + target, or **Assign first available URL** to a timetable/Rochtah ID. Per-row **Assign** on available URLs.

**Bulk actions** (pool table): select rows → **Unassign** (assigned only, returns to pool) or **Delete** (removes from pool; assigned sessions lose their Meet link).
| `snks_get_session_meeting_for_timetable( $id )` | Join payload for sessions |
| `snks_get_session_meeting_for_rochtah( $id )` | Join payload for Rochtah |
| `snks_meeting_on_rochtah_confirmed( $booking_id )` | Assign on Rochtah confirm |

REST: `GET /wp-json/jalsah-ai/v1/live-stream-settings` — frontend reads provider and `use_meeting_timers`.

## Booking hooks

Meet URLs are assigned when an **online** session is booked and released on cancel/reschedule:

- WooCommerce: `functions/actions/create-appointment.php`
- AI orders: `functions/helpers/ai-orders.php` (`book_slot_for_order`, Rochtah payment)
- Manual booking/reschedule: `functions/helpers/admin-manual-booking.php`
- Cancel: `functions/ai-integration.php`, `functions/ajax/timetable-ajax.php`
- Rochtah confirm: `functions/ai-integration.php`, `functions/ajax/timetable-ajax.php`, `functions/helpers/ai-orders.php`
- Generic: `snks_appointment_created` → `snks_meeting_service_on_appointment_created`

**Empty pool:** booking fails (no Jitsi fallback). Low-pool admin notice + email (24h dedupe).

## Shortlinks & guest pages

- `snks_get_meeting_shortlink( $timetable_id )` — issues `/meeting/{token}` only (no pool assignment; read-only)
- Meet URLs are assigned **only** at booking confirmation, Rochtah confirm, admin manual assign, and `snks_appointment_created` — never when building links, listing sessions, or sending reminders
- `snks_get_notification_meeting_link( $timetable_id )` — SMS/WhatsApp/email use this (Meet direct URL when active, else `/meeting/{token}`)
- `snks_get_notification_meeting_link_for_rochtah( $booking_id )` — same for Rochtah
- `/j/{token}` — Meet mode renders `snks_render_guest_google_meet_room()`; otherwise redirects to frontend
- REST `meeting-by-token` returns `google_meet_join_url`, `live_stream_provider`, `use_meeting_timers`

## Disabled in Meet mode

- Cron/SMS timed online reminders (`functions/crons/sms.php`)
- Doctor-presence SMS on join (`functions/ajax/meeting-room.php`)
- WhatsApp doctor-joined (`functions/helpers/whatsapp-ai-notifications.php`)
- Frontend therapist polling and countdown gates (`useLiveMeeting` composable)
- Classic WP countdown script (`functions/scripts/my-booking.php`)
- Patient wait-for-doctor loader on meeting-room shortcode

## Frontend (Jalsah AI)

- `jalsah-ai-frontend/src/composables/useLiveMeeting.js` — settings load, `openGoogleMeetUrl()`
- `MeetingRoom.vue`, `RochtahMeetingRoom.vue` — new-tab Meet, no Jitsi DOM when Meet
- `Appointments.vue`, `Session.vue`, `PrescriptionCard.vue` — Meet join paths

## QA checklist

1. **Admin:** Switch provider, bulk-import URLs (duplicates skipped), verify pool count and low-pool alert.
2. **Book online session (AI + WC + manual):** row gets assigned URL; cancel releases URL.
3. **Empty pool:** booking blocked with clear error.
4. **Meet join (patient/doctor/guest):** opens new tab; no Jitsi iframe/modal.
5. **Shortlink `/j/{token}` and `/meeting/{token}`:** resolve and open Meet when provider is Meet.
6. **Rochtah:** confirm assigns URL; join from Appointments and doctor dashboard.
7. **Jitsi mode regression:** timers, wait-for-doctor, and embeds still work when provider is `jitsi`.
