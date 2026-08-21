<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">{{ $t('packages.sessionsTitle') }}</h1>

    <div class="mb-4 flex flex-wrap gap-3 items-end">
      <div class="min-w-[200px]">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('packages.patientPhone') }}</label>
        <input v-model="phone" type="text" class="w-full rounded border border-gray-300 px-3 py-2" :placeholder="$t('manualBooking.phonePlaceholder')" />
      </div>
      <button
        type="button"
        class="inline-flex items-center justify-center w-10 h-10 rounded border"
        :class="connectActive ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-gray-600 border-gray-300'"
        :title="$t('packages.connectFilter')"
        @click="connectActive = !connectActive"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
      </button>
      <div class="min-w-[260px]">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('packages.dateRange') }}</label>
        <FlatPickr v-model="dateRange" :config="fpConfig" class="w-full rounded border border-gray-300 px-3 py-2" />
      </div>
      <button type="button" class="px-4 py-2 bg-primary-500 text-white rounded" @click="load">{{ $t('manualBooking.search') }}</button>
    </div>
    <p class="text-xs text-gray-500 mb-4">{{ $t('packages.sessionsFilterHint') }}</p>

    <div class="overflow-x-auto border rounded bg-white">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tableDateTime') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.patientName') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.patientPhone') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistName') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePackageCounter') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionPrice') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePaymentMethod') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in rows" :key="row.session_id">
            <td class="px-3 py-2 text-sm">{{ row.session_id }}</td>
            <td class="px-3 py-2 text-sm">{{ row.date_time }}</td>
            <td class="px-3 py-2 text-sm">{{ row.patient_name }}</td>
            <td class="px-3 py-2 text-sm">{{ row.patient_phone || '—' }}</td>
            <td class="px-3 py-2 text-sm">{{ row.therapist_name }}</td>
            <td class="px-3 py-2 text-sm">{{ row.package_counter || '—' }}</td>
            <td class="px-3 py-2 text-sm">{{ formatPrice(row.session_price) }}</td>
            <td class="px-3 py-2 text-sm">{{ row.payment_method }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && rows.length === 0" class="p-6 text-center text-gray-500">{{ $t('packages.noSessions') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import FlatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import manualBookingApi from '@/services/manualBooking'

const { t } = useI18n()
const toast = useToast()
const loading = ref(false)
const rows = ref([])
const phone = ref('')
const connectActive = ref(false)
const dateRange = ref('')

function defaultRange() {
  const to = new Date()
  const from = new Date()
  from.setMonth(from.getMonth() - 1)
  const fmt = (d) => {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${day}`
  }
  return `${fmt(from)} to ${fmt(to)}`
}
dateRange.value = defaultRange()

const fpConfig = computed(() => ({
  mode: 'range',
  dateFormat: 'Y-m-d'
}))

function formatPrice(n) {
  if (n == null || typeof n !== 'number') return '—'
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n)
}

function parseRange() {
  const raw = String(dateRange.value || '')
  const parts = raw.split(/\s+to\s+/i).map(s => s.trim()).filter(Boolean)
  return {
    date_from: parts[0] || '',
    date_to: parts[1] || parts[0] || ''
  }
}

async function load() {
  loading.value = true
  try {
    const range = parseRange()
    const data = await manualBookingApi.listPackageSessions({
      phone: phone.value || '',
      connect_active: connectActive.value ? 1 : 0,
      date_from: range.date_from,
      date_to: range.date_to,
      per_page: 200
    })
    rows.value = data?.rows || []
  } catch (e) {
    toast.error(e?.response?.data?.error || t('packages.loadFailed'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
