# Direct conversations — WhatsApp notifications (unread & first message)

This document describes **when**, **where**, and **how** WhatsApp messages are sent for **therapist–patient direct conversations** (not AI session booking WhatsApp — see [README_WhatsApp_AI_Notifications.md](./README_WhatsApp_AI_Notifications.md)).

A shorter cross-reference also exists in [NOTIFICATION_EVENTS.md](./NOTIFICATION_EVENTS.md) under “Direct therapist–patient conversations”.

---

## Related docs

| Document | Purpose |
|----------|---------|
| [NOTIFICATION_EVENTS.md](./NOTIFICATION_EVENTS.md) | All notification types (sessions + direct chat) |
| [README_WhatsApp_AI_Notifications.md](./README_WhatsApp_AI_Notifications.md) | Session/booking WhatsApp (`snks_send_whatsapp_template_message`, API credentials) |
| [README_Conversation_Flow_AR.md](./README_Conversation_Flow_AR.md) | Product flow (Arabic, non-technical) |
| [tests/README_Direct_Conversations_Acceptance.md](../tests/README_Direct_Conversations_Acceptance.md) | Manual acceptance checklist |

---

## Summary

| Event | Trigger | In-app | WhatsApp template option | Recipient |
|-------|---------|--------|--------------------------|-----------|
| **Conversation started** | First message in thread **and** sender is **therapist** | `direct_conversation_started` | Patient: `snks_whatsapp_template_dc_patient_first` (`chat_pt1`) | **Patient only** |
| **Daily unread digest** | WP-Cron once per day at configured hour | `direct_conversation_daily_digest` (max once per calendar day per user) | Therapist: `snks_whatsapp_template_dc_therapist` (`chat_th`) — static body | Therapist or patient |
| | | | Patient: `snks_whatsapp_template_dc_patient_digest` (`chat_pt2`) — `{{chat_link}}` | |

There is **no** immediate WhatsApp (or in-app “started”) when the **patient** sends the first message. Later messages in the same thread also do **not** trigger immediate notifications.

---

## Where the code lives

| Piece | File | Symbol |
|-------|------|--------|
| Message insert + first-message hook | `functions/direct-conversations/snks-direct-conversations.php` | `snks_direct_conversations_insert_message()` |
| First-message in-app + WhatsApp | same | `snks_direct_conversations_notify_conversation_started()` |
| Daily digest (in-app + WhatsApp) | same | `snks_direct_conversations_run_daily_digest()` |
| Cron schedule | same | `snks_direct_conversations_schedule_digest_cron()` on `init` |
| Cron hook | same | `snks_direct_conversations_daily_digest` |
| Admin settings + test send | `functions/admin/direct-conversations-settings.php` | **Jalsah AI → Direct conversations** |
| WhatsApp HTTP sender | `functions/helpers/whatsapp-ai-notifications.php` | `snks_send_whatsapp_template_message()` |
| User phone lookup | same | `snks_get_user_whatsapp()` |

---

## Flow diagrams

### 1. First message (therapist → patient only)

```
Therapist sends message
  → snks_direct_conversations_insert_message()
  → COUNT(messages in thread) === 1 AND sender_type === 'therapist'
  → snks_direct_conversations_notify_conversation_started( patient_user_id, ... )
       → snks_create_ai_notification( type: direct_conversation_started )
       → if snks_ai_notifications_enabled AND phone:
            snks_send_whatsapp_template_message( chat_pt1, { chat_link, enter } )
```

`chat_link` / `enter` come from `snks_direct_conversations_patient_first_whatsapp_params()` (password-protected **dc-access** guest URL + rotated numeric password).

### 2. Daily unread digest (one per user per day)

```
WP-Cron: snks_direct_conversations_daily_digest  (daily @ snks_direct_conv_digest_hour, site TZ)
  → snks_direct_conversations_run_daily_digest()
  → users with ANY unread in last N days (snks_conversation_unread_summary_days)
  → skip if digest in-app row already exists today for that user
  → snks_create_ai_notification( type: direct_conversation_daily_digest )
  → WhatsApp when same user has unread in window (created_at >= NOW - N days)
  → therapist: chat_th, no body parameters
  → patient:   chat_pt2, { chat_link } → SPA link to newest qualifying unread thread in window
```

**Important:** In-app and WhatsApp digest both use the **same rule**: unread messages **sent within the last N days** (`snks_conversation_unread_summary_days`). Messages older than N days are **not** included.

---

## Prerequisites (all WhatsApp sends)

1. **Global toggle:** `snks_ai_notifications_enabled` = `1` (Jalsah AI notifications).
2. **WhatsApp Cloud API** configured: `snks_whatsapp_api_url`, `snks_whatsapp_api_token`, `snks_whatsapp_phone_number_id` (Registration Settings).
3. **Template name** saved for the event in **Jalsah AI → Direct conversations** (empty option = no WhatsApp for that branch; in-app may still fire).
4. **Recipient phone:** `snks_get_user_whatsapp( $user_id )` — tries `billing_phone`, then `user_login`, then `whatsapp` user meta.

---

## Settings (wp_options)

| Option | Default | Role |
|--------|---------|------|
| `snks_conversation_unread_summary_days` | `3` | Include only unread messages **sent within** this many days (in-app + WhatsApp) |
| `snks_direct_conv_digest_hour` | `20` | Hour (0–23, site timezone) for daily cron |
| `snks_whatsapp_template_dc_therapist` | (empty) | Meta template e.g. `chat_th` |
| `snks_whatsapp_template_dc_patient_first` | (empty) | Meta template e.g. `chat_pt1` |
| `snks_whatsapp_template_dc_patient_digest` | (empty) | Meta template e.g. `chat_pt2` |

Patient deep links use **Jalsah AI → General settings → Frontend URLs** (first URL), via `snks_direct_conversations_patient_app_base_url()`.

---

## Template parameters

| Template | When | Body parameters (named) |
|----------|------|-------------------------|
| `chat_th` | Therapist **daily digest** WhatsApp only (current code) | *(none — static approved copy in Meta)* |
| `chat_pt1` | Patient notified on **therapist’s first** message in thread | `chat_link` (dc-access URL), `enter` (access password) |
| `chat_pt2` | Patient daily digest WhatsApp | `chat_link` (SPA `/direct-conversations/{id}` for oldest unread past threshold) |

Parameters are passed to Meta as **named** body variables in `snks_send_whatsapp_template_message()`.

---

## Admin: test sends

**Jalsah AI → Direct conversations** → “Test WhatsApp templates”:

- AJAX action: `snks_dc_test_whatsapp` (`functions/admin/direct-conversations-settings.php`)
- Does **not** require `snks_ai_notifications_enabled`
- Buttons: `chat_th`, `chat_pt1` (needs conversation ID), `chat_pt2` (optional conversation ID or sample `chat_link`)

---

## Digest debug (troubleshooting)

**Jalsah AI → Direct conversations → Daily digest debug**

| Control | Purpose |
|---------|---------|
| **Digest debug logging** (settings checkbox) | Stores the last **15** cron runs in option `snks_dc_digest_debug_log` |
| **Run digest now** | Runs `snks_direct_conversations_run_daily_digest( true )` and shows a per-user report |
| **Diagnose user ID** | `snks_direct_conversations_digest_diagnose_user()` — preview blockers without sending |
| **Clear debug log** | Deletes stored runs |

Each user entry in a run report includes `in_app.status` / `reason` and `whatsapp.status` / `reason`. Typical skip reasons:

| Reason | Meaning |
|--------|---------|
| `digest_already_sent_today` | In-app digest row already exists for this calendar day |
| `no_unread_in_summary_window` | No unread in last N days (diagnose only) |
| `no_unread_in_summary_window` | No unread in the last N days |
| `ai_notifications_disabled` | Global `snks_ai_notifications_enabled` is off |
| `no_whatsapp_phone` | `snks_get_user_whatsapp()` returned empty |
| `whatsapp_template_dc_*_empty` | Template option not set in admin |

With `WP_DEBUG_LOG` enabled, lines are also written as `[SNKS DC digest] …` to the PHP error log.

---

## Manual / staging triggers

```bash
# Run digest immediately (WP-CLI)
wp eval "do_action('snks_direct_conversations_daily_digest');"
```

Check next scheduled run:

```bash
wp cron event list --hook=snks_direct_conversations_daily_digest
```

After changing digest hour, save **Direct conversations** settings (reschedules cron) or call `snks_direct_conversations_reschedule_digest_cron()`.

---

## What does *not* send WhatsApp

- Patient sends first message (no immediate notification).
- Second and later messages in an existing thread.
- Daily digest when user has **no** unread within the N-day window (older unread is ignored).
- Missing template option, missing phone, or global notifications disabled.
- Therapist when patient starts the thread (no `direct_conversation_started` path today).

---

## Database touchpoints

- Messages: `wp_snks_direct_conversation_messages` (`is_read`, `recipient_user_id`, `created_at`)
- In-app rows: `wp_snks_ai_notifications` (`type` = `direct_conversation_started` | `direct_conversation_daily_digest`)

Marking a conversation read (API `POST /api/ai/direct-conversations/{id}/read` or therapist hub) sets `is_read = 1` for that recipient and lowers bell counts; it does not cancel a digest already sent that day.
