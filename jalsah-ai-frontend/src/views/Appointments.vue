<template>
  <div>

    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
              <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ $t('appointmentsPage.title') }}</h1>

      <!-- Filter Tabs -->
      <div class="mb-8">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              {{ tab.name }}
              <span 
                v-if="tab.count !== undefined"
                :class="[
                  activeTab === tab.id ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-900',
                  'ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">{{ $t('appointmentsPage.loading') }}</p>
      </div>

      <!-- Appointments List -->
      <div v-else-if="filteredAppointments.length > 0" class="space-y-6">
        <div 
          v-for="appointment in filteredAppointments" 
          :key="appointment.id"
          class="card"
        >
          <div class="md:flex md:items-center md:justify-between">
            <!-- Appointment Info -->
            <div class="md:flex md:items-center md:space-x-6">
              <!-- Therapist Image -->
                              <img 
                  :src="appointment.therapist?.photo || '/default-therapist.svg'" 
                  :alt="appointment.therapist?.name"
                  class="w-16 h-16 rounded-full mb-4 md:mb-0"
                  :class="appointment.therapist?.photo ? 'object-cover' : 'object-contain bg-gray-100 p-1'"
                />

              <!-- Details -->
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  {{ appointment.therapist?.name }}
                </h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-600">
                  <div>
                    <span class="font-medium">{{ $t('appointments.date') }}:</span> {{ formatDate(appointment.date) }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointments.time') }}:</span> {{ formatTime(appointment.time) }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointments.duration') }}:</span> {{ appointment.session_type }} minutes
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointments.status') }}:</span> 
                    <span :class="getStatusClass(appointment.status)">
                      {{ getStatusText(appointment.status) }}
                    </span>
                  </div>
                </div>
                
                <!-- Notes -->
                <div v-if="appointment.notes" class="mt-3">
                  <span class="font-medium text-gray-900">{{ $t('appointments.notes') }}:</span>
                  <p class="text-gray-600 text-sm mt-1">{{ appointment.notes }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-4 md:mt-0 md:ml-6 flex flex-col space-y-2">
              <!-- Join Session Button -->
              <button 
                v-if="canJoinSession(appointment)"
                @click="joinSession(appointment.id)"
                class="btn-primary text-sm"
              >
                {{ $t('appointments.joinSession') }}
              </button>

              <!-- Reschedule Button -->
              <button 
                v-if="canReschedule(appointment)"
                @click="rescheduleAppointment(appointment.id)"
                class="btn-outline text-sm"
              >
                {{ $t('appointments.reschedule') }}
              </button>

              <!-- Cancel Button -->
              <button 
                v-if="canCancel(appointment)"
                @click="cancelAppointment(appointment.id)"
                class="btn-outline text-sm text-red-600 hover:text-red-700"
              >
                {{ $t('appointments.cancel') }}
              </button>

              <!-- View Details -->
              <button 
                @click="viewAppointmentDetails(appointment.id)"
                class="btn-outline text-sm"
              >
                {{ $t('appointments.viewDetails') }}
              </button>
            </div>
          </div>

          <!-- Session Link (if available) -->
          <div v-if="appointment.session_link" class="mt-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-blue-900">{{ $t('appointments.sessionLinkAvailable') }}</p>
                <p class="text-xs text-blue-700">{{ $t('appointments.sessionLinkMessage') }}</p>
              </div>
              <a 
                :href="appointment.session_link" 
                target="_blank"
                class="btn-primary text-sm"
              >
                {{ $t('appointments.joinNow') }}
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('appointments.noAppointments') }}</h3>
        <p class="text-gray-600 mb-6">
          {{ activeTab === 'upcoming' ? $t('appointments.noUpcoming') : 
             activeTab === 'past' ? $t('appointments.noPast') : 
             $t('appointments.noCancelled') }}
        </p>
        <button 
          @click="$router.push('/therapists')"
          class="btn-primary"
        >
          {{ $t('appointments.bookSession') }}
        </button>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('appointments.cancelTitle') }}</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ $t('appointments.cancelMessage') }}
          </p>
          <div class="flex justify-center space-x-4">
            <button 
              @click="confirmCancel"
              :disabled="cancelling"
              class="btn-primary"
            >
              <span v-if="cancelling" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('appointments.cancelling') }}
              </span>
              <span v-else>{{ $t('appointments.yesCancel') }}</span>
            </button>
            <button 
              @click="showCancelModal = false"
              class="btn-outline"
            >
              {{ $t('appointments.noKeep') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
export default {
  name: 'Appointments',
  setup() {
    const router = useRouter()
    const toast = useToast()
    const { t: $t, locale } = useI18n()
    
    const loading = ref(true)
    const cancelling = ref(false)
    const showCancelModal = ref(false)
    const appointments = ref([])
    const activeTab = ref('upcoming')
    const appointmentToCancel = ref(null)

    const tabs = computed(() => [
      { 
        id: 'upcoming', 
        name: $t('appointmentsPage.tabs.upcoming'), 
        count: appointments.value.filter(a => a.status === 'confirmed' || a.status === 'pending').length 
      },
      { 
        id: 'past', 
        name: $t('appointmentsPage.tabs.past'), 
        count: appointments.value.filter(a => a.status === 'completed').length 
      },
      { 
        id: 'cancelled', 
        name: $t('appointmentsPage.tabs.cancelled'), 
        count: appointments.value.filter(a => a.status === 'cancelled').length 
      }
    ])

    const filteredAppointments = computed(() => {
      switch (activeTab.value) {
        case 'upcoming':
          return appointments.value.filter(a => a.status === 'confirmed' || a.status === 'pending')
        case 'past':
          return appointments.value.filter(a => a.status === 'completed')
        case 'cancelled':
          return appointments.value.filter(a => a.status === 'cancelled')
        default:
          return appointments.value
      }
    })

    const loadAppointments = async () => {
      loading.value = true
      try {
        console.log('Appointments Debug: Loading appointments')
        const response = await api.get('/api/ai/appointments')
        console.log('Appointments Debug: Appointments response:', response)
        appointments.value = response.data.data || []
        console.log('Appointments Debug: Appointments loaded:', appointments.value)
      } catch (error) {
        toast.error('Failed to load appointments')
        console.error('Appointments Debug: Error loading appointments:', error)
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const currentLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US'
      return new Date(dateString).toLocaleDateString(currentLocale, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatTime = (timeString) => {
      if (!timeString) return 'N/A'
      const [hours, minutes] = timeString.split(':')
      const hour = parseInt(hours)
      const ampm = hour >= 12 ? $t('dateTime.pm') : $t('dateTime.am')
      const displayHour = hour % 12 || 12
      return `${displayHour}:${minutes} ${ampm}`
    }

    const getStatusText = (status) => {
      const statusMap = {
        'pending': 'Pending',
        'confirmed': 'Confirmed',
        'completed': 'Completed',
        'cancelled': 'Cancelled',
        'no_show': 'No Show'
      }
      return statusMap[status] || status
    }

    const getStatusClass = (status) => {
      const classMap = {
        'pending': 'text-yellow-600 bg-yellow-100',
        'confirmed': 'text-green-600 bg-green-100',
        'completed': 'text-blue-600 bg-blue-100',
        'cancelled': 'text-red-600 bg-red-100',
        'no_show': 'text-gray-600 bg-gray-100'
      }
      return `px-2 py-1 rounded-full text-xs font-medium ${classMap[status] || ''}`
    }

    const canJoinSession = (appointment) => {
      if (appointment.status !== 'confirmed') return false
      
      const appointmentTime = new Date(`${appointment.date}T${appointment.time}`)
      const now = new Date()
      const timeDiff = appointmentTime - now
      
      // Can join 5 minutes before and up to 15 minutes after
      return timeDiff >= -5 * 60 * 1000 && timeDiff <= 15 * 60 * 1000
    }

    const canReschedule = (appointment) => {
      if (appointment.status !== 'confirmed' && appointment.status !== 'pending') return false
      
      const appointmentTime = new Date(`${appointment.date}T${appointment.time}`)
      const now = new Date()
      
      // Can reschedule up to 24 hours before
      return appointmentTime - now > 24 * 60 * 60 * 1000
    }

    const canCancel = (appointment) => {
      if (appointment.status !== 'confirmed' && appointment.status !== 'pending') return false
      
      const appointmentTime = new Date(`${appointment.date}T${appointment.time}`)
      const now = new Date()
      
      // Can cancel up to 24 hours before
      return appointmentTime - now > 24 * 60 * 60 * 1000
    }

    const joinSession = (appointmentId) => {
      // This would typically redirect to the video call platform
      toast.info('Redirecting to session...')
      // router.push(`/session/${appointmentId}`)
    }

    const rescheduleAppointment = (appointmentId) => {
      router.push(`/booking/reschedule/${appointmentId}`)
    }

    const cancelAppointment = (appointmentId) => {
      appointmentToCancel.value = appointmentId
      showCancelModal.value = true
    }

    const confirmCancel = async () => {
      if (!appointmentToCancel.value) return
      
      cancelling.value = true
      
      try {
        await api.put(`/api/ai/appointments/${appointmentToCancel.value}/cancel`)
        
        toast.success('Appointment cancelled successfully')
        
        // Reload appointments
        await loadAppointments()
        
        showCancelModal.value = false
        appointmentToCancel.value = null
        
      } catch (error) {
        toast.error('Failed to cancel appointment')
        console.error('Error cancelling appointment:', error)
      } finally {
        cancelling.value = false
      }
    }

    const viewAppointmentDetails = (appointmentId) => {
      router.push(`/appointments/${appointmentId}`)
    }

    onMounted(() => {
      loadAppointments()
    })

    return {
      loading,
      cancelling,
      showCancelModal,
      appointments,
      activeTab,
      tabs,
      filteredAppointments,
      formatDate,
      formatTime,
      getStatusText,
      getStatusClass,
      canJoinSession,
      canReschedule,
      canCancel,
      joinSession,
      rescheduleAppointment,
      cancelAppointment,
      confirmCancel,
      viewAppointmentDetails
    }
  }
}
</script> 