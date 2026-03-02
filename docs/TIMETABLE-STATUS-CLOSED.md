# Timetable Status: When It Is Set to "Closed"

This document describes all cases in the Doctor Appointments plugin where a timetable slot’s `session_status` is set to **`closed`**.

---

## Overview

A timetable row is **closed** when the slot is no longer available for booking. Closed slots are excluded from the normal “available” list when `show_closed` is false (e.g. on the booking calendar), but can still be shown for doctor views or reporting.

---

## 1. Timetable Generation (Off Days)

**File:** `functions/helpers/settings.php`  
**Function:** `snks_generate_timetable()`

When the timetable is generated for a date range, each slot is created with an initial status:

- **`waiting`** – date is a normal working day (slot is available to be booked).
- **`closed`** – date is an **off day** for the doctor.

Off days come from the doctor’s `off_days` user meta (comma‑separated dates), via `snks_get_off_days( $user_id )`. Any slot whose date is in that list is created with `session_status = 'closed'` so it is never offered for booking.

```php
// functions/helpers/settings.php (around line 888)
'session_status' => in_array( $date, $off_days, true ) ? 'closed' : 'waiting',
```

---

## 2. Overlapping Slots When One Is Booked (`snks_close_others`)

**File:** `functions/helpers/timetable-helpers.php`  
**Function:** `snks_close_others( $booked_session )`

When a slot is **booked** (or reserved), other slots that overlap the same time but are still **unbooked** (`order_id = 0`) and in status **`waiting`** are set to **`closed`**. This avoids double‑booking the same time.

Logic depends on the **period** of the booked slot:

### 2.1 Period = 30 minutes

- Same **date** and **base_hour** as the booked session.
- `order_id = 0` and `session_status = 'waiting'`.
- **Excluded:** the exact 30‑minute slot that was booked (same `starts`/`ends` and `period = 30`).
- All other waiting slots in that base hour are set to **`closed`**.

### 2.2 Period = 45 or 60 minutes

- Same **date** and **base_hour** as the booked session.
- `order_id = 0` and `session_status = 'waiting'`.
- All such slots are set to **`closed`** (the whole base hour is considered taken).

**Where `snks_close_others` is used (and thus where these slots become `closed`):**

| File | Context |
|------|--------|
| `functions/actions/consulting-checkout.php` | After setting the chosen slot to `pending` (checkout started). |
| `functions/actions/consulting-checkout.php` | On `woocommerce_thankyou` when order is completed/processing (slot linked to order, others closed). |
| `functions/actions/edit-appointment.php` | When the **old** slot is updated during an edit and its new status is `closed` (overlap case below). |
| `functions/actions/edit-appointment.php` | After moving the booking to the **new** slot (new slot set to `open`, others in that time closed). |
| `functions/actions/edit-appointment.php` | When the user only reserves a new slot (e.g. pending edit) – the new slot is set to `pending` and others are closed. |

So: any time a slot is “taken” (booked, pending, or opened after edit), overlapping unbooked `waiting` slots are set to **`closed`** via `snks_close_others`.

---

## 3. Edit Appointment: Old Slot Set to `closed` (Overlap on Same Day)

**File:** `functions/actions/edit-appointment.php`  
**Function:** `snks_apply_booking_edit()`

When a booking is **moved** to another slot:

1. The previous slot is cleared (`client_id = 0`, `order_id = 0`).
2. The **new** slot is fetched. If the new slot is on the **same day** as the old one and its start time falls **inside** the old slot’s time range (old start &lt; new start &lt; old end), the **old** slot is set to **`closed`** instead of **`waiting`**. That prevents re‑booking a slot that still overlaps the new appointment time.
3. The old slot is updated with that status (`waiting` or `closed`).
4. If the status is **`closed`**, `snks_close_others( $booking )` is called so other overlapping unbooked slots are closed as well.

So in this flow, a timetable row is set to **`closed`** when:

- It is the **previous** slot of an edited booking, and  
- The **new** slot is on the same day and overlaps the old slot’s time.

```php
// functions/actions/edit-appointment.php (around lines 43–50)
$status = 'waiting';
if ( $new_date === $prev_date ) {
    // ... time comparison ...
    if ( $prev_date_starts < $new_date_starts && $prev_date_ends > $new_date_starts ) {
        $status = 'closed';
    }
}
```

---

## Summary Table

| Case | Where | Condition |
|------|--------|-----------|
| **Off days** | `snks_generate_timetable()` in `settings.php` | Slot’s date is in the doctor’s `off_days` list. |
| **Overlap when a slot is taken** | `snks_close_others()` in `timetable-helpers.php` | Same date and base hour, unbooked (`order_id = 0`), `session_status = 'waiting'`; called from checkout, thankyou, and edit flows. |
| **Old slot after edit** | `snks_apply_booking_edit()` in `edit-appointment.php` | Old slot is cleared and the new slot is on the same day and overlaps the old slot’s time → old slot set to `closed`. |

---

## Related: When “Closed” Slots Are Shown or Hidden

- **`snks_get_timetable_by_date()`** (`timetable-helpers.php`):
  - `$show_closed = true`: returns slots that are **not** `cancelled`, `completed`, `open`, or `pending` (so **`closed`** and **`waiting`** are included).
  - `$show_closed = false`: also excludes **`closed`**, so only non‑closed available slots (e.g. `waiting`) are returned.

This controls whether closed slots appear on the calendar (e.g. for doctors) or are hidden (e.g. for patients).


### 1.1 Changing `off_days` After Slots Were Closed

**Key idea:** `off_days` control how new timetables are generated and which dates are filtered out in queries, but they do **not** automatically reopen existing rows in the database.

- When you **add** a date to `off_days` and then generate/apply the timetable, any newly generated slots for that date get `session_status = 'closed'`.
- When you **later remove** that date from `off_days`:
  - Existing timetable rows already saved with `session_status = 'closed'` stay closed. There is no background job that scans for those rows and flips them to `waiting`.
  - Fresh calls to `snks_generate_timetable()` use the current `off_days` list. For dates that are no longer off, the generated slots for those dates will now come out as `waiting` instead of `closed`.
  - Several timetable/AI queries add a condition like `DATE(date_time) NOT IN (off_days)` on top of `session_status = 'waiting'`. Once a date is removed from `off_days`, those queries stop excluding that date, but they still only return rows that match their status filter (usually `waiting`).

**Practical effect:**

- Setting a day as off and publishing can create closed slots in the timetable table for that date.
- Removing the same date from `off_days` later does not reopen those closed rows; they remain closed until something explicitly changes them.
- To actually make that day bookable again, you should regenerate and publish the timetable so that new `waiting` slots for that date exist and are picked up by the usual `session_status = 'waiting'` queries.