/**
 * Rochetah Google Meet booking API (admin/secretary/rochtah_doctor). JWT required.
 */
import api from './api'

const BASE = '/api/ai/rochtah-meet'

export default {
  getDoctors() {
    return api.get(BASE + '/doctors').then(r => r.data?.data ?? r.data)
  },
  searchPatient(q) {
    return api.post(BASE + '/search-patient', { q }).then(r => r.data?.data ?? r.data)
  },
  getPatientDiagnosis(patientId) {
    return api
      .get(BASE + '/patient-diagnosis', { params: { patient_id: patientId } })
      .then(r => r.data?.data ?? r.data)
  },
  getMeetUrls() {
    return api.get(BASE + '/meet-urls').then(r => r.data?.data ?? r.data)
  },
  submit(payload) {
    return api
      .post(BASE + '/submit', payload, { skipGlobalErrorToast: true })
      .then(r => r.data?.data ?? r.data)
  },
  listBookings(params = {}) {
    return api.get(BASE + '/list-bookings', { params }).then(r => r.data?.data ?? r.data)
  },
  updateStatus(bookingId, status) {
    return api
      .post(BASE + '/update-status', { booking_id: bookingId, status }, { skipGlobalErrorToast: true })
      .then(r => r.data?.data ?? r.data)
  }
}
