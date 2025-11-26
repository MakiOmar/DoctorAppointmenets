# Earnings Code Locations

## Summary: Earnings are NOT created when order is completed for AI orders

For **AI orders**, earnings are created when:
- ✅ Session status changes to `'completed'` (via `snks_update_timetable()`)
- ✅ Session is ended via AJAX (`end_session` handler)
- ✅ Session is ended via API (`end_ai_session` endpoint)

For **Regular orders**, earnings ARE created immediately when:
- ✅ Order status changes to `'completed'` or `'processing'`
- ✅ Location: `functions/actions/create-appointment.php` lines 77-92

## Key Files

### 1. Order Completion Hooks
**File:** `functions/actions/create-appointment.php`
- Lines 10-22: Hooks for `woocommerce_order_status_changed` and `woocommerce_payment_complete`
- Line 31: `snks_woocommerce_payment_complete_action()` - Main handler
  - For AI orders: Calls `snks_process_ai_order_completion()` (NO earnings yet)
  - For regular orders: Creates earnings immediately (lines 77-92)

### 2. AI Order Processing
**File:** `functions/ai-integration.php`
- Lines 7871-7872: Additional hooks for AI order completion
- Line 7907: `snks_handle_ai_order_completion()` - Tries earnings if session_id exists
- Line 7939: `snks_process_ai_order_completion()` - Connects slots to order (NO earnings)

### 3. Earnings Creation (AI Sessions)
**File:** `functions/helpers/timetable-helpers.php`
- Line 533: `snks_update_timetable()` - Creates earnings when status → 'completed' for AI sessions
  - **This is the main entry point for manual status changes**

**File:** `functions/ajax/timetable-ajax.php`
- Line 80: `end_session` AJAX handler - Creates earnings when session completed
- Line 610: `snks_handle_session_doctor_actions()` - Creates earnings for doctor actions

**File:** `functions/ai-integration.php`
- Line 7083: `end_ai_session` API endpoint - Creates earnings via REST API

### 4. Earnings Helper Functions
**File:** `functions/helpers/profit-calculator.php`
- Line 101: `snks_add_ai_session_transaction()` - Adds transaction to database
- Line 152: `snks_create_ai_earnings_from_timetable()` - Creates earnings from timetable data
- Line 318: `snks_execute_ai_profit_transfer()` - Executes profit transfer

## Important Note

⚠️ **AI orders do NOT create earnings when the order is completed.**
Earnings are only created when the **session status** changes to `'completed'`.

This is why earnings may not appear until you manually change the session status to 'completed' in the timetable.
