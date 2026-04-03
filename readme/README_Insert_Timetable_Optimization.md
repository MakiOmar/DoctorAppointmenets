# Planned Optimization: `wp_ajax_insert_timetable`

> This document is for planning purposes only. No code changes described here have been applied yet.

## Scope

This note covers the AJAX handler in:

- `functions/ajax/settings-ajax.php`

And the helper functions it depends on, mainly:

- `functions/helpers/timetable-helpers.php`
- `functions/helpers/settings.php`

## Goal

Make `wp_ajax_insert_timetable` safer, easier to reason about, and less likely to create partial or inconsistent timetable data.

## Current Flow

The current handler roughly does this:

1. Checks doctor/clinic manager/admin capability.
2. Reads `$_POST`.
3. Verifies nonce only if the nonce field exists.
4. Loads preview timetable data.
5. Deletes all waiting sessions for the selected doctor.
6. Loops through preview rows one by one.
7. Skips rows if an ordered timetable exists for the same `date_time`.
8. Inserts rows if an exact match does not exist.
9. Sometimes inserts again when an existing row has a non-`open` status.
10. Always returns success with an empty `errors` array.

## Main Problems

### 1. Nonce validation is optional

Current logic only rejects the request when a nonce is present and invalid. A request with no nonce at all can still proceed.

### 2. Destructive delete happens too early

`snks_delete_waiting_sessions_by_user_id( $user_id )` runs before the insert plan is validated and before success is confirmed. If the insert loop fails halfway, the user can lose waiting slots and still receive a success response.

### 3. Mismatched comparison in duplicate logic

The current condition compares:

- `$data['attendance_type']`
- `$timetable->session_status`

Those fields represent different concepts. `attendance_type` is usually `online` or `offline`, while `session_status` is usually `waiting`, `open`, `closed`, `completed`, etc. This makes the condition unreliable.

### 4. Insert rules are split across multiple places

Some duplicate prevention happens in the AJAX handler, and more duplicate prevention happens again inside `snks_insert_timetable()`. That makes the final behavior harder to predict and harder to maintain.

### 5. No meaningful error collection

The `$errors` array is initialized but never populated. The handler always returns:

```php
array(
	'resp'   => true,
	'errors' => $errors,
)
```

Even if inserts fail.

### 6. No protection against partial success

The handler can end up in a mixed state:

- some waiting rows deleted
- some preview rows inserted
- some skipped
- no accurate result returned to the client

### 7. Business rules are not clearly expressed

Right now the code mixes multiple concerns in one loop:

- duplicate detection
- preserving booked/open rows
- allowing some reinsert cases
- handling attendance type
- deciding whether a slot should be restored

That makes the intended booking rules difficult to verify.

## Suggested Optimization

## Preferred Direction

Refactor the handler into a small coordinator that delegates the real sync rules to a dedicated helper.

Suggested shape:

1. Authenticate and require a valid nonce.
2. Load preview timetable rows for the target doctor.
3. Normalize all rows first.
4. Validate all rows first.
5. Build an insertion/sync plan before deleting anything.
6. Delete only the waiting rows that should actually be replaced.
7. Insert only rows that are safe and necessary.
8. Return a real result summary.

## Suggested New Helper

Create a dedicated function with one clear responsibility, for example:

```php
snks_sync_preview_timetables_to_db( $user_id, $preview_timetables )
```

Suggested return shape:

```php
array(
	'success'         => true,
	'inserted'        => array(),
	'skipped'         => array(),
	'preserved'       => array(),
	'deleted_waiting' => array(),
	'errors'          => array(),
)
```

This makes the AJAX response honest and much easier to debug.

## Recommended Logic Rules

### Rule 1: Require nonce strictly

Use a strict nonce check such as `check_ajax_referer()` or an equivalent explicit `isset + wp_verify_nonce` combination.

### Rule 2: Normalize row data once

Before any DB writes:

- normalize `date_time`
- ensure `user_id` is correct
- remove preview-only fields like `date`
- sanitize key fields consistently

### Rule 3: Separate "exists" checks by meaning

Use clearly named checks for different cases:

- exact same slot already exists
- slot already has an order
- slot exists but is `open`
- slot exists but is `closed`
- slot exists for another attendance type

Avoid mixing these rules in one `if` statement.

### Rule 4: Do not compare unrelated fields

Never compare `attendance_type` with `session_status`.

If the intent is "same slot but different attendance type", compare against `attendance_type`.

If the intent is "restore only when not open/booked", compare against `session_status` and `order_id`.

### Rule 5: Delete waiting rows only after a valid plan exists

Instead of deleting all waiting rows for the doctor up front, prefer one of these approaches:

- delete only the waiting rows that correspond to the preview dataset being synced
- or build the insert plan first, then delete only rows proven to be safe to replace

This reduces the blast radius of failures.

### Rule 6: Preserve booked/open rows explicitly

If a slot already has an order, or the slot is intentionally open/booked, preserve it and mark it as skipped in the result.

This should be a documented rule, not an accidental side effect.

### Rule 7: Return real error data

If any row fails, include:

- the slot payload
- the reason
- whether the failure happened before or after delete

The AJAX response should not claim success if the sync partially failed.

### Rule 8: Consider transaction-style safety

If practical for this plugin and database usage, wrap the delete/insert sync in a DB transaction. If transactions are not feasible here, at least structure the code to minimize partial writes.

## Recommended Refactor Structure

### AJAX handler responsibilities

- capability check
- nonce check
- fetch preview data
- call sync helper
- return JSON response

### Helper responsibilities

- normalize preview rows
- inspect existing DB rows
- decide insert/skip/preserve/delete behavior
- execute writes
- collect result details

This split will make the handler much easier to test.

## Minimum Safe Improvement

If a full refactor is not possible yet, the minimum safe improvement should be:

1. Require nonce strictly.
2. Stop comparing `attendance_type` to `session_status`.
3. Build and return real `$errors`.
4. Do not delete all waiting rows before confirming what will be reinserted.

## Acceptance Criteria

The optimized implementation should satisfy all of these:

- A request without nonce is rejected.
- A failed sync does not silently report success.
- Waiting rows are not broadly deleted before the insert plan is known.
- Ordered/booked rows are preserved.
- Duplicate rows are not inserted.
- Attendance type logic is explicit and correct.
- Response payload includes useful counts and errors.

## Suggested Test Checklist

- Insert preview rows when no timetable rows exist yet.
- Re-run the same request and confirm duplicates are not created.
- Keep slots with `order_id > 0` untouched.
- Keep `open` rows untouched when required by business rules.
- Confirm `closed` rows are only restored when explicitly intended.
- Confirm online/offline rows are handled independently when needed.
- Confirm missing nonce fails.
- Confirm partial DB failures are visible in the response.

## Notes

There is already an existing helper, `snks_insert_preview_timetables()`, that is structurally closer to the behavior we want. A future refactor should either reuse it or replace both code paths with one shared sync implementation, so the timetable rules live in one place only.
