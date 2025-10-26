<template>
  <div v-if="prescriptionRequests.length > 0 || completedPrescriptions.length > 0" class="mb-8">
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
              <span class="text-blue-800 text-sm font-medium">{{ t('prescription.prescriptionRequested').replace('{name}', request.therapist_name) || 'Prescription Requested' }}</span>
            </div>
          </div>
          
          <!-- Status and Actions -->
          <div class="flex items-center justify-between">
            <div class="text-sm">
              <div v-if="request.status === 'confirmed' && request.booking_date && request.booking_date !== '0000-00-00'" class="mb-2">
                <div class="text-gray-700 font-medium">
                  📅 {{ formatDate(request.booking_date) }}
                </div>
                <div class="text-gray-700 font-medium">
                  🕐 {{ formatTime(request.booking_time) }}
                </div>
              </div>
              <div v-if="request.status === 'pending'">
                <span class="text-orange-600 font-medium">
                  {{ t('prescription.pending') || 'Pending' }}
                </span>
                <div class="text-xs text-red-600 mt-1" v-if="request.days_until_expiry !== undefined && request.days_until_expiry >= 0">
                  ⚠️ {{ t('prescription.expiryWarning') || 'يجب إتمام الحجز قبل' }} {{ formatDate(request.expiry_date) }}
                </div>
              </div>
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
                v-if="request.status === 'confirmed'"
                @click="$emit('join-meeting', request.id)"
                :disabled="!canJoinRochtahSession(request)"
                :class="[
                  'text-sm px-4 py-2 rounded-lg font-medium transition-colors',
                  canJoinRochtahSession(request)
                    ? 'btn-success'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed border border-gray-300'
                ]"
              >
                <span v-if="canJoinRochtahSession(request)">
                  {{ t('prescription.joinMeeting') || 'Join Meeting' }}
                </span>
                <span v-else class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ getRochtahStatusMessage(request) }}
                </span>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Completed Prescriptions Section -->
        <div v-if="completedPrescriptions.length > 0" class="mt-6">
          <h4 class="text-md font-semibold text-blue-900 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ t('prescription.completedPrescriptions') || 'My Prescriptions' }}
          </h4>
          
          <div class="space-y-3">
            <div 
              v-for="prescription in completedPrescriptions" 
              :key="prescription.id"
              class="bg-green-50 rounded-lg p-4 border border-green-200 shadow-sm hover:shadow-md transition-shadow"
            >
              <!-- Prescription Header -->
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                  <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                  <span class="text-green-800 text-sm font-medium">{{ t('prescription.viewPrescription') || 'Prescription Ready' }}</span>
                </div>
              </div>
              
              <!-- Prescription Details -->
              <div class="grid grid-cols-1 gap-3 mb-4 text-sm">
                <div>
                  <span class="font-medium text-gray-700">{{ t('prescription.prescribedBy') }}:</span>
                  <span class="text-gray-900">{{ prescription.prescribed_by_name }}</span>
                </div>
              </div>
              
              <!-- Action Button -->
              <div class="flex justify-end">
                <button 
                  @click="$emit('view-prescription', prescription)"
                  class="btn-primary text-sm px-4 py-2"
                >
                  {{ t('prescription.viewPrescription') }}
                </button>
              </div>
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
import { computed } from 'vue'

export default {
  name: 'PrescriptionCard',
  props: {
    prescriptionRequests: {
      type: Array,
      default: () => []
    },
    completedPrescriptions: {
      type: Array,
      default: () => []
    }
  },
  emits: ['book-appointment', 'view-appointment', 'join-meeting', 'view-prescription'],
  setup() {
    const { t, locale } = useI18n()
    
    const formatDateWithLocale = (dateString) => {
      return formatDate(dateString, locale.value)
    }
    
    const formatTimeWithLocale = (timeString) => {
      return formatTime(timeString, locale.value)
    }
    
    // Check if rochtah session can be joined (at scheduled time AND doctor has joined)
    const canJoinRochtahSession = (request) => {
      if (!request.booking_date || !request.booking_time || request.booking_date === '0000-00-00') {
        return false
      }
      
      // Parse booking date and time
      const bookingDateTime = new Date(`${request.booking_date}T${request.booking_time}`)
      const now = new Date()
      
      // Check if session time has arrived
      const timeHasArrived = now >= bookingDateTime
      
      // Check if doctor has joined (if doctor_joined field exists)
      const doctorJoined = request.doctor_joined !== undefined ? request.doctor_joined : false
      
      // Allow joining only when both conditions are met
      return timeHasArrived && doctorJoined
    }
    
    // Get status message based on session state
    const getRochtahStatusMessage = (request) => {
      if (!request.booking_date || !request.booking_time || request.booking_date === '0000-00-00') {
        return t('prescription.waitingForSession') || 'Waiting for session to start'
      }
      
      // Parse booking date and time
      const bookingDateTime = new Date(`${request.booking_date}T${request.booking_time}`)
      const now = new Date()
      
      // Check if session time has arrived
      const timeHasArrived = now >= bookingDateTime
      
      // Check if doctor has joined
      const doctorJoined = request.doctor_joined !== undefined ? request.doctor_joined : false
      
      if (!timeHasArrived) {
        return t('prescription.sessionNotStarted') || 'Session hasn\'t started yet'
      }
      
      if (!doctorJoined) {
        return t('prescription.waitingForDoctor') || 'Waiting for doctor to join'
      }
      
      return t('prescription.waitingForSession') || 'Waiting for session to start'
    }
    
    return {
      t,
      formatDate: formatDateWithLocale,
      formatTime: formatTimeWithLocale,
      canJoinRochtahSession,
      getRochtahStatusMessage
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

.btn-success {
  @apply bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors;
}
</style>
