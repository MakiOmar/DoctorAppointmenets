<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">
      {{ $t('manualBooking.title', 'Manual Booking') }}
    </h1>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
      <button
        type="button"
        class="px-4 py-2 font-medium"
        :class="activeTab === 'new' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500'"
        @click="activeTab = 'new'"
      >
        {{ $t('manualBooking.newBooking', 'New booking') }}
      </button>
      <button
        type="button"
        class="px-4 py-2 font-medium"
        :class="activeTab === 'change' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500'"
        @click="activeTab = 'change'"
      >
        {{ $t('manualBooking.changeAppointment', 'Change appointment') }}
      </button>
    </div>

    <!-- New booking -->
    <form v-if="activeTab === 'new'" class="space-y-4" @submit.prevent="submitNewBooking">
      <!-- Patient: country + phone -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.enterPhone', 'Enter the phone number') }}</label>
        <div class="flex rounded-md shadow-sm">
          <div class="relative flex-1 flex">
            <button
              type="button"
              class="inline-flex items-center px-3 rounded-l-md border border-gray-300 bg-gray-50 text-sm"
              @click="showPatientCountryDropdown = !showPatientCountryDropdown"
            >
              <span class="mr-1">{{ selectedPatientCountry?.flag }}</span>
              <span>{{ selectedPatientCountry?.dial_code ?? '+20' }}</span>
            </button>
            <div v-if="showPatientCountryDropdown" class="absolute z-20 mt-9 left-0 w-56 bg-white border rounded shadow-lg max-h-48 overflow-y-auto">
              <input
                v-model="patientCountrySearch"
                type="text"
                placeholder="Search..."
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
                <span>{{ c.name }}</span>
                <span class="text-gray-400 text-xs ml-1">{{ c.dial_code }}</span>
              </button>
            </div>
            <input
              v-model="patientPhoneDigits"
              type="text"
              inputmode="numeric"
              class="flex-1 rounded-r-md border border-gray-300 px-3 py-2"
              :placeholder="$t('manualBooking.phoneDigits', 'Digits only')"
              @input="onPatientPhoneInput"
            />
          </div>
          <div class="ml-2 flex items-center">
            <span v-if="patientSearchLoading" class="animate-spin h-5 w-5 border-2 border-primary-500 border-t-transparent rounded-full" />
          </div>
        </div>
        <!-- Patient search results -->
        <div v-if="patientSearchResults.length > 0" class="mt-1 border rounded bg-white shadow-lg max-h-40 overflow-y-auto">
          <button
            v-for="p in patientSearchResults"
            :key="p.id"
            type="button"
            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
            @click="selectPatient(p)"
          >
            {{ p.name || p.email }} {{ p.first_name || p.last_name ? `(${p.first_name} ${p.last_name})` : '' }}
          </button>
        </div>
      </div>

      <!-- First / Last name -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.firstName', 'First name') }} *</label>
          <input v-model="patientFirstName" type="text" required class="w-full rounded border border-gray-300 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.lastName', 'Last name') }} *</label>
          <input v-model="patientLastName" type="text" required class="w-full rounded border border-gray-300 px-3 py-2" />
        </div>
      </div>

      <!-- Therapist -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.therapist', 'Therapist') }}</label>
        <select v-model="selectedTherapistId" class="w-full rounded border border-gray-300 px-3 py-2" @change="onTherapistChange">
          <option value="">— {{ $t('manualBooking.selectTherapist', 'Select therapist') }} —</option>
          <option v-for="t in therapists" :key="t.user_id" :value="t.user_id">
            {{ t.name || t.name_en || t.user_id }}
          </option>
        </select>
        <span v-if="therapistsLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date', 'Date') }}</label>
        <select v-model="selectedDate" class="w-full rounded border border-gray-300 px-3 py-2" @change="onDateChange">
          <option value="">— {{ $t('manualBooking.selectDate', 'Select date') }} —</option>
          <option v-for="d in availableDates" :key="d.date" :value="d.date">{{ d.label }}</option>
        </select>
        <span v-if="datesLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Slot -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot', 'Time slot') }}</label>
        <select v-model="selectedSlotId" class="w-full rounded border border-gray-300 px-3 py-2">
          <option value="">— {{ $t('manualBooking.selectSlot', 'Select slot') }} —</option>
          <option v-for="s in slots" :key="s.slot_id" :value="s.slot_id">{{ s.formatted_time }}</option>
        </select>
        <span v-if="slotsLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Country (pricing) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.country', 'Country (price)') }}</label>
        <select v-model="selectedCountryCode" class="w-full rounded border border-gray-300 px-3 py-2">
          <option value="">— {{ $t('manualBooking.selectCountry', 'Select country') }} —</option>
          <option v-for="c in therapistCountries" :key="c.code" :value="c.code">
            {{ c.name }} — {{ c.price }} {{ c.currency_symbol }}
          </option>
        </select>
        <span v-if="countriesLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Amount override (optional) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.amount', 'Amount (optional override)') }}</label>
        <input v-model="amountOverride" type="text" class="w-full rounded border border-gray-300 px-3 py-2" placeholder="" />
      </div>

      <!-- Payment method -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.paymentMethod', 'Payment method') }}</label>
        <select v-model="paymentMethod" class="w-full rounded border border-gray-300 px-3 py-2">
          <option value="">—</option>
          <option value="InstaPay">InstaPay</option>
          <option value="Wallet">Wallet</option>
          <option value="Bank transfer">Bank transfer</option>
        </select>
      </div>

      <div class="pt-2">
        <button
          type="submit"
          class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50"
          :disabled="submitLoading || !patientId || !selectedTherapistId || !selectedSlotId || !selectedCountryCode || !patientFirstName.trim() || !patientLastName.trim()"
        >
          <span v-if="submitLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
          {{ $t('manualBooking.createBooking', 'Create booking') }}
        </button>
      </div>
    </form>

    <!-- Change appointment -->
    <div v-else class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.searchAppointment', 'Search by email, phone or booking ID') }}</label>
        <div class="flex gap-2">
          <input
            v-model="changeSearchQuery"
            type="text"
            class="flex-1 rounded border border-gray-300 px-3 py-2"
            :placeholder="$t('manualBooking.searchPlaceholder', 'Email, phone or booking ID')"
            @input="onChangeSearchInput"
          />
          <span v-if="changeSearchLoading" class="flex items-center animate-spin h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
        </div>
      </div>
      <div v-if="changeSearchResults.length > 0" class="border rounded bg-white divide-y max-h-48 overflow-y-auto">
        <button
          v-for="a in changeSearchResults"
          :key="a.booking_id"
          type="button"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
          @click="selectAppointment(a)"
        >
          #{{ a.booking_id }} — {{ a.patient_name }} / {{ a.therapist_name }} — {{ formatDateTime(a.date_time) }}
        </button>
      </div>

      <template v-if="selectedAppointment">
        <div class="border-t pt-4 mt-4">
          <p class="text-sm text-gray-600 mb-2">{{ $t('manualBooking.selectNewSlot', 'Select new date and time') }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date', 'Date') }}</label>
              <select v-model="changeSelectedDate" class="w-full rounded border border-gray-300 px-3 py-2" @change="onChangeDateChange">
                <option value="">— {{ $t('manualBooking.selectDate', 'Select date') }} —</option>
                <option v-for="d in changeAvailableDates" :key="d.date" :value="d.date">{{ d.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot', 'Time slot') }}</label>
              <select v-model="changeSelectedSlotId" class="w-full rounded border border-gray-300 px-3 py-2">
                <option value="">— {{ $t('manualBooking.selectSlot', 'Select slot') }} —</option>
                <option v-for="s in changeSlots" :key="s.slot_id" :value="s.slot_id">{{ s.formatted_time }}</option>
              </select>
            </div>
          </div>
          <div class="mt-4">
            <button
              type="button"
              class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50"
              :disabled="changeSubmitLoading || !changeSelectedSlotId"
              @click="submitChangeAppointment"
            >
              <span v-if="changeSubmitLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
              {{ $t('manualBooking.confirmChange', 'Confirm change') }}
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import manualBookingApi from '@/services/manualBooking'

const toast = useToast()
const activeTab = ref('new')

// —— New booking ——
const patientPhoneDigits = ref('')
const patientCountrySearch = ref('')
const showPatientCountryDropdown = ref(false)
const patientCountries = ref([])
const selectedPatientCountryCode = ref('EG')
const patientSearchResults = ref([])
const patientSearchLoading = ref(false)
const patientSearchDebounce = ref(null)
const patientId = ref(null)
const patientFirstName = ref('')
const patientLastName = ref('')
const therapists = ref([])
const therapistsLoading = ref(false)
const selectedTherapistId = ref('')
const availableDates = ref([])
const datesLoading = ref(false)
const selectedDate = ref('')
const slots = ref([])
const slotsLoading = ref(false)
const selectedSlotId = ref('')
const therapistCountries = ref([])
const countriesLoading = ref(false)
const selectedCountryCode = ref('')
const amountOverride = ref('')
const paymentMethod = ref('')
const submitLoading = ref(false)

const selectedPatientCountry = computed(() => {
  return patientCountries.value.find(c => c.country_code === selectedPatientCountryCode.value) || patientCountries.value[0]
})
const filteredPatientCountries = computed(() => {
  const q = patientCountrySearch.value.toLowerCase()
  if (!q) return patientCountries.value.slice(0, 50)
  return patientCountries.value.filter(c =>
    (c.name_en && c.name_en.toLowerCase().includes(q)) ||
    (c.name_ar && c.name_ar.toLowerCase().includes(q)) ||
    (c.dial_code && c.dial_code.includes(q)) ||
    (c.country_code && c.country_code.toLowerCase().includes(q))
  ).slice(0, 50)
})

function onPatientPhoneInput(e) {
  const v = (e.target?.value || '').replace(/\D/g, '')
  patientPhoneDigits.value = v
  if (patientSearchDebounce.value) clearTimeout(patientSearchDebounce.value)
  if (v.length < 2) {
    patientSearchResults.value = []
    return
  }
  patientSearchLoading.value = true
  patientSearchDebounce.value = setTimeout(async () => {
    try {
      const dial = selectedPatientCountry.value?.dial_code?.replace('+', '') || '20'
      const q = dial + v
      const data = await manualBookingApi.searchPatient(q)
      patientSearchResults.value = Array.isArray(data) ? data : []
    } catch (err) {
      patientSearchResults.value = []
      toast.error(err.response?.data?.error || 'Search failed')
    } finally {
      patientSearchLoading.value = false
    }
  }, 300)
}
function selectPatient(p) {
  patientId.value = p.id
  patientFirstName.value = p.first_name || ''
  patientLastName.value = p.last_name || ''
  patientSearchResults.value = []
}
function selectPatientCountry(c) {
  selectedPatientCountryCode.value = c.country_code
}

function onTherapistChange() {
  selectedDate.value = ''
  selectedSlotId.value = ''
  selectedCountryCode.value = ''
  availableDates.value = []
  slots.value = []
  therapistCountries.value = []
  if (!selectedTherapistId.value) return
  datesLoading.value = true
  countriesLoading.value = true
  Promise.all([
    manualBookingApi.getAvailableDates(selectedTherapistId.value),
    manualBookingApi.getTherapistCountries(selectedTherapistId.value)
  ]).then(([dates, countries]) => {
    availableDates.value = Array.isArray(dates) ? dates : []
    therapistCountries.value = Array.isArray(countries) ? countries : []
    if (therapistCountries.value.length) selectedCountryCode.value = therapistCountries.value[0].code
  }).catch(() => {
    toast.error('Failed to load dates or countries')
  }).finally(() => {
    datesLoading.value = false
    countriesLoading.value = false
  })
}
function onDateChange() {
  selectedSlotId.value = ''
  slots.value = []
  if (!selectedDate.value || !selectedTherapistId.value) return
  slotsLoading.value = true
  manualBookingApi.getSlots(selectedTherapistId.value, selectedDate.value).then(data => {
    slots.value = Array.isArray(data) ? data : []
  }).catch(() => toast.error('Failed to load slots')).finally(() => { slotsLoading.value = false })
}

async function submitNewBooking() {
  const payload = {
    mode: 'new',
    patient_id: patientId.value,
    therapist_id: selectedTherapistId.value,
    slot_id: selectedSlotId.value,
    country_code: selectedCountryCode.value,
    patient_first_name: patientFirstName.value.trim(),
    patient_last_name: patientLastName.value.trim(),
    payment_method: paymentMethod.value || ''
  }
  if (amountOverride.value && !isNaN(parseFloat(amountOverride.value))) {
    payload.amount = parseFloat(amountOverride.value)
  }
  submitLoading.value = true
  try {
    const result = await manualBookingApi.submit(payload)
    toast.success(result?.message || 'Booking created. Order #' + (result?.order_id || ''))
    patientId.value = null
    patientFirstName.value = ''
    patientLastName.value = ''
    patientPhoneDigits.value = ''
    selectedTherapistId.value = ''
    selectedDate.value = ''
    selectedSlotId.value = ''
    selectedCountryCode.value = ''
    amountOverride.value = ''
    paymentMethod.value = ''
    availableDates.value = []
    slots.value = []
    therapistCountries.value = []
  } catch (err) {
    toast.error(err.response?.data?.error || 'Booking failed')
  } finally {
    submitLoading.value = false
  }
}

// —— Change appointment ——
const changeSearchQuery = ref('')
const changeSearchResults = ref([])
const changeSearchLoading = ref(false)
const changeSearchDebounce = ref(null)
const selectedAppointment = ref(null)
const changeAvailableDates = ref([])
const changeSelectedDate = ref('')
const changeSlots = ref([])
const changeSelectedSlotId = ref('')
const changeSubmitLoading = ref(false)

function onChangeSearchInput() {
  if (changeSearchDebounce.value) clearTimeout(changeSearchDebounce.value)
  if (!changeSearchQuery.value.trim()) {
    changeSearchResults.value = []
    return
  }
  changeSearchLoading.value = true
  changeSearchDebounce.value = setTimeout(() => {
    manualBookingApi.searchAppointments(changeSearchQuery.value.trim()).then(data => {
      changeSearchResults.value = Array.isArray(data) ? data : []
    }).catch(() => {
      changeSearchResults.value = []
      toast.error('Search failed')
    }).finally(() => { changeSearchLoading.value = false })
  }, 400)
}
function selectAppointment(a) {
  selectedAppointment.value = a
  changeSelectedDate.value = ''
  changeSelectedSlotId.value = ''
  changeSlots.value = []
  if (!a.therapist_id) return
  manualBookingApi.getAvailableDates(a.therapist_id).then(data => {
    changeAvailableDates.value = Array.isArray(data) ? data : []
  })
}
function onChangeDateChange() {
  changeSelectedSlotId.value = ''
  changeSlots.value = []
  if (!changeSelectedDate.value || !selectedAppointment.value?.therapist_id) return
  manualBookingApi.getSlots(selectedAppointment.value.therapist_id, changeSelectedDate.value).then(data => {
    changeSlots.value = Array.isArray(data) ? data : []
  })
}
function formatDateTime(s) {
  if (!s) return '—'
  const d = new Date(s)
  return d.toLocaleString()
}
async function submitChangeAppointment() {
  if (!selectedAppointment.value || !changeSelectedSlotId.value) return
  changeSubmitLoading.value = true
  try {
    const result = await manualBookingApi.submit({
      mode: 'change',
      existing_booking_id: selectedAppointment.value.booking_id,
      slot_id: changeSelectedSlotId.value
    })
    toast.success(result?.message || 'Appointment changed')
    selectedAppointment.value = null
    changeSearchQuery.value = ''
    changeSearchResults.value = []
    changeAvailableDates.value = []
    changeSelectedDate.value = ''
    changeSlots.value = []
    changeSelectedSlotId.value = ''
  } catch (err) {
    toast.error(err.response?.data?.error || 'Change failed')
  } finally {
    changeSubmitLoading.value = false
  }
}

onMounted(async () => {
  therapistsLoading.value = true
  try {
    const data = await manualBookingApi.getTherapists()
    therapists.value = Array.isArray(data) ? data : []
  } catch (e) {
    toast.error('Failed to load therapists')
  } finally {
    therapistsLoading.value = false
  }
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
