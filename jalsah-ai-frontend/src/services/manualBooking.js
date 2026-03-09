/**
 * Manual Booking API (admin/secretary). All requests require JWT (Bearer).
 */
import api from './api'

const BASE = '/api/ai/manual-booking'

export default {
  getTherapists() {
    return api.get(BASE + '/therapists').then(r => r.data?.data ?? r.data)
  },
  searchPatient(q) {
    return api.post(BASE + '/search-patient', { q }).then(r => r.data?.data ?? r.data)
  },
  getAvailableDates(therapistId) {
    return api.get(BASE + '/available-dates', { params: { therapist_id: therapistId } }).then(r => r.data?.data ?? r.data)
  },
  getSlots(therapistId, date) {
    return api.get(BASE + '/slots', { params: { therapist_id: therapistId, date } }).then(r => r.data?.data ?? r.data)
  },
  getTherapistCountries(therapistId) {
    return api.get(BASE + '/therapist-countries', { params: { therapist_id: therapistId } }).then(r => r.data?.data ?? r.data)
  },
  getPrice(therapistId, countryCode, period = 45) {
    return api.get(BASE + '/price', { params: { therapist_id: therapistId, country_code: countryCode, period } }).then(r => r.data?.data ?? r.data)
  },
  searchAppointments(q) {
    return api.post(BASE + '/search-appointments', { q }).then(r => r.data?.data ?? r.data)
  },
  listBookings(page = 1, perPage = 100) {
    return api.get(BASE + '/list-bookings', { params: { page, per_page: perPage } }).then(r => r.data?.data ?? r.data)
  },
  getBookingsByPhone(phone, page = 1, perPage = 100) {
    return api.post(BASE + '/bookings-by-phone', { phone, page, per_page: perPage }).then(r => r.data?.data ?? r.data)
  },
  submit(payload) {
    return api.post(BASE + '/submit', payload).then(r => r.data?.data ?? r.data)
  }
}
