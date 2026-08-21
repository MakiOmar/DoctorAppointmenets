<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">{{ $t('packages.oweTitle') }}</h1>
    <p class="text-sm text-gray-600 mb-4">{{ $t('packages.oweHint') }}</p>

    <div class="overflow-x-auto border rounded bg-white">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.patientName') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.patientPhone') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.packageType') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.remainingSessions') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.amountPaid') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.amountRemaining') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in rows" :key="row.id">
            <td class="px-3 py-2 text-sm">{{ row.patient_name }}</td>
            <td class="px-3 py-2 text-sm">{{ row.patient_phone || '—' }}</td>
            <td class="px-3 py-2 text-sm">{{ row.package_type }}</td>
            <td class="px-3 py-2 text-sm">{{ row.remaining_sessions }}</td>
            <td class="px-3 py-2 text-sm">{{ formatPrice(row.amount_paid) }}</td>
            <td class="px-3 py-2 text-sm font-medium">{{ formatPrice(row.amount_remaining) }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && rows.length === 0" class="p-6 text-center text-gray-500">{{ $t('packages.noOwe') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import manualBookingApi from '@/services/manualBooking'

const { t } = useI18n()
const toast = useToast()
const loading = ref(false)
const rows = ref([])

function formatPrice(n) {
  if (n == null || typeof n !== 'number') return '—'
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n)
}

async function load() {
  loading.value = true
  try {
    const data = await manualBookingApi.getPackageOweReport()
    rows.value = data?.rows || []
  } catch (e) {
    toast.error(e?.response?.data?.error || t('packages.loadFailed'))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
