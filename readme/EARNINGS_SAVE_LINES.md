# Exact Lines Where Earnings Are Saved to Database

## Summary: The Core Database Insert Function

**File:** `functions/crons/transactions.php`
- **Line 123-151:** `snks_add_transaction()` - **THIS IS THE MAIN FUNCTION THAT SAVES TO DATABASE**
- **Line 127-143:** `$wpdb->insert()` - **THIS IS THE ACTUAL DATABASE INSERT**

This function inserts into `snks_booking_transactions` table.

---

## 1. Regular Orders - Earnings Saved When Order Status = Completed

**File:** `functions/actions/create-appointment.php`

### Trigger Point:
- **Line 10-22:** Hook: `woocommerce_order_status_changed` → calls `snks_woocommerce_payment_complete_action()`

### Actual Save Line:
- **Line 90:** `snks_add_transaction( $timetable->user_id, $timetable->ID, 'add', $doctor_earning );`
  - This calls the database insert function at `functions/crons/transactions.php:127`

**Full Context (lines 77-92):**
```php
if ( $updated ) {
    $current_hour   = current_time( 'H' );
    $doctor_earning = $order->get_meta( '_main_price', true );
    
    if ( $current_hour >= 0 && $current_hour < 9 ) {
        // Save to temp wallet
    } else {
        snks_wallet_credit( $timetable->user_id, $doctor_earning, 'الدخل مقابل حجز موعد' );
    }
    
    snks_add_transaction( $timetable->user_id, $timetable->ID, 'add', $doctor_earning ); // ← LINE 90: SAVES EARNINGS
    snks_log_transaction( $timetable->user_id, $doctor_earning, 'add' );
}
```

---

## 2. AI Sessions - Earnings Saved When Session Status = Completed

### A. Via Manual Status Change (Admin Panel)

**File:** `functions/helpers/timetable-helpers.php`
- **Line 533:** Function `snks_update_timetable()` - Called when status is manually changed
- **Line 597-598:** Calls `snks_execute_ai_profit_transfer()` or `snks_create_ai_earnings_from_timetable()`
  - Which eventually calls → `functions/helpers/profit-calculator.php:106`

### B. Via AJAX Handler `end_session`

**File:** `functions/ajax/timetable-ajax.php`
- **Line 118-121:** Calls `snks_execute_ai_profit_transfer()` or `snks_create_ai_earnings_from_timetable()`
  - Which eventually calls → `functions/helpers/profit-calculator.php:106`

### C. Via AJAX Handler `snks_handle_session_doctor_actions`

**File:** `functions/ajax/timetable-ajax.php`
- **Line 648:** Calls `snks_execute_ai_profit_transfer()`
- **Line 656:** Or calls `snks_create_ai_earnings_from_timetable()`
  - Which eventually calls → `functions/helpers/profit-calculator.php:106`

### D. Via API Endpoint `end_ai_session`

**File:** `functions/ai-integration.php`
- **Line 7122:** Calls `snks_execute_ai_profit_transfer()`
- **Line 7139:** Or calls `snks_create_ai_earnings_from_timetable()`
  - Which eventually calls → `functions/helpers/profit-calculator.php:106`

---

## 3. The AI Earnings Save Chain

**File:** `functions/helpers/profit-calculator.php`

### Step 1: Entry Points
- **Line 232:** `snks_create_ai_earnings_from_timetable()` calls:
  - `snks_add_ai_session_transaction( $therapist_id, $session_data, $profit_amount )`
  
- **Line 368:** `snks_execute_ai_profit_transfer()` calls:
  - `snks_add_ai_session_transaction( $therapist_id, $session_data_for_transaction, $profit_amount )`

### Step 2: The Wrapper Function
- **Line 101:** `snks_add_ai_session_transaction()` function
- **Line 106:** `$transaction_id = snks_add_transaction( $therapist_id, 0, 'add', $profit_amount );`
  - **← THIS CALLS THE DATABASE INSERT**
  - **Line 129-135:** Updates transaction metadata (ai_session_id, ai_order_id, etc.)

### Step 3: Database Insert (Same for All)
- **File:** `functions/crons/transactions.php`
- **Line 106** (from profit-calculator.php) → **Line 123** (snks_add_transaction function)
- **Line 127-143:** `$wpdb->insert()` - **THE ACTUAL DATABASE WRITE**

---

## Complete Call Chain for AI Sessions

```
Session Status → 'completed'
    ↓
snks_update_timetable() [timetable-helpers.php:533]
    OR
end_session AJAX [timetable-ajax.php:80]
    OR  
snks_handle_session_doctor_actions() [timetable-ajax.php:610]
    OR
end_ai_session API [ai-integration.php:7083]
    ↓
snks_create_ai_earnings_from_timetable() [profit-calculator.php:232]
    OR
snks_execute_ai_profit_transfer() [profit-calculator.php:368]
    ↓
snks_add_ai_session_transaction() [profit-calculator.php:106]
    ↓
snks_add_transaction() [transactions.php:123]
    ↓
$wpdb->insert() [transactions.php:127] ← **DATABASE SAVE HAPPENS HERE**
```

---

## Quick Reference: All Lines That Save Earnings

| Type | File | Line | Function |
|------|------|------|----------|
| **Regular Orders** | `functions/actions/create-appointment.php` | **90** | `snks_add_transaction()` call |
| **AI Sessions** | `functions/helpers/profit-calculator.php` | **106** | `snks_add_transaction()` call |
| **Database Insert** | `functions/crons/transactions.php` | **127** | `$wpdb->insert()` - **ACTUAL SAVE** |
| **AI Metadata Update** | `functions/helpers/profit-calculator.php` | **129** | `$wpdb->update()` - Updates AI session metadata |

---

## Important Notes

1. **Regular Orders:** Earnings are saved **immediately** when order status changes to 'completed'
2. **AI Sessions:** Earnings are saved when **session status** changes to 'completed' (NOT when order is completed)
3. **All earnings go through:** `snks_add_transaction()` → `$wpdb->insert()` at `transactions.php:127`
4. **AI sessions get additional metadata** updated at `profit-calculator.php:129-135`
