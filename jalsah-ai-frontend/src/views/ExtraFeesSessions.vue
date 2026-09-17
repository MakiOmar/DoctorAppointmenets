<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-2">{{ $t('packages.extraFeesTitle') }}</h1>
    <p class="text-sm text-gray-600 mb-6">{{ $t('packages.extraFeesHint') }}</p>

    <div class="mb-4 flex flex-wrap gap-3 items-end">
      <div class="min-w-[260px]">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('packages.dateRange') }}</label>
        <FlatPickr v-model="dateRange" :config="fpConfig" class="w-full rounded border border-gray-300 px-3 py-2" />
      </div>
      <button type="button" class="px-4 py-2 bg-primary-500 text-white rounded" @click="load">{{ $t('manualBooking.search') }}</button>
    </div>

    <div class="overflow-x-auto border rounded bg-white">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.therapistPrice') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.extraFees') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in rows" :key="row.session_id">
            <td class="px-3 py-2 text-sm">{{ row.session_id }}</td>
            <td class="px-3 py-2 text-sm">{{ formatPrice(row.therapist_price) }}</td>
            <td class="px-3 py-2 text-sm">{{ formatPrice(row.extra_fees) }}</td>
          </tr>
        </tbody>
        <tfoot v-if="rows.length" class="bg-gray-50 font-semibold">
          <tr>
            <td class="px-3 py-2 text-sm" colspan="2">{{ $t('packages.totalExtraFees') }}</td>
            <td class="px-3 py-2 text-sm">{{ formatPrice(totalExtraFees) }}</td>
          </tr>
        </tfoot>
      </table>
      <p v-if="!loading && rows.length === 0" class="p-6 text-center text-gray-500">{{ $t('packages.noExtraFees') }}</p>
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
const totalExtraFees = ref(0)
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
    const data = await manualBookingApi.listExtraFeesSessions({
      date_from: range.date_from,
      date_to: range.date_to,
      per_page: 500
    })
    rows.value = data?.rows || []
    totalExtraFees.value = typeof data?.total_extra_fees === 'number' ? data.total_extra_fees : 0
  } catch (e) {
    toast.error(e?.response?.data?.error || t('packages.loadFailed'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
