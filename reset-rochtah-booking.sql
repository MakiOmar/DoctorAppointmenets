-- Reset rochtah booking to allow patient to book again
-- Replace REQUEST_ID with the actual request ID from snks_rochtah_bookings table

UPDATE wp_snks_rochtah_bookings 
SET 
    status = 'pending',
    booking_date = '0000-00-00',
    booking_time = '00:00:00',
    appointment_id = NULL,
    whatsapp_appointment_sent = 0,
    updated_at = NOW()
WHERE id = REQUEST_ID;

-- To find the request ID, run this query first:
-- SELECT id, patient_id, therapist_id, status, booking_date, booking_time 
-- FROM wp_snks_rochtah_bookings 
-- WHERE patient_id = PATIENT_ID AND status = 'confirmed';
