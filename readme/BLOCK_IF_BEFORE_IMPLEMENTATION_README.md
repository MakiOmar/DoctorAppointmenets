# Block If Before Implementation - Issues & Fixes

## 📋 Overview

This document outlines the current implementation status of `block_if_before_number` functionality across different booking systems and identifies issues that need to be addressed for complete functionality.

## 🎯 What `block_if_before_number` Does

The `block_if_before_number` setting allows doctors to prevent patients from booking appointments too close to the current time. This ensures adequate preparation time and prevents last-minute bookings.

### Configuration
- **Storage:** User meta fields: `block_if_before_number` and `block_if_before_unit`
- **Units:** Can be 'hour' or 'day'
- **Logic:** Adds the specified time period to current time and filters out slots before that time

### Examples
- `block_if_before_number = 2` + `block_if_before_unit = 'hour'` → Block slots within 2 hours
- `block_if_before_number = 1` + `block_if_before_unit = 'day'` → Block slots within 24 hours

## ✅ Current Implementation Status

### 1. Shortcode Booking Form
**Status:** ✅ **PARTIALLY IMPLEMENTED**

#### What Works:
- **Date Level Filtering:** `get_bookable_dates()` correctly applies `block_if_before_number`
- **Database Level:** SQL queries use adjusted datetime to exclude recent dates
- **Logic:** Same calculation as applied to frontend AI system

#### What's Missing:
- **Time Slot Level Filtering:** AJAX function `fetch_start_times_callback()` doesn't apply `block_if_before_number`
- **Same-Day Filtering:** If a date is available, all time slots are shown, even if some are too close

#### Code Location:
```php
// functions/helpers/timetable-helpers.php
function get_bookable_dates( $user_id, $period, $_for = '+1 month', $attendance_type = 'both' ) {
    // ✅ CORRECTLY IMPLEMENTED
    $seconds_before_block = $number * $base * 3600;
    $current_datetime = date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $seconds_before_block ) );
    // SQL query uses $current_datetime
}

// functions/ajax/consulting-form.php
function fetch_start_times_callback() {
    // ❌ MISSING IMPLEMENTATION
    // No block_if_before_number filtering applied
}
```

### 2. Frontend Jalsah AI System
**Status:** ✅ **FULLY IMPLEMENTED**

#### What Works:
- **Date Level Filtering:** All AI API endpoints correctly apply `block_if_before_number`
- **Database Level:** SQL queries use adjusted datetime
- **Consistent Logic:** Same implementation as shortcode form
- **All Endpoints:** Available dates, time slots, and earliest slot calculations

#### Code Location:
```php
// functions/ai-integration.php
private function get_ai_therapist_available_dates( $therapist_id ) {
    // ✅ CORRECTLY IMPLEMENTED
    $seconds_before_block = $number * $base * 3600;
    $adjusted_current_datetime = date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $seconds_before_block ) );
    // SQL query uses $adjusted_current_datetime
}
```

## 🚨 Identified Issues

### Issue 1: Shortcode Form - Incomplete Time Slot Filtering

**Problem:** The shortcode booking form has incomplete `block_if_before_number` implementation.

**Current Behavior:**
- ✅ Dates with slots before the blocking period are filtered out
- ❌ If a date is available, ALL time slots on that date are shown (including those too close to current time)

**Example Scenario:**
- Current Time: 2:00 PM
- Doctor Setting: `block_if_before_number = 2`, `block_if_before_unit = 'hour'`
- Expected: Only slots after 4:00 PM should be shown
- Current: If today has slots at 3:00 PM, 4:00 PM, 5:00 PM, all three are shown

**Affected Files:**
- `functions/ajax/consulting-form.php` - `fetch_start_times_callback()` function
- `functions/helpers/render.php` - `snks_render_consulting_hours()` function

### Issue 2: Inconsistent Implementation

**Problem:** Different booking systems have different levels of `block_if_before_number` support.

**Impact:**
- Shortcode form: Partial implementation
- Frontend AI system: Complete implementation
- Users get different experiences depending on which system they use

## 🔧 Proposed Fixes

### Fix 1: Complete Shortcode Form Implementation

#### Step 1: Update AJAX Function
**File:** `functions/ajax/consulting-form.php`
**Function:** `fetch_start_times_callback()`

**Required Changes:**
```php
function fetch_start_times_callback() {
    // ... existing code ...
    
    // ADD: Get doctor settings and calculate block time
    $doctor_settings = snks_doctor_settings( $user_id );
    $seconds_before_block = 0;
    if ( ! empty( $doctor_settings['block_if_before_number'] ) && ! empty( $doctor_settings['block_if_before_unit'] ) ) {
        $number = $doctor_settings['block_if_before_number'];
        $unit = $doctor_settings['block_if_before_unit'];
        $base = ( 'day' === $unit ) ? 24 : 1;
        $seconds_before_block = $number * $base * 3600;
    }
    
    // ADD: Calculate adjusted current datetime
    $adjusted_current_datetime = date_i18n( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $seconds_before_block ) );
    
    // MODIFY: Update SQL query to use adjusted datetime
    $sql = "SELECT * FROM {$wpdb->prefix}snks_provider_timetable 
            WHERE user_id = %d 
            AND period = %d 
            AND order_id = 0 
            AND session_status = 'waiting' 
            AND DATE(date_time) = %s
            AND date_time >= %s"; // ADD: Time filtering
    
    // ADD: Include adjusted datetime in query parameters
    $query_params[] = $adjusted_current_datetime;
}
```

#### Step 2: Update Rendering Function (Optional)
**File:** `functions/helpers/render.php`
**Function:** `snks_render_consulting_hours()`

**Required Changes:**
```php
function snks_render_consulting_hours( $availables, $_attendance_type, $user_id = false ) {
    // ADD: Post-process filtering for additional safety
    if ( $user_id ) {
        $doctor_settings = snks_doctor_settings( $user_id );
        if ( ! empty( $doctor_settings['block_if_before_number'] ) && ! empty( $doctor_settings['block_if_before_unit'] ) ) {
            $seconds_before_block = $number * $base * 3600;
            $adjusted_time = current_time( 'timestamp' ) + $seconds_before_block;
            
            // Filter out slots that are too close
            $availables = array_filter( $availables, function( $slot ) use ( $adjusted_time ) {
                return strtotime( $slot->date_time ) >= $adjusted_time;
            });
        }
    }
    
    // ... rest of existing code ...
}
```

### Fix 2: Add Time Slot Level Filtering to AI System

#### Current Status:
- ✅ AI system correctly filters at date level
- ❌ AI system doesn't filter individual time slots within a date

#### Required Changes:
**File:** `functions/ai-integration.php`
**Functions:** `get_ai_therapist_availability()`, `get_ai_therapist_time_slots()`

**Implementation:**
```php
private function get_ai_therapist_availability( $therapist_id, $date ) {
    // ... existing code ...
    
    // ADD: Apply block_if_before_number to individual slots
    if ( $date === current_time( 'Y-m-d' ) ) {
        $doctor_settings = snks_doctor_settings( $therapist_id );
        if ( ! empty( $doctor_settings['block_if_before_number'] ) && ! empty( $doctor_settings['block_if_before_unit'] ) ) {
            $seconds_before_block = $number * $base * 3600;
            $adjusted_time = current_time( 'timestamp' ) + $seconds_before_block;
            
            // Filter slots that are too close to current time
            $available_slots = array_filter( $available_slots, function( $slot ) use ( $adjusted_time ) {
                return strtotime( $slot['date_time'] ) >= $adjusted_time;
            });
        }
    }
}
```

## 🧪 Testing Scenarios

### Test Case 1: Hour-Based Blocking
**Setup:**
- Current Time: 2:00 PM
- Setting: `block_if_before_number = 2`, `block_if_before_unit = 'hour'`
- Available Slots: 3:00 PM, 4:00 PM, 5:00 PM

**Expected Results:**
- ❌ 3:00 PM slot should be hidden (too close)
- ✅ 4:00 PM slot should be visible
- ✅ 5:00 PM slot should be visible

### Test Case 2: Day-Based Blocking
**Setup:**
- Current Time: 2:00 PM today
- Setting: `block_if_before_number = 1`, `block_if_before_unit = 'day'`
- Available Slots: Tomorrow 10:00 AM, Tomorrow 2:00 PM

**Expected Results:**
- ❌ Tomorrow 10:00 AM slot should be hidden (within 24 hours)
- ✅ Tomorrow 2:00 PM slot should be visible (after 24 hours)

### Test Case 3: No Blocking
**Setup:**
- Current Time: 2:00 PM
- Setting: `block_if_before_number = 0` or empty
- Available Slots: 3:00 PM, 4:00 PM, 5:00 PM

**Expected Results:**
- ✅ All slots should be visible (no blocking applied)

## 📝 Implementation Priority

### High Priority (Fix Now)
1. **Complete shortcode form implementation** - Affects existing users
2. **Test and verify AI system implementation** - Ensure consistency

### Medium Priority (Fix Later)
1. **Add time slot level filtering to AI system** - Enhancement for better UX
2. **Add admin interface for easier configuration** - User experience improvement

### Low Priority (Future Enhancement)
1. **Add real-time slot filtering** - Advanced feature
2. **Add different blocking rules for different appointment types** - Advanced feature

## 🔍 Verification Checklist

### For Shortcode Form Fix:
- [ ] AJAX function applies `block_if_before_number` to time slots
- [ ] Same-day appointments respect the blocking period
- [ ] Different units (hour/day) work correctly
- [ ] No blocking when settings are empty/zero
- [ ] Existing functionality remains unchanged

### For AI System Verification:
- [ ] Date filtering works correctly
- [ ] Time slot filtering works correctly (if implemented)
- [ ] All API endpoints are consistent
- [ ] Frontend components receive filtered data
- [ ] No performance degradation

## 📚 Related Files

### Core Implementation:
- `functions/helpers/timetable-helpers.php` - Main date filtering logic
- `functions/helpers/settings.php` - Settings retrieval
- `functions/ai-integration.php` - AI system implementation

### Shortcode Form:
- `functions/ajax/consulting-form.php` - AJAX handlers
- `functions/helpers/render.php` - Rendering functions
- `functions/shortcodes.php` - Shortcode definitions

### Frontend AI:
- `jalsah-ai-frontend/src/components/BookingModal.vue` - Booking modal
- `jalsah-ai-frontend/src/views/RescheduleAppointment.vue` - Reschedule form
- `jalsah-ai-frontend/src/views/Appointments.vue` - Appointments page

## 🎯 Success Criteria

### Complete Implementation Achieved When:
1. ✅ Shortcode form respects `block_if_before_number` at both date and time slot levels
2. ✅ AI system respects `block_if_before_number` at both date and time slot levels
3. ✅ Both systems provide identical behavior for identical settings
4. ✅ All test scenarios pass
5. ✅ No regression in existing functionality
6. ✅ Performance remains acceptable

---

**Last Updated:** $(date)  
**Status:** Implementation in progress  
**Priority:** High for shortcode form completion, Medium for AI system enhancements
