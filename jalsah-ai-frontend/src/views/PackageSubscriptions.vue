<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">{{ $t('packages.subscriptionsTitle') }}</h1>

    <div class="mb-4 flex flex-wrap gap-2 items-center">
      <select v-model="statusFilter" class="rounded border border-gray-300 px-3 py-2 text-sm" @change="load">
        <option value="">{{ $t('packages.allStatuses') }}</option>
        <option value="active">{{ $t('packages.statusActive') }}</option>
        <option value="completed">{{ $t('packages.statusCompleted') }}</option>
        <option value="cancelled">{{ $t('packages.statusCancelled') }}</option>
      </select>
      <button type="button" class="px-3 py-2 bg-primary-500 text-white rounded text-sm" @click="load">{{ $t('manualBooking.search') }}</button>
    </div>

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
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.status') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.packagePrice') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.remainingSessions') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.subscribedAt') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('packages.cancelledAt') }}</th>
            <th class="px-3 py-2 text-xs font-medium text-gray-600">{{ $t('manualBooking.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in rows" :key="row.id">
            <td class="px-3 py-2 text-sm">{{ row.patient_name }}</td>
            <td class="px-3 py-2 text-sm">{{ row.patient_phone || '—' }}</td>
            <td class="px-3 py-2 text-sm">{{ row.package_type }}</td>
            <td class="px-3 py-2 text-sm">{{ statusLabel(row.status) }}</td>
            <td class="px-3 py-2 text-sm">{{ formatPrice(row.package_price) }}</td>
            <td class="px-3 py-2 text-sm">{{ row.remaining_sessions }}</td>
            <td class="px-3 py-2 text-sm">{{ row.subscribed_at || '—' }}</td>
            <td class="px-3 py-2 text-sm">{{ row.cancelled_at || '—' }}</td>
            <td class="px-3 py-2 text-sm">
              <button
                v-if="row.status === 'active'"
                type="button"
                class="px-2 py-1 text-xs rounded border border-red-400 text-red-600 hover:bg-red-50"
                @click="cancelSub(row)"
              >
                {{ $t('packages.cancelSubscription') }}
              </button>
              <span v-else class="text-gray-400">—</span>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && rows.length === 0" class="p-6 text-center text-gray-500">{{ $t('packages.noSubscriptions') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import Swal from 'sweetalert2'
import manualBookingApi from '@/services/manualBooking'

const { t } = useI18n()
const toast = useToast()
const loading = ref(false)
const rows = ref([])
const statusFilter = ref('')

function formatPrice(n) {
  if (n == null || typeof n !== 'number') return '—'
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(n)
}

function statusLabel(s) {
  if (s === 'active') return t('packages.statusActive')
  if (s === 'completed') return t('packages.statusCompleted')
  if (s === 'cancelled') return t('packages.statusCancelled')
  return s
}

async function load() {
  loading.value = true
  try {
    const data = await manualBookingApi.listPackageSubscriptions({
      status: statusFilter.value || undefined,
      per_page: 200
    })
    rows.value = data?.rows || []
  } catch (e) {
    toast.error(e?.response?.data?.error || t('packages.loadFailed'))
  } finally {
    loading.value = false
  }
}

async function cancelSub(row) {
  const conf = await Swal.fire({
    icon: 'warning',
    title: t('packages.cancelConfirmTitle'),
    text: t('packages.cancelConfirmText'),
    showCancelButton: true,
    confirmButtonText: t('packages.cancelSubscription'),
    cancelButtonText: t('common.cancel')
  })
  if (!conf.isConfirmed) return
  try {
    await manualBookingApi.cancelPackageSubscription(row.id)
    toast.success(t('packages.cancelSuccess'))
    load()
  } catch (e) {
    toast.error(e?.response?.data?.error || t('packages.cancelFailed'))
  }
}

onMounted(load)
</script>
