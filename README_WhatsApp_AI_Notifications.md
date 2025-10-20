# WhatsApp Notifications for Jalsah AI Sessions

This document describes the WhatsApp notification system implemented specifically for Jalsah AI sessions.

## Overview

A comprehensive WhatsApp notification system has been implemented that sends automated notifications to patients and doctors at various stages of the AI session lifecycle. **All notifications work exclusively for AI sessions only**, not regular therapist sessions.

## Features

### 1. Database Structure

**New columns added to `snks_provider_timetable` table:**
- `whatsapp_new_session_sent` - Tracks if new session notification was sent to patient
- `whatsapp_doctor_notified` - Tracks if new booking notification was sent to doctor
- `whatsapp_rosheta_activated` - Tracks if rosheta activation notification was sent
- `whatsapp_rosheta_booked` - Tracks if rosheta appointment notification was sent
- `whatsapp_doctor_reminded` - Tracks if midnight reminder was sent to doctor
- `whatsapp_patient_now_sent` - Tracks if "doctor joined" notification was sent to patient

### 2. Admin Settings Page

**Location:** WordPress Admin > Shrinks Settings > WhatsApp AI Notifications

**Settings include:**
- Enable/disable WhatsApp notifications
- Customizable template names for each notification type
- Template content reference (read-only) for easy setup

### 3. Notification Types

#### 3.1 New Session Notification (Patient)
- **Template Name:** `new_session` (customizable)
- **Trigger:** When a patient books an AI session
- **Recipient:** Patient
- **Variables:** `{{doctor}}`, `{{day}}`, `{{date}}`, `{{time}}`
- **Content:** Full session details with instructions and notes

#### 3.2 New Booking Notification (Doctor)
- **Template Name:** `doctor_new` (customizable)
- **Trigger:** When a patient books an AI session
- **Recipient:** Doctor/Therapist
- **Variables:** `{{patient}}`, `{{day}}`, `{{date}}`, `{{time}}`
- **Content:** New booking notification with patient details

#### 3.3 Rosheta Service Activation (Patient)
- **Template Name:** `rosheta10` (customizable)
- **Trigger:** When therapist activates prescription service for patient
- **Recipient:** Patient
- **Variables:** `{{patient}}`, `{{doctor}}`
- **Content:** Notification to book a prescription appointment

#### 3.4 Rosheta Appointment Confirmation (Patient)
- **Template Name:** `rosheta_app` (customizable)
- **Trigger:** When patient confirms/books a rosheta appointment slot
- **Recipient:** Patient
- **Variables:** `{{day}}`, `{{date}}`, `{{time}}`
- **Content:** Appointment details and instructions

#### 3.5 24-Hour Reminder (Patient)
- **Template Name:** `patient_rem_24h` (customizable)
- **Trigger:** 24 hours before AI session
- **Recipient:** Patient
- **Variables:** `{{doctor}}`, `{{day}}`, `{{date}}`, `{{time}}`
- **Content:** Session reminder with full instructions
- **Note:** Only for AI sessions; regular sessions use legacy SMS

#### 3.6 1-Hour Reminder (Patient)
- **Template Name:** `patient_rem_1h` (customizable)
- **Trigger:** 1 hour before AI session
- **Recipient:** Patient
- **Variables:** None
- **Content:** Urgent reminder to join session

#### 3.7 Doctor Joined Notification (Patient)
- **Template Name:** `patient_rem_now` (customizable)
- **Trigger:** When doctor/therapist joins the AI session
- **Recipient:** Patient
- **Variables:** None
- **Content:** Notification that therapist is ready and waiting

#### 3.8 Midnight Doctor Reminder
- **Template Name:** `doctor_rem` (customizable)
- **Trigger:** Daily at midnight if doctor has AI sessions tomorrow
- **Recipient:** Doctor/Therapist
- **Variables:** `{{day}}`, `{{date}}`
- **Content:** Reminder of tomorrow's sessions
- **Cron:** Runs daily at midnight

## Implementation Details

### Files Created/Modified

**New Files:**
1. `functions/helpers/whatsapp-ai-notifications.php` - Core notification functions
2. `functions/admin/whatsapp-ai-notifications-settings.php` - Admin settings page

**Modified Files:**
1. `includes/timetable-table.php` - Added notification tracking columns
2. `functions/ai-prescription.php` - Added rosheta activation notification
3. `functions/ajax/timetable-ajax.php` - Added rosheta appointment notification
4. `functions/crons/sms.php` - Updated reminder system for AI sessions
5. `functions/ai-integration.php` - Added doctor joined notification
6. `anony-shrinks.php` - Added database columns to activation hook

### Key Functions

#### Core Functions
- `snks_is_ai_session($session)` - Checks if session is an AI session
- `snks_get_whatsapp_notification_settings()` - Gets notification settings
- `snks_send_whatsapp_template_message($phone, $template, $params)` - Sends WhatsApp template message
- `snks_get_user_whatsapp($user_id)` - Gets user's WhatsApp number
- `snks_get_arabic_day_name($date)` - Formats Arabic day name

#### Notification Functions
- `snks_send_new_session_notification($session_id)` - Patient booking notification
- `snks_send_doctor_new_booking_notification($session_id)` - Doctor booking notification
- `snks_send_rosheta_activation_notification($patient_id, $doctor_id)` - Rosheta activation
- `snks_send_rosheta_appointment_notification($booking_id)` - Rosheta appointment
- `snks_send_doctor_joined_notification($session_id)` - Doctor joined notification
- `snks_send_doctor_midnight_reminders()` - Midnight doctor reminders

### Hooks and Integration Points

1. **Session Booking:** `snks_appointment_created` action hook
2. **Rosheta Activation:** Called in `snks_send_ai_prescription_notifications()`
3. **Rosheta Appointment:** After booking confirmation in `snks_book_session_rochtah_appointment()`
4. **Reminders:** Integrated into `snks_send_session_notifications()` cron
5. **Doctor Joined:** In `set_therapist_joined()` REST API endpoint
6. **Midnight Reminder:** Scheduled cron job `snks_send_doctor_midnight_reminders`

## WhatsApp Business API Requirements

### Template Setup
All templates must be created and approved in your WhatsApp Business API account before use. The system uses the Meta WhatsApp Business API format.

### Required Configuration
The system uses existing WhatsApp API settings from:
- **Settings Location:** Therapist Registration Settings
- **Required Fields:**
  - WhatsApp API URL
  - WhatsApp API Token
  - WhatsApp Phone Number ID
  - Message Language (ar/en_US)

### Template Parameters
Templates must be configured with the correct number of parameters as specified for each notification type.

## Usage Instructions

### Initial Setup

1. **Create WhatsApp Templates:**
   - Log into your Meta Business Manager
   - Create templates matching the provided content
   - Get template names after approval

2. **Configure Plugin Settings:**
   - Go to: WordPress Admin > Shrinks Settings > WhatsApp AI Notifications
   - Enable notifications
   - Enter your approved template names
   - Save settings

3. **Test Notifications:**
   - Create a test AI session booking
   - Verify notifications are sent correctly
   - Check phone number format and delivery

### Database Setup

The notification tracking columns are automatically added:
- On plugin activation
- On `admin_init` hook (fallback)

No manual database migrations required.

### Monitoring

**Check Notification Status:**
- Query `snks_provider_timetable` table
- Check `whatsapp_*_sent` columns
- Review WordPress error logs for debugging

**Error Logs:**
- Failed API calls are logged to error_log
- Missing phone numbers are logged
- API response codes are logged

## AI Session Detection

The system identifies AI sessions by checking:
1. Session `settings` field contains `'ai_booking'`
2. Uses `snks_is_ai_session()` helper function

**Non-AI sessions** continue to use the legacy SMS notification system.

## Cron Jobs

### Existing: Session Reminders
- **Hook:** `snks_check_session_notifications`
- **Schedule:** Every minute
- **Function:** `snks_send_session_notifications()`
- **Handles:** 24-hour and 1-hour reminders

### New: Midnight Doctor Reminders
- **Hook:** `snks_send_doctor_midnight_reminders`
- **Schedule:** Daily at midnight
- **Function:** `snks_send_doctor_midnight_reminders()`
- **Handles:** Next-day session reminders for doctors

## Troubleshooting

### Notifications Not Sending

1. **Check Settings:**
   - Verify WhatsApp AI Notifications are enabled
   - Confirm WhatsApp API is configured
   - Check template names match approved templates

2. **Check Phone Numbers:**
   - User must have valid `billing_phone` or `whatsapp` meta
   - Phone should be in international format (+20...)
   - Check error logs for missing numbers

3. **Check Session Type:**
   - Confirm session has `ai_booking` in settings field
   - Verify `snks_is_ai_session()` returns true

4. **Check Notification Status:**
   - Query database for `whatsapp_*_sent` flags
   - Ensure flags are 0 (not sent yet)
   - Reset flags if needed for testing

### API Errors

1. **Invalid Template:**
   - Template name doesn't match approved template
   - Template parameters count mismatch
   - Solution: Update template name in settings

2. **Authentication Failed:**
   - API token expired or invalid
   - Solution: Update token in registration settings

3. **Rate Limiting:**
   - Too many messages sent
   - Solution: Implement delay between sends (future enhancement)

## Future Enhancements

Potential improvements:
1. Message delivery status tracking
2. Rate limiting for bulk notifications
3. Notification history log
4. Test notification feature
5. Notification statistics dashboard
6. Custom notification scheduling
7. Multi-language template support
8. SMS fallback option

## Security Considerations

- All phone numbers are sanitized
- Nonce verification on settings save
- Capability checks (`manage_options`)
- SQL injection prevention with prepared statements
- XSS protection on admin interface

## Compatibility

- **WordPress:** 5.0+
- **PHP:** 7.4+
- **WhatsApp Business API:** Meta Cloud API
- **Works With:** Jalsah AI plugin ecosystem
- **Session Types:** AI sessions only

## Support

For issues or questions:
1. Check error logs
2. Verify WhatsApp API configuration
3. Review template setup
4. Check session is AI type
5. Verify phone number format

## Changelog

### Version 1.0.0 (Initial Release)
- Complete WhatsApp notification system for AI sessions
- 8 notification types with customizable templates
- Admin settings interface
- Database tracking columns
- Automated cron jobs
- Full integration with existing AI booking flow

---

**Note:** This system works exclusively for Jalsah AI sessions. Regular therapist bookings continue to use the existing SMS notification system via WhySMS.

