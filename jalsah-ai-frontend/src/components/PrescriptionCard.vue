<template>
  <div v-if="prescriptionRequests.length > 0" class="mb-8">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
      <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        {{ t('prescription.prescriptionServices') || 'Prescription Services' }}
      </h3>
      
      <div class="space-y-4">
        <div 
          v-for="request in prescriptionRequests" 
          :key="request.id"
          class="bg-white rounded-lg p-4 border border-blue-100 shadow-sm hover:shadow-md transition-shadow"
        >
          <!-- Prescription Request Header -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center">
              <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                              <span class="text-blue-800 text-sm font-medium">{{ t('prescription.prescriptionRequested') || 'Prescription Requested' }}</span>
            </div>
            <div class="text-xs text-gray-500">
              {{ formatDate(request.created_at) }}
            </div>
          </div>
          
          <!-- Session Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4 text-sm">
            <div>
              <span class="font-medium text-gray-700">{{ t('appointmentsPage.therapist') }}:</span>
              <span class="text-gray-900">{{ request.therapist_name }}</span>
            </div>
            <div>
              <span class="font-medium text-gray-700">{{ t('appointmentsPage.session') }}:</span>
              <span class="text-gray-900">{{ formatDate(request.date_time) }} - {{ formatTime(request.starts) }}</span>
            </div>
          </div>
          
          <!-- Status and Actions -->
          <div class="flex items-center justify-between">
            <div class="text-sm">
              <span v-if="request.status === 'pending'" class="text-orange-600 font-medium">
                {{ t('prescription.pending') || 'Pending' }}
              </span>
              <span v-else-if="request.status === 'confirmed'" class="text-green-600 font-medium">
                {{ t('prescription.confirmed') }}
              </span>
              <span v-else class="text-gray-600">
                {{ request.status }}
              </span>
            </div>
            
            <div class="flex space-x-2">
              <button 
                v-if="request.status === 'pending'"
                @click="$emit('book-appointment', request.id)"
                class="btn-primary text-sm px-4 py-2"
              >
                {{ t('prescription.bookFreeAppointment') || 'Book Free Appointment' }}
              </button>
              <button 
                v-else-if="request.status === 'confirmed'"
                @click="$emit('view-appointment', request.id)"
                class="btn-secondary text-sm px-4 py-2"
              >
                {{ t('prescription.viewAppointment') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDate, formatTime } from '@/utils/dateUtils'
import { useI18n } from 'vue-i18n'

export default {
  name: 'PrescriptionCard',
  props: {
    prescriptionRequests: {
      type: Array,
      default: () => []
    }
  },
  emits: ['book-appointment', 'view-appointment'],
  setup() {
    const { t, locale } = useI18n()
    
    const formatDateWithLocale = (dateString) => {
      return formatDate(dateString, locale.value)
    }
    
    const formatTimeWithLocale = (timeString) => {
      return formatTime(timeString, locale.value)
    }
    
    return {
      t,
      formatDate: formatDateWithLocale,
      formatTime: formatTimeWithLocale
    }
  }
}
</script>

<style scoped>
.btn-primary {
  @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors;
}

.btn-secondary {
  @apply bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors;
}
</style>
