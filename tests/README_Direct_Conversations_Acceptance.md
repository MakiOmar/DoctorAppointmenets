# Direct conversations — acceptance checklist

Run after deployment and **flush permalinks** (Settings → Permalinks → Save) so `/api/ai/direct-conversations/*` rewrite rules exist.

## Database

- [ ] Tables `wp_snks_direct_conversations` and `wp_snks_direct_conversation_messages` exist.
- [ ] Optional column `link_url` exists on `wp_snks_ai_notifications` after first notification with a link.

## Notification rules

1. [ ] Therapist T and patient P have no row yet. **Therapist** sends first message. Patient receives **one** `direct_conversation_started` in-app notification (WhatsApp `chat_pt1` only if `snks_whatsapp_template_dc_patient_first` is set and phone exists).
1b. [ ] Patient sends first message in a new thread. **No** `direct_conversation_started` for therapist (by design).
2. [ ] Patient sends a **second** message in the same thread. **No** new immediate `direct_conversation_started` for therapist.
3. [ ] Leave messages unread with `created_at` within N days. Run `do_action( 'snks_direct_conversations_daily_digest' );` (e.g. WP-CLI `wp eval "do_action('snks_direct_conversations_daily_digest');"`). User receives **at most one** `direct_conversation_daily_digest` per calendar day.
4. [ ] Unread messages only older than N days: digest run produces **no** digest for those (adjust `snks_conversation_unread_summary_days` in **Jalsah AI → Direct conversations**).

## Therapist hub (Elementor)

- [ ] Logged-in doctor on a page with `[snks_therapist_conversations_hub]`: bell shows **green** when unread count &gt; 0, **red** when zero.
- [ ] Dropdown lists recent inbox rows; clicking opens thread panel and scrolls to bottom.
- [ ] “View all conversations” lists patients; click opens thread.
- [ ] Send text and optional attachment respects max size and MIME allowlist from admin settings.

## Patient (Jalsah AI)

- [ ] Bell loads `/api/ai/direct-conversations/feed` (JWT).
- [ ] Background refresh uses `GET .../feed?summary=1` (COUNT + MAX only); full feed loads when summary changes or when opening the bell.
- [ ] Thread view uses `GET .../messages?since_id=<lastId>` for incremental updates (no full thread on every tick).
- [ ] Opening an item navigates to `/direct-conversations/:id` and can send/reply.
- [ ] `/notifications` uses the same feed and mark-read endpoint.

## Attachments

- [ ] Upload rejected when MIME not in allowlist or file exceeds `snks_direct_conv_max_upload_bytes`.
