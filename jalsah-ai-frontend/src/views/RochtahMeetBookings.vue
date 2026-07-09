<template>
  <!-- Rochetah Google Meet bookings list / management -->
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8 max-w-6xl">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <h1 class="text-2xl font-semibold text-primary-500">
        {{ $t('rochtahMeetManage.title') }}
      </h1>
      <router-link
        to="/rochtah-meet-booking"
        class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-primary-500 text-white text-sm font-medium hover:bg-primary-600"
      >
        {{ $t('rochtahMeetManage.newBooking') }}
      </router-link>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row flex-wrap gap-3 mb-4">
      <div class="flex-1 min-w-[200px]">
        <input
          v-model="searchQuery"
          type="text"
          class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
          :placeholder="$t('rochtahMeetManage.searchPlaceholder')"
          @keydown.enter.prevent="loadBookings(1)"
        />
      </div>
      <select v-model="statusFilter" class="rounded border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ $t('rochtahMeetManage.allStatuses') }}</option>
        <option value="scheduled">{{ $t('rochtahMeetManage.statusScheduled') }}</option>
        <option value="completed">{{ $t('rochtahMeetManage.statusCompleted') }}</option>
        <option value="cancelled">{{ $t('rochtahMeetManage.statusCancelled') }}</option>
      </select>
      <button
        type="button"
        class="px-4 py-2 rounded-md bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 disabled:opacity-50"
        :disabled="loading"
        @click="loadBookings(1)"
      >
        {{ $t('rochtahMeetManage.search') }}
      </button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto border rounded bg-white">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
        <p class="mt-2">{{ $t('common.loading') }}</p>
      </div>
      <table v-else class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-start font-medium text-gray-700">#</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.patient') }}</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.doctor') }}</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.datetime') }}</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.diagnosis') }}</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.meetUrl') }}</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.status') }}</th>
            <th class="px-3 py-2 text-start font-medium text-gray-700">{{ $t('rochtahMeetManage.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="row in bookings" :key="row.id">
            <td class="px-3 py-2 whitespace-nowrap">{{ row.id }}</td>
            <td class="px-3 py-2">
              <div>{{ row.patient_name }}</div>
              <div v-if="row.patient_phone" class="text-xs text-gray-500">{{ row.patient_phone }}</div>
            </td>
            <td class="px-3 py-2 whitespace-nowrap">{{ row.rochtah_doctor_name }}</td>
            <td class="px-3 py-2 whitespace-nowrap">{{ formatDateTime(row.appointment_datetime) }}</td>
            <td class="px-3 py-2 max-w-[180px]">
              <span v-if="row.diagnosis_name">{{ row.diagnosis_name }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="px-3 py-2">
              <button
                v-if="row.meet_url"
                type="button"
                class="px-2 py-1 rounded border border-gray-300 text-xs hover:bg-gray-100"
                @click="copyText(row.meet_url)"
              >
                {{ $t('rochtahMeetManage.copyMeetUrl') }}
              </button>
              <span v-else>—</span>
            </td>
            <td class="px-3 py-2">
              <span :class="statusClass(row.status)" class="px-2 py-0.5 rounded text-xs font-medium">
                {{ statusLabel(row.status) }}
              </span>
            </td>
            <td class="px-3 py-2 whitespace-nowrap">
              <button
                v-if="row.status === 'scheduled'"
                type="button"
                class="px-2 py-1 rounded border border-red-500 text-red-600 text-xs hover:bg-red-50 disabled:opacity-50"
                :disabled="actionLoadingId === row.id"
                @click="cancelBooking(row)"
              >
                {{ $t('rochtahMeetManage.cancel') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && bookings.length === 0" class="p-6 text-center text-gray-500">
        {{ $t('rochtahMeetManage.noBookings') }}
      </p>

      <!-- Pagination -->
      <div v-if="total > perPage" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
        <span class="text-sm text-gray-600">
          {{ $t('rochtahMeetManage.showing') }}
          {{ (page - 1) * perPage + 1 }}-{{ Math.min(page * perPage, total) }}
          {{ $t('rochtahMeetManage.of') }} {{ total }}
        </span>
        <div class="flex gap-2">
          <button
            type="button"
            class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50"
            :disabled="page <= 1"
            @click="loadBookings(page - 1)"
          >
            {{ $t('common.previous') }}
          </button>
          <button
            type="button"
            class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50"
            :disabled="page >= totalPages"
            @click="loadBookings(page + 1)"
          >
            {{ $t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Swal from 'sweetalert2'
import { useToast } from 'vue-toastification'
import rochtahMeetApi from '@/services/rochtahMeet'

const { t } = useI18n()
const toast = useToast()

const bookings = ref([])
const loading = ref(false)
const actionLoadingId = ref(null)
const page = ref(1)
const perPage = ref(20)
const total = ref(0)
const searchQuery = ref('')
const statusFilter = ref('')

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))

function formatDateTime(value) {
  if (!value) return '—'
  const d = new Date(value.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleString()
}

function statusLabel(status) {
  const map = {
    scheduled: t('rochtahMeetManage.statusScheduled'),
    completed: t('rochtahMeetManage.statusCompleted'),
    cancelled: t('rochtahMeetManage.statusCancelled')
  }
  return map[status] || status
}

function statusClass(status) {
  if (status === 'scheduled') return 'bg-blue-100 text-blue-800'
  if (status === 'completed') return 'bg-green-100 text-green-800'
  if (status === 'cancelled') return 'bg-gray-100 text-gray-600'
  return 'bg-gray-100 text-gray-700'
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text)
    toast.success(t('rochtahMeetManage.copied'))
  } catch (_) {
    toast.error(t('rochtahMeetManage.copyFailed'))
  }
}

async function loadBookings(nextPage = 1) {
  page.value = nextPage
  loading.value = true
  try {
    const params = {
      page: page.value,
      per_page: perPage.value
    }
    if (statusFilter.value) params.status = statusFilter.value
    if (searchQuery.value.trim()) params.q = searchQuery.value.trim()
    const data = await rochtahMeetApi.listBookings(params)
    bookings.value = Array.isArray(data?.rows) ? data.rows : []
    total.value = Number(data?.total) || 0
  } catch (err) {
    bookings.value = []
    total.value = 0
    toast.error(err.response?.data?.error || t('rochtahMeetManage.loadFailed'))
  } finally {
    loading.value = false
  }
}

async function cancelBooking(row) {
  const confirm = await Swal.fire({
    icon: 'warning',
    title: t('rochtahMeetManage.confirmCancelTitle'),
    text: t('rochtahMeetManage.confirmCancelText'),
    showCancelButton: true,
    confirmButtonText: t('common.confirm'),
    cancelButtonText: t('common.cancel')
  })
  if (!confirm.isConfirmed) return

  actionLoadingId.value = row.id
  try {
    await rochtahMeetApi.updateStatus(row.id, 'cancelled')
    toast.success(t('rochtahMeetManage.statusUpdated'))
    await loadBookings(page.value)
  } catch (err) {
    toast.error(err.response?.data?.error || t('rochtahMeetManage.updateFailed'))
  } finally {
    actionLoadingId.value = null
  }
}

watch(statusFilter, () => {
  loadBookings(1)
})

onMounted(() => {
  loadBookings(1)
})
</script>
