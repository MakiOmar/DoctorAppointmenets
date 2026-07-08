<template>
  <!-- Rochetah Google Meet booking form -->
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">
      {{ $t('rochtahMeet.title') }}
    </h1>
    <p class="mb-4">
      <router-link to="/rochtah-meet-bookings" class="text-primary-600 hover:underline text-sm">
        {{ $t('rochtahMeetManage.viewAllBookings') }}
      </router-link>
    </p>

    <form class="space-y-4" @submit.prevent="submitBooking">
      <!-- Patient phone search -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('rochtahMeet.enterPhone') }}</label>
        <div class="flex flex-col gap-2 sm:flex-row sm:gap-0">
          <div class="relative flex flex-1 min-w-0 rounded-md shadow-sm">
            <button
              type="button"
              class="inline-flex items-center px-3 rounded-l-md border border-gray-300 bg-gray-50 text-sm shrink-0"
              @click="showPatientCountryDropdown = !showPatientCountryDropdown"
            >
              <span class="mr-1">{{ selectedPatientCountry?.flag }}</span>
              <span>{{ selectedPatientCountry?.dial_code ?? '+20' }}</span>
            </button>
            <div
              v-if="showPatientCountryDropdown"
              class="absolute z-20 mt-9 left-0 w-56 bg-white border rounded shadow-lg max-h-48 overflow-y-auto"
            >
              <input
                v-model="patientCountrySearch"
                type="text"
                :placeholder="$t('rochtahMeet.searchCountries')"
                class="w-full px-2 py-1 border-b text-sm"
              />
              <button
                v-for="c in filteredPatientCountries"
                :key="c.country_code"
                type="button"
                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-center"
                @click="selectPatientCountry(c); showPatientCountryDropdown = false"
              >
                <span class="mr-2">{{ c.flag }}</span>
                <span>{{ $i18n.locale === 'ar' && c.name_ar ? c.name_ar : (c.name_en || c.name) }}</span>
                <span class="text-gray-400 text-xs ml-1">{{ c.dial_code }}</span>
              </button>
            </div>
            <input
              v-model="patientPhoneDigits"
              type="text"
              inputmode="numeric"
              class="flex-1 min-w-0 rounded-r-md border border-gray-300 px-3 py-2.5 sm:py-2"
              :class="{ 'border-red-500': errors.phone }"
              :placeholder="$t('rochtahMeet.phoneDigits')"
              @input="onPatientPhoneInput"
            />
          </div>
          <div class="flex items-center gap-2 sm:ml-2 shrink-0">
            <button
              type="button"
              class="w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-md border border-primary-500 bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 disabled:opacity-50"
              :disabled="patientSearchLoading || !patientPhoneDigits.trim()"
              @click="runPatientSearch"
            >
              {{ $t('rochtahMeet.searchPatient') }}
            </button>
            <span
              v-if="patientSearchLoading"
              class="animate-spin h-5 w-5 border-2 border-primary-500 border-t-transparent rounded-full shrink-0"
            />
          </div>
        </div>
        <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
        <div v-if="patientSearchResults.length > 0" class="mt-1 border rounded bg-white shadow-lg max-h-40 overflow-y-auto">
          <button
            v-for="p in patientSearchResults"
            :key="p.id"
            type="button"
            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
            @click="selectPatient(p)"
          >
            {{ p.name || p.email }}
            <span v-if="p.phone" class="text-gray-500 text-xs"> — {{ p.phone }}</span>
          </button>
        </div>
        <p v-else-if="patientSearched && !patientSearchLoading" class="mt-1 text-sm text-amber-600">
          {{ $t('rochtahMeet.noPatientFound') }}
        </p>
      </div>

      <!-- Patient name (read-only after selection) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('rochtahMeet.patientName') }} *</label>
        <input
          v-model="patientName"
          type="text"
          readonly
          class="w-full rounded border px-3 py-2 bg-gray-50 border-gray-300"
          :class="{ 'border-red-500': errors.patient }"
          :placeholder="$t('rochtahMeet.selectPatientHint')"
        />
        <p v-if="errors.patient" class="mt-1 text-sm text-red-600">{{ errors.patient }}</p>
      </div>

      <!-- Diagnosis preview -->
      <div v-if="patientId" class="rounded border border-gray-200 bg-gray-50 p-3 text-sm">
        <p class="font-medium text-gray-700 mb-1">{{ $t('rochtahMeet.diagnosisPreview') }}</p>
        <p v-if="diagnosisLoading" class="text-gray-500">{{ $t('rochtahMeet.loadingDiagnosis') }}</p>
        <template v-else-if="patientDiagnosis">
          <p><strong>{{ $t('rochtahMeet.diagnosisName') }}:</strong> {{ patientDiagnosis.diagnosis_name || '—' }}</p>
          <p class="mt-1 whitespace-pre-wrap"><strong>{{ $t('rochtahMeet.reasoning') }}:</strong> {{ patientDiagnosis.reasoning || '—' }}</p>
        </template>
        <p v-else class="text-gray-500">{{ $t('rochtahMeet.noDiagnosis') }}</p>
      </div>

      <!-- Rochtah doctor -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('rochtahMeet.rochtahDoctor') }} *</label>
        <select
          v-model="rochtahDoctorId"
          class="w-full rounded border px-3 py-2 border-gray-300"
          :class="{ 'border-red-500': errors.doctor }"
          :disabled="doctorsLoading"
        >
          <option value="">{{ $t('rochtahMeet.selectDoctor') }}</option>
          <option v-for="d in doctors" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
        </select>
        <p v-if="errors.doctor" class="mt-1 text-sm text-red-600">{{ errors.doctor }}</p>
      </div>

      <!-- Date & time (Flatpickr) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('rochtahMeet.appointmentDateTime') }} *</label>
        <FlatPickr
          v-model="appointmentDatetime"
          :config="flatpickrConfig"
          class="w-full rounded border px-3 py-2 border-gray-300"
          :class="{ 'border-red-500': errors.datetime }"
          :placeholder="$t('rochtahMeet.selectDateTime')"
        />
        <p v-if="errors.datetime" class="mt-1 text-sm text-red-600">{{ errors.datetime }}</p>
      </div>

      <!-- Google Meet URL from pool -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('rochtahMeet.meetUrl') }} *</label>
        <select
          v-model="meetUrlId"
          class="w-full rounded border px-3 py-2 border-gray-300"
          :class="{ 'border-red-500': errors.meetUrl }"
          :disabled="meetUrlsLoading"
        >
          <option value="">{{ $t('rochtahMeet.selectMeetUrl') }}</option>
          <option v-for="u in meetUrls" :key="u.id" :value="String(u.id)">
            #{{ u.id }} — {{ u.meet_url }}
          </option>
        </select>
        <p v-if="!meetUrlsLoading && meetUrls.length === 0" class="mt-1 text-sm text-amber-600">
          {{ $t('rochtahMeet.noMeetUrls') }}
        </p>
        <p v-if="errors.meetUrl" class="mt-1 text-sm text-red-600">{{ errors.meetUrl }}</p>
      </div>

      <button
        type="submit"
        class="w-full sm:w-auto px-6 py-2.5 rounded-md bg-primary-500 text-white font-medium hover:bg-primary-600 disabled:opacity-50"
        :disabled="submitLoading"
      >
        {{ submitLoading ? $t('rochtahMeet.submitting') : $t('rochtahMeet.submit') }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import Swal from 'sweetalert2'
import { useToast } from 'vue-toastification'
import rochtahMeetApi from '@/services/rochtahMeet'

const { t, locale } = useI18n()
const toast = useToast()

const patientPhoneDigits = ref('')
const patientCountrySearch = ref('')
const showPatientCountryDropdown = ref(false)
const patientCountries = ref([])
const selectedPatientCountryCode = ref('EG')
const patientSearchResults = ref([])
const patientSearchLoading = ref(false)
const patientSearched = ref(false)
const patientId = ref(null)
const patientName = ref('')
const patientDiagnosis = ref(null)
const diagnosisLoading = ref(false)

const doctors = ref([])
const doctorsLoading = ref(false)
const rochtahDoctorId = ref('')

const appointmentDatetime = ref('')
const meetUrlId = ref('')
const meetUrls = ref([])
const meetUrlsLoading = ref(false)
const submitLoading = ref(false)
const errors = ref({})

const flatpickrConfig = computed(() => ({
  enableTime: true,
  dateFormat: 'Y-m-d H:i',
  time_24hr: false,
  minDate: 'today',
  locale: locale.value === 'ar' ? undefined : undefined
}))

const selectedPatientCountry = computed(() => {
  return patientCountries.value.find(c => c.country_code === selectedPatientCountryCode.value) || patientCountries.value[0]
})

const filteredPatientCountries = computed(() => {
  const q = patientCountrySearch.value.trim().toLowerCase()
  if (!q) return patientCountries.value
  return patientCountries.value.filter(c => {
    const name = (c.name_en || c.name || '').toLowerCase()
    const nameAr = (c.name_ar || '').toLowerCase()
    return name.includes(q) || nameAr.includes(q) || (c.dial_code || '').includes(q)
  })
})

function validatePhoneNumber(phoneNumber, countryCode) {
  const digits = String(phoneNumber || '').replace(/\D/g, '')
  if (!digits) {
    return { isValid: false, error: t('rochtahMeet.validation.phoneRequired') }
  }
  if (countryCode === 'EG' && digits.length < 10) {
    return { isValid: false, error: t('rochtahMeet.validation.phoneInvalid') }
  }
  return { isValid: true }
}

function onPatientPhoneInput() {
  patientPhoneDigits.value = patientPhoneDigits.value.replace(/\D/g, '')
  patientSearchResults.value = []
  patientSearched.value = false
  if (errors.value.phone) errors.value.phone = ''
}

function selectPatientCountry(c) {
  selectedPatientCountryCode.value = c.country_code
}

async function runPatientSearch() {
  const digits = patientPhoneDigits.value.trim()
  if (!digits) {
    errors.value.phone = t('rochtahMeet.validation.phoneRequired')
    return
  }
  const validation = validatePhoneNumber(digits, selectedPatientCountryCode.value)
  if (!validation.isValid) {
    errors.value.phone = validation.error
    toast.error(validation.error)
    return
  }
  errors.value.phone = ''
  patientSearchLoading.value = true
  patientSearchResults.value = []
  patientSearched.value = true
  try {
    const dial = selectedPatientCountry.value?.dial_code?.replace('+', '') || '20'
    const q = dial + digits
    const data = await rochtahMeetApi.searchPatient(q)
    patientSearchResults.value = Array.isArray(data) ? data : []
  } catch (err) {
    patientSearchResults.value = []
    toast.error(err.response?.data?.error || t('rochtahMeet.messages.searchFailed'))
  } finally {
    patientSearchLoading.value = false
  }
}

async function loadPatientDiagnosis(id) {
  diagnosisLoading.value = true
  patientDiagnosis.value = null
  try {
    const data = await rochtahMeetApi.getPatientDiagnosis(id)
    patientDiagnosis.value = data?.diagnosis || null
  } catch (_) {
    patientDiagnosis.value = null
  } finally {
    diagnosisLoading.value = false
  }
}

function selectPatient(p) {
  patientId.value = p.id
  const combined = `${p.first_name || ''} ${p.last_name || ''}`.trim()
  patientName.value = combined || p.name || p.email || ''
  patientSearchResults.value = []
  patientSearched.value = false
  if (errors.value.patient) errors.value.patient = ''
  loadPatientDiagnosis(p.id)
}

function validateForm() {
  const next = {}
  if (!patientId.value) {
    next.patient = t('rochtahMeet.validation.patientRequired')
  }
  if (!rochtahDoctorId.value) {
    next.doctor = t('rochtahMeet.validation.doctorRequired')
  }
  if (!appointmentDatetime.value) {
    next.datetime = t('rochtahMeet.validation.datetimeRequired')
  }
  if (!meetUrlId.value) {
    next.meetUrl = t('rochtahMeet.validation.meetUrlRequired')
  }
  errors.value = next
  return Object.keys(next).length === 0
}

function resetForm() {
  patientPhoneDigits.value = ''
  patientId.value = null
  patientName.value = ''
  patientDiagnosis.value = null
  rochtahDoctorId.value = ''
  appointmentDatetime.value = ''
  meetUrlId.value = ''
  errors.value = {}
  patientSearched.value = false
}

async function submitBooking() {
  if (!validateForm()) return

  const confirm = await Swal.fire({
    icon: 'question',
    title: t('rochtahMeet.confirmTitle'),
    text: t('rochtahMeet.confirmText'),
    showCancelButton: true,
    confirmButtonText: t('rochtahMeet.submit'),
    cancelButtonText: t('common.cancel')
  })
  if (!confirm.isConfirmed) return

  submitLoading.value = true
  try {
    const result = await rochtahMeetApi.submit({
      patient_id: patientId.value,
      rochtah_doctor_id: Number(rochtahDoctorId.value),
      appointment_datetime: appointmentDatetime.value,
      meet_url_id: Number(meetUrlId.value)
    })
    await Swal.fire({
      icon: 'success',
      title: t('rochtahMeet.messages.success'),
      html: `<p>${t('rochtahMeet.messages.bookingId')}: #${result?.booking?.booking_id || '—'}</p>`
    })
    toast.success(t('rochtahMeet.messages.success'))
    resetForm()
    await loadMeetUrls()
  } catch (err) {
    const msg = err.response?.data?.error || t('rochtahMeet.messages.submitFailed')
    toast.error(msg)
    await Swal.fire({ icon: 'error', title: t('rochtahMeet.messages.submitFailed'), text: msg })
  } finally {
    submitLoading.value = false
  }
}

async function loadMeetUrls() {
  meetUrlsLoading.value = true
  try {
    const data = await rochtahMeetApi.getMeetUrls()
    meetUrls.value = Array.isArray(data?.urls) ? data.urls : []
  } catch (_) {
    meetUrls.value = []
    toast.error(t('rochtahMeet.messages.loadMeetUrlsFailed'))
  } finally {
    meetUrlsLoading.value = false
  }
}

onMounted(async () => {
  doctorsLoading.value = true
  meetUrlsLoading.value = true
  try {
    const data = await rochtahMeetApi.getDoctors()
    doctors.value = Array.isArray(data) ? data : []
  } catch (_) {
    toast.error(t('rochtahMeet.messages.loadDoctorsFailed'))
  } finally {
    doctorsLoading.value = false
  }

  await loadMeetUrls()

  try {
    const res = await fetch('/countries-codes-and-flags.json')
    const json = await res.json()
    if (Array.isArray(json)) {
      const eg = json.find(c => c.country_code === 'EG')
      const rest = json.filter(c => c.country_code !== 'EG')
      patientCountries.value = eg ? [eg, ...rest] : json
    }
  } catch (_) {
    patientCountries.value = [
      { country_code: 'EG', name_en: 'Egypt', dial_code: '+20', flag: '🇪🇬' },
      { country_code: 'SA', name_en: 'Saudi Arabia', dial_code: '+966', flag: '🇸🇦' }
    ]
  }
})
</script>
