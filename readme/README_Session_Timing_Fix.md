# Session Timing Fix — Problems & Solutions by File

**Date:** 2026-05-19  
**Area:** Doctor/patient booking cards, session start buttons, live countdown timers, meeting room early-join guard  
**Symptom:** At the real session start time, PHP and JavaScript showed opposite/wrong states (e.g. 5:00 PM session both “not started” and “passed”, while 6:00 PM session appeared ready to start).

---

## Table of contents

1. [Executive summary](#executive-summary)
2. [Observed bug](#observed-bug)
3. [Root cause](#root-cause)
4. [Architecture after fix](#architecture-after-fix)
5. [Files changed](#files-changed)
6. [File-by-file reference](#file-by-file-reference)
7. [UI strings affected](#ui-strings-affected)
8. [Testing checklist](#testing-checklist)
9. [Related code (unchanged but relevant)](#related-code-unchanged-but-relevant)
10. [Future guidelines](#future-guidelines)

---

## Executive summary

Booking session timing was calculated in **two incompatible ways**:

| Layer | Before (broken) | After (fixed) |
|-------|-----------------|---------------|
| **PHP** | `strtotime()` + `date_i18n( ..., current_time('mysql') )` | `snks_diff_seconds()` / `snks_get_session_start_timestamp()` using `wp_timezone()` |
| **JavaScript** | `new Date(data-datetime)` (browser-dependent parsing) | `data-start-ts` Unix timestamp from PHP, compared with `Date.now()` |

The fix introduces a **single server-side timestamp** (`data-start-ts`) and reuses existing WordPress timezone helpers so PHP buttons, timers, and meeting-room guards stay aligned.

---

## Observed bug

**Scenario:** Device clock shows **5:00 PM**. Two online sessions today: **5:00 PM** and **6:00 PM**.

| Session | Expected at 5:00 PM | Actual (before fix) |
|---------|---------------------|---------------------|
| 5:00 PM | “حان موعد الجلسة” + enabled “ابدأ الجلسة” | Button: “الجلسة لم تبدأ بعد” **and** timer: “تجاوزت موعد الجلسة” |
| 6:00 PM | Countdown or “not started yet” | “حان موعد الجلسة” + “ابدأ الجلسة” (as if already due) |

This looked like a **~1 hour skew** between PHP server logic and JavaScript client logic.

---

## Root cause

### 1. PHP: inconsistent timezone handling

Several places used:

```php
$scheduled_timestamp = strtotime( $record->date_time );
$current_timestamp   = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
$is_too_early        = ( $current_timestamp - $scheduled_timestamp ) < 0;
```

Problems:

- `strtotime( $record->date_time )` uses PHP’s default timezone, not necessarily **Settings → General → Timezone** in WordPress.
- `date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) )` passes a **MySQL datetime string** where a **Unix timestamp** is expected; behavior is unreliable.
- `current_time( 'mysql' )` is already in the site timezone; wrapping it again does not produce a trustworthy comparison.

### 2. JavaScript: ambiguous date string parsing

The countdown in `my-booking.php` used:

```javascript
var startDate = new Date( parent.data('datetime') );
var countDownDate = startDate.getTime();
```

`data-datetime` holds values like `2026-05-19 17:00:00` or `2026-05-19 5:00 pm`. Browsers parse these **inconsistently** (local vs invalid vs offset), often producing roughly a **one-hour error** relative to PHP.

### 3. Split brain: PHP vs JS on the same card

For AI sessions before start time:

- **PHP** (`template_str_replace`) sets the side button to “الجلسة لم تبدأ بعد” and keeps the timer placeholder.
- **JS** (`initializeSnksTimer`) overwrites `.snks-apointment-timer` every second.

When the two clocks disagreed, users saw **contradictory messages on one card**.

---

## Architecture after fix

```
┌─────────────────────────────────────────────────────────────┐
│  WordPress timezone (wp_timezone / current_datetime)        │
└──────────────────────────┬──────────────────────────────────┘
                           │
         ┌─────────────────┴─────────────────┐
         ▼                                   ▼
 snks_get_session_start_timestamp()    snks_diff_seconds()
         │                                   │
         │                                   ├── PHP: too early? disable button
         │                                   ├── PHP: patient template rules
         │                                   └── PHP: meeting room guard
         │
         └── data-start-ts on .snks-booking-item
                           │
                           ▼
              initializeSnksTimer() (my-booking.php)
              countDownDate = startTs * 1000
              compare with Date.now()
```

**Rule:** Any “is it time yet?” check for bookings should use `snks_diff_seconds()` or `snks_get_session_start_timestamp()`, not raw `strtotime()` on `date_time`.

---

## Files changed

| File | Role |
|------|------|
| `functions/helpers.php` | New `snks_get_session_start_timestamp()`; `snks_diff_seconds()` refactored |
| `functions/helpers/render.php` | Booking HTML `data-start-ts`; doctor/patient template timing |
| `functions/scripts/my-booking.php` | Countdown JS + meeting room early-join checks |

---

## File-by-file reference

### `functions/helpers.php`

#### Problem

`snks_diff_seconds()` existed and used `DateTime` + `wp_timezone()` correctly, but **booking render and meeting scripts did not use it**. Other code paths duplicated broken `strtotime` logic instead.

#### Fix

| Symbol | Lines (approx.) | Change |
|--------|-----------------|--------|
| `snks_get_session_start_timestamp( $session )` | ~667–686 | **New.** Returns Unix timestamp for `date_time` in `wp_timezone()`. Accepts session object or date string. |
| `snks_diff_seconds( $session )` | ~694–696 | **Refactored** to `snks_get_session_start_timestamp() - current_datetime()->getTimestamp()`. Positive = future, zero = now, negative = past. |

#### Usage elsewhere (already correct, no change required)

- `functions/actions/edit-appointment.php` — edit-window checks  
- `functions/helpers/render.php` — `snks_render_sessions_listing()` edit eligibility (~1812)

---

### `functions/helpers/render.php`

#### 1. `snks_booking_item_template( $record )` — ~line 872

**Problem:** Only `data-datetime` (unparsed string) was exposed to JavaScript.

**Fix:** Added server-computed attribute:

```html
data-start-ts="<?php echo esc_attr( snks_get_session_start_timestamp( $record ) ); ?>"
```

Kept `data-datetime` for backward compatibility / debugging.

---

#### 2. `template_str_replace( $record )` — ~lines 1013–1032

**Problem:** AI “too early” flag used broken `strtotime` / `date_i18n` comparison.

**Before:**

```php
$scheduled_timestamp = strtotime( $record->date_time );
$current_timestamp   = strtotime( date_i18n( 'Y-m-d H:i:s', current_time( 'mysql' ) ) );
$is_too_early        = ( $current_timestamp - $scheduled_timestamp ) < 0;
```

**After:**

```php
$is_too_early = snks_diff_seconds( $record ) > 0;
```

**Behavior:**

- `$is_too_early === true` → disabled button, spinner text “الجلسة لم تبدأ بعد”, `snks-disabled`, timer row kept.
- `$is_too_early === false` (includes **exactly** at start time) → normal “ابدأ الجلسة”, timer row removed from template (JS still runs on `.snks-count-down` where present).

---

#### 3. `patient_template_str_replace( $record, ... )` — ~lines 1150–1180

**Problem:** Same broken early-time logic for AI sessions; non-AI rules used the same unreliable timestamps.

**After:**

```php
$is_too_early          = snks_diff_seconds( $record ) > 0;
$current_timestamp     = current_datetime()->getTimestamp();
$scheduled_timestamp   = snks_get_session_start_timestamp( $record );
```

Non-AI disable rules (15 minutes after start, before start, cancelled) now use timezone-safe timestamps.

---

#### 4. `snks_render_sessions_listing( $tense )` — ~line 1848

**Problem:** Patient session list wrapper had `data-datetime` only; countdown JS could mis-parse.

**Fix:** Added `data-start-ts` on the patient booking `<div>` wrapper (same as doctor booking cards).

---

#### 5. `snks_doctor_actions( $session )` — ~lines 1727–1740

**Not changed in this fix** — already used `DateTime` + `get_option( 'timezone_string' )` + `current_time( 'timestamp' )`. Treated as the **reference implementation** that the rest of the stack was aligned to.

---

### `functions/scripts/my-booking.php`

#### 1. Meeting room — `meeting_room` shortcode footer — ~lines 49–51

**Problem:** AI early-join block used `strtotime` / `date_i18n`.

**After:**

```php
$is_too_early = snks_diff_seconds( $session ) > 0;
```

---

#### 2. Meeting room HTML loader — ~lines 312–315

**Problem:** Duplicate early-check for patient waiting screen.

**After:** Same `snks_diff_seconds( $session ) > 0` pattern.

---

#### 3. `initializeSnksTimer()` — `wp_footer` — ~lines 334–428

**Problem:** Parsed `data-datetime` with `new Date(dateTime)`.

**After:**

```javascript
var startTs = parseInt( parent.attr( 'data-start-ts' ), 10 );
if ( isNaN( startTs ) || startTs <= 0 ) {
    // Fallback only when attribute missing (cached/old HTML)
    var normalizedDateTime = String( parent.data( 'datetime' ) ).replace( ' ', 'T' );
    startTs = new Date( normalizedDateTime ).getTime() / 1000;
}
var countDownDate  = startTs * 1000;
var sessionEndDate = countDownDate + ( period * 60 * 1000 );
```

**Timer state machine (unchanged logic, fixed inputs):**

| Condition | Timer text | Button |
|-----------|------------|--------|
| `countDownDate > now` | Countdown (days/hours/minutes/seconds) | Unchanged until start |
| `now <= sessionEndDate` | “حان موعد الجلسة” | Enable “إبدأ الجلسة”, set meeting URL |
| `now > sessionEndDate` | “تجاوزت موعد الجلسة” | Disable for non-AI (`snks-disabled`) |

Triggered on:

- `jet-popup/show-event/after-show`
- `jet-popup/render-content/render-custom-content`

---

## UI strings affected

| Arabic | English meaning | Set by |
|--------|-----------------|--------|
| الجلسة لم تبدأ بعد | Session has not started yet | PHP (`template_str_replace`, `patient_template_str_replace`) |
| حان موعد الجلسة | Session time has arrived | JS timer |
| تجاوزت موعد الجلسة | Session time has passed | JS timer |
| ابدأ الجلسة / إبدأ الجلسة | Start session | PHP default button; JS may rewrite at start |

---

## Testing checklist

Use a doctor account with at least two **online** bookings today (e.g. 5:00 PM and 6:00 PM). Hard-refresh or bypass cache so `data-start-ts` is present in HTML.

### At exactly session start (e.g. 5:00 PM for 5:00 PM slot)

- [ ] Timer shows **“حان موعد الجلسة”** (not “تجاوزت موعد الجلسة”).
- [ ] Side button shows **“ابدأ الجلسة”** and is clickable (not “الجلسة لم تبدأ بعد”).
- [ ] No contradictory messages on the same card.

### One hour before second session (e.g. 5:00 PM for 6:00 PM slot)

- [ ] Timer shows **countdown** or early state — **not** “حان موعد الجلسة”.
- [ ] Start button not active for future slot.

### After session end (start + period, default 45 min)

- [ ] Timer shows **“تجاوزت موعد الجلسة”**.
- [ ] Non-AI: card gets `snks-disabled`.
- [ ] AI: may stay enabled per existing AI rules.

### Meeting room (AI, patient)

- [ ] Before start: waiting message with “الجلسة لم تبدأ بعد…”.
- [ ] At/after start (if doctor joined): room loads.

### Verify HTML

Inspect booking card:

```html
<div class="snks-booking-item" data-datetime="..." data-start-ts="1716134400" data-period="45">
```

`data-start-ts` must be a positive integer (Unix seconds).

### WordPress settings

Confirm **Settings → General → Timezone** matches the region sessions are booked in (e.g. `Africa/Cairo`). All fixed PHP paths respect this setting.

---

## Related code (unchanged but relevant)

| Location | Notes |
|----------|--------|
| `functions/helpers/render.php` → `snks_doctor_actions()` | Session end for “تحديد كمكتملة” uses correct `DateTime` timezone |
| `functions/helpers/timetable-helpers.php` → `snks_insert_timetable()` | Stores `date_time` as `Y-m-d H:i:s` on insert |
| `functions/crons/sms.php` | Comment notes local time alignment for `date_time` |
| `jalsah-ai-frontend/` | Separate Vue app; **not** part of this fix (WordPress shortcode bookings only) |

### Known remaining `strtotime( $session->date_time )` usages

Some older helpers (e.g. `snks_validate_absence_15_minute_rule()` in `functions/helpers.php`) still use raw `strtotime`. Consider migrating them to `snks_get_session_start_timestamp()` in a follow-up if absence rules show timing drift.

---

## Future guidelines

1. **Do not** compare booking times with `strtotime( $record->date_time )` alone.
2. **Do not** pass `current_time( 'mysql' )` into `date_i18n` as if it were a timestamp.
3. **Do** use `snks_diff_seconds( $record )` for relative checks (`> 0` = too early, `<= 0` = started or past).
4. **Do** expose `data-start-ts` whenever JavaScript needs session timing.
5. **Do** use `current_datetime()` / `wp_timezone()` for “now” in PHP.
6. When adding new booking UIs, copy the pattern from `snks_doctor_actions()` or `snks_get_session_start_timestamp()`.

---

## Quick reference: helper API

```php
// Unix timestamp of session start in WP timezone
$start = snks_get_session_start_timestamp( $record );

// Unix timestamp of session end (start + period)
$end = snks_get_session_end_timestamp( $record );

// Seconds until start (negative = already started)
$diff = snks_diff_seconds( $record );

$is_too_early = $diff > 0;
$has_started  = $diff <= 0;
```

---

## Post-fix audit (2026-05-19)

### Booking card flow — correct after follow-up fixes

| Check | Status |
|-------|--------|
| PHP early/late via `snks_diff_seconds()` | OK |
| JS countdown via `data-start-ts` | OK |
| JS session end via `data-end-ts` | OK (added in audit) |
| “تحديد كمكتملة” end time | OK — `snks_doctor_actions()` now uses `snks_get_session_end_timestamp()` |
| Timer runs on `my-bookings` page load | OK — `DOMContentLoaded` + Jet popup |
| Duplicate timer intervals | OK — `snksTimerRunning` guard |
| Mixed `date_time` DB formats (`H:i:s` / `h:i a`) | OK — `createFromFormat` loop in helper |

### Intentionally out of scope (not 100% unified yet)

These still use raw `strtotime()` and are **not** part of the live booking-card timer path:

- `snks_can_modify_appointment()`, `snks_get_appointment_time_remaining()` — `functions/helpers.php`
- `snks_validate_absence_15_minute_rule()` — `functions/helpers.php`
- SMS cron — `functions/crons/sms.php`
- AI integration slot filters — `functions/ai-integration.php`

Migrate those only if you see wrong behavior in those specific features.

### Prerequisites for correct behavior

1. **WordPress timezone** under Settings → General must match how sessions are booked.
2. **Hard refresh** after deploy so HTML includes `data-start-ts` / `data-end-ts`.
3. User device clock should be accurate (`Date.now()` is used for live countdown).

---

## Optimal timing API (v1.2)

All session timing should go through these helpers in `functions/helpers.php`:

| Function | Purpose |
|----------|---------|
| `snks_get_current_timestamp()` | Site "now" (WordPress timezone) |
| `snks_get_session_start_timestamp( $session )` | Start instant (parses `H:i:s` and `h:i a`) |
| `snks_get_session_end_timestamp( $session )` | End instant (start + `period`) |
| `snks_get_session_timing( $session )` | Full state array (start, end, now, flags) |
| `snks_diff_seconds( $session )` | Seconds until start (+ = future) |
| `snks_is_session_too_early( $session )` | Before start |
| `snks_is_session_started( $session )` | Start time reached |
| `snks_is_session_active( $session )` | Between start and end |
| `snks_is_session_ended( $session )` | After end |
| `snks_session_timing_data_attrs( $session )` | HTML `data-*` for booking cards |

### JavaScript (server clock sync)

Loaded from `my-booking.php` (and bootstrapped in `scripts.php` if missing):

- `window.snksServerClock` — anchor at page load  
- `window.snksNowMs()` / `window.snksNowUnix()` — now aligned to server, with client drift compensation  

Used by booking timers and “تحديد كمكتملة” enable logic.

### Migrated in v1.2

- Appointment modify/cancel 24h rules (`snks_can_modify_appointment`, AI edit helpers)  
- 15-minute absence rule  
- SMS cron `time_diff`  
- `snks_is_past_date()` (start passed, not “ended”)  
- Removed duplicate strtotime fallbacks in `ai-integration.php`  

---

## Caller alignment checklist (v1.3)

Every changed function and its required usage:

| Function | Meaning | Use when |
|----------|---------|----------|
| `snks_is_session_too_early()` | `now < start` | Disable join / show “لم تبدأ بعد” |
| `snks_is_session_started()` | `now >= start` | Slot no longer in future (`snks_is_past_date`) |
| `snks_is_session_active()` | `start <= now < end` | Session in progress |
| `snks_is_session_ended()` | `now >= end` | After period; enable “تحديد كمكتملة” |
| `snks_diff_seconds()` | `start - now` (+ = future) | Countdowns, 24h edit rules, SMS windows |
| `snks_get_session_timing()` | All flags in one call | Templates needing multiple checks |
| `snks_format_session_datetime()` | Display labels | Never use `strtotime` for logic |
| `snks_session_timing_data_attrs()` | HTML `data-*` | Booking card markup only |

### Aligned call sites (v1.3)

- `template_str_replace()` / `patient_template_str_replace()` — `snks_get_session_timing()`
- `snks_doctor_actions()` — `snks_get_session_timing()['is_ended']`
- `snks_render_sessions_listing()` — `snks_format_session_datetime()`, `snks_diff_seconds()`
- `snks_get_time_difference()` — `snks_diff_seconds()` (was `createFromFormat` only)
- `snks_is_past_date()` — alias of `snks_is_session_started()`
- `snks_can_modify_appointment()` / AI edit helpers — `snks_diff_seconds()` + `DAY_IN_SECONDS`
- `snks_validate_absence_15_minute_rule()` — `snks_get_session_start_timestamp()`
- `snks_send_session_notifications()` — `snks_diff_seconds()`; 1h window requires `$time_diff > 0`
- `create-appointment` cancel — `snks_is_past_date()` (= started)
- `settings.php` slot filter — `! snks_is_past_date()` (= future slots)

### Argument types supported

All timing helpers accept: **timetable object**, **array with `date_time`**, or **raw `date_time` string**.

---

*Document version: 1.3 — full caller alignment + parser hardening*
