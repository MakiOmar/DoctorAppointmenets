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

      <!-- Prescription Requests Section -->
      <div v-if="prescriptionRequests.length > 0" class="mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-blue-900 mb-4">{{ $t('prescription.prescriptionServices') }}</h3>
          
          <div v-for="request in prescriptionRequests" :key="request.id" class="mb-4 last:mb-0">
            <div class="bg-white rounded-lg p-4 border border-blue-100">
              <div class="mb-3">
                <p class="text-blue-800 text-sm">{{ $t('prescription.prescriptionRequested') }}</p>
              </div>
              
              <div class="flex justify-end">
                <button 
                  @click="showRochtahBookingModal(request.id)"
                  class="btn-primary text-sm"
                >
                  {{ $t('prescription.bookFreeAppointment') }}
                </button>
              </div>
            </div>
          </div>
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
                    <span class="font-medium">{{ $t('appointmentsPage.date') }}:</span> {{ formatDate(appointment.date) }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.time') }}:</span> {{ formatTime(appointment.time) }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.duration') }}:</span> {{ appointment.session_type }} {{ $t('appointmentsPage.minutes') }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.status') }}:</span> 
                    <span :class="getStatusClass(appointment.status)">
                      {{ getStatusText(appointment.status) }}
                    </span>
                  </div>
                </div>
                
                <!-- Notes -->
                <div v-if="appointment.notes" class="mt-3">
                  <span class="font-medium text-gray-900">{{ $t('appointmentsPage.notes') }}:</span>
                  <p class="text-gray-600 text-sm mt-1">{{ appointment.notes }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-4 md:mt-0 md:ml-6 flex flex-col space-y-2">
              <!-- For Upcoming Sessions -->
              <template v-if="activeTab === 'upcoming'">
                <!-- Join Session Button -->
                <button 
                  v-if="canJoinSession(appointment)"
                  @click="joinSession(appointment.id)"
                  class="btn-primary text-sm"
                >
                  {{ $t('appointmentsPage.joinSession') }}
                </button>

                <!-- Reschedule Button -->
                <button 
                  v-if="canReschedule(appointment)"
                  @click="rescheduleAppointment(appointment.id)"
                  class="btn-outline text-sm"
                >
                  {{ $t('appointmentsPage.reschedule') }}
                </button>

                <!-- Cancel Button -->
                <button 
                  v-if="canCancel(appointment)"
                  @click="cancelAppointment(appointment.id)"
                  class="btn-outline text-sm text-red-600 hover:text-red-700"
                >
                  {{ $t('appointmentsPage.cancel') }}
                </button>
              </template>

              <!-- For Past Sessions -->
              <template v-if="activeTab === 'past'">
                <!-- Book a new appointment with the same therapist -->
                <button 
                  @click="bookWithSameTherapist(appointment)"
                  class="btn-primary text-sm"
                >
                  {{ $t('appointmentsPage.bookWithSameTherapist') }}
                </button>
              </template>
            </div>
          </div>

          <!-- Session Link (if available) -->
          <div v-if="appointment.session_link" class="mt-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-blue-900">{{ $t('appointmentsPage.sessionLinkAvailable') }}</p>
                <p class="text-xs text-blue-700">{{ $t('appointmentsPage.sessionLinkMessage') }}</p>
              </div>
              <a 
                :href="appointment.session_link" 
                target="_blank"
                class="btn-primary text-sm"
              >
                {{ $t('appointmentsPage.joinNow') }}
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
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('appointmentsPage.noAppointments') }}</h3>
        <p class="text-gray-600 mb-6">
          {{ activeTab === 'upcoming' ? $t('appointmentsPage.noUpcoming') : 
             activeTab === 'past' ? $t('appointmentsPage.noPast') : 
             $t('appointmentsPage.noCancelled') }}
        </p>
        <button 
          @click="$router.push('/therapists')"
          class="btn-primary"
        >
          {{ $t('appointmentsPage.bookSession') }}
        </button>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('appointmentsPage.cancelTitle') }}</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ $t('appointmentsPage.cancelMessage') }}
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
                {{ $t('appointmentsPage.cancelling') }}
              </span>
              <span v-else>{{ $t('appointmentsPage.yesCancel') }}</span>
            </button>
            <button 
              @click="showCancelModal = false"
              class="btn-outline"
            >
              {{ $t('appointmentsPage.noKeep') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rochtah Booking Modal -->
    <div v-if="showRochtahModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">{{ $t('prescription.bookFreeAppointment') }}</h3>
          <button @click="closeRochtahModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loadingSlots" class="text-center py-8">
          <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600">{{ $t('appointmentsPage.loading') }}</p>
        </div>

        <!-- Available Slots -->
        <div v-else-if="availableSlots.length > 0" class="max-h-96 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div 
              v-for="slot in availableSlots" 
              :key="`${slot.date}-${slot.time}`"
              @click="selectSlot(slot)"
              :class="[
                'p-3 border rounded-lg cursor-pointer transition-colors',
                selectedSlot && selectedSlot.date === slot.date && selectedSlot.time === slot.time
                  ? 'border-primary-500 bg-primary-50'
                  : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50'
              ]"
            >
              <div class="font-medium text-gray-900">{{ formatDate(slot.date) }}</div>
              <div class="text-sm text-gray-600">{{ slot.formatted_time }}</div>
            </div>
          </div>
        </div>

        <!-- No Slots Available -->
        <div v-else class="text-center py-8">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <p class="text-gray-600">{{ $t('prescription.noAvailableSlots') }}</p>
        </div>

        <!-- Action Buttons -->
        <div v-if="availableSlots.length > 0" class="flex justify-end space-x-3 mt-6 pt-4 border-t">
          <button 
            @click="closeRochtahModal"
            class="btn-outline"
          >
            {{ $t('common.cancel') }}
          </button>
          <button 
            @click="bookRochtahAppointment"
            :disabled="!selectedSlot || bookingRochtah"
            class="btn-primary"
          >
            <span v-if="bookingRochtah" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('appointmentsPage.booking') }}
            </span>
            <span v-else>{{ $t('prescription.bookAppointment') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Booking Confirmation Modal -->
    <div v-if="showBookingConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('prescription.confirmBooking') }}</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ $t('prescription.confirmBookingMessage') }}
          </p>
          <div class="flex justify-center space-x-4">
            <button 
              @click="confirmRochtahBooking"
              :disabled="bookingRochtah"
              class="btn-primary"
            >
              <span v-if="bookingRochtah" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('appointmentsPage.booking') }}
              </span>
              <span v-else>{{ $t('common.yes') }}</span>
            </button>
            <button 
              @click="showBookingConfirmModal = false"
              class="btn-outline"
            >
              {{ $t('common.no') }}
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
    
    // Rochtah booking related refs
    const prescriptionRequests = ref([])
    const showRochtahModal = ref(false)
    const showBookingConfirmModal = ref(false)
    const loadingSlots = ref(false)
    const bookingRochtah = ref(false)
    const availableSlots = ref([])
    const selectedSlot = ref(null)
    const currentRequestId = ref(null)

    const tabs = computed(() => [
      { 
        id: 'upcoming', 
        name: $t('appointmentsPage.tabs.upcoming'), 
        count: appointments.value.filter(a => a.status === 'confirmed' || a.status === 'pending' || a.status === 'open').length 
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
      let filtered = []
      
      switch (activeTab.value) {
        case 'upcoming':
          filtered = appointments.value.filter(a => a.status === 'confirmed' || a.status === 'pending' || a.status === 'open')
          // Sort upcoming sessions from nearest to farthest
          return filtered.sort((a, b) => {
            const dateA = new Date(a.date_time || a.date)
            const dateB = new Date(b.date_time || b.date)
            return dateA - dateB
          })
        case 'past':
          filtered = appointments.value.filter(a => a.status === 'completed')
          // Sort past sessions from newest to oldest
          return filtered.sort((a, b) => {
            const dateA = new Date(a.date_time || a.date)
            const dateB = new Date(b.date_time || b.date)
            return dateB - dateA
          })
        case 'cancelled':
          filtered = appointments.value.filter(a => a.status === 'cancelled')
          // Sort cancelled sessions from newest to oldest
          return filtered.sort((a, b) => {
            const dateA = new Date(a.date_time || a.date)
            const dateB = new Date(b.date_time || b.date)
            return dateB - dateA
          })
        default:
          return appointments.value
      }
    })

    const loadAppointments = async () => {
      loading.value = true
      try {
        const response = await api.get('/api/ai/appointments')

        appointments.value = response.data.data || []

      } catch (error) {
        toast.error('Failed to load appointments')
        console.error('Error loading appointments:', error)
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      
      const date = new Date(dateString)
      const isArabic = locale.value === 'ar'
      
      if (isArabic) {
        // Arabic month names
        const arabicMonths = [
          'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
          'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ]
        
        // Arabic day names
        const arabicDays = [
          'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
        ]
        
        const dayName = arabicDays[date.getDay()]
        const monthName = arabicMonths[date.getMonth()]
        const day = date.getDate()
        const year = date.getFullYear()
        
        return `${dayName}، ${day} ${monthName} ${year}`
      } else {
        // English formatting
        return date.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      }
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
        'pending': $t('appointmentsPage.statusPending'),
        'confirmed': $t('appointmentsPage.statusConfirmed'),
        'open': $t('appointmentsPage.statusConfirmed'), // Map 'open' to 'confirmed' for display
        'completed': $t('appointmentsPage.statusCompleted'),
        'cancelled': $t('appointmentsPage.statusCancelled'),
        'no_show': $t('appointmentsPage.statusNoShow')
      }
      return statusMap[status] || status
    }

    const getStatusClass = (status) => {
      const classMap = {
        'pending': 'text-yellow-600 bg-yellow-100',
        'confirmed': 'text-green-600 bg-green-100',
        'open': 'text-green-600 bg-green-100', // Map 'open' to 'confirmed' styling
        'completed': 'text-blue-600 bg-blue-100',
        'cancelled': 'text-red-600 bg-red-100',
        'no_show': 'text-gray-600 bg-gray-100'
      }
      return `px-2 py-1 rounded-full text-xs font-medium ${classMap[status] || ''}`
    }

    const canJoinSession = (appointment) => {
      // Allow joining for both 'open' and 'confirmed' statuses
      if (appointment.status !== 'confirmed' && appointment.status !== 'open') {
        return false
      }
      
      // Always show the button for confirmed/open appointments
      return true
    }

    const canReschedule = (appointment) => {
      if (appointment.status !== 'confirmed' && appointment.status !== 'open' && appointment.status !== 'pending') return false
      
      // Check if appointment has already been rescheduled
      if (appointment.settings && appointment.settings.includes('ai_booking:rescheduled')) {
        return false
      }
      
      let appointmentTime
      if (appointment.date && appointment.date.includes(' ')) {
        appointmentTime = new Date(appointment.date)
      } else if (appointment.date_time) {
        appointmentTime = new Date(appointment.date_time)
      } else if (appointment.date && appointment.time) {
        appointmentTime = new Date(`${appointment.date}T${appointment.time}`)
      } else {
        return false
      }
      
      const now = new Date()
      
      // Can reschedule up to 24 hours before
      return appointmentTime - now > 24 * 60 * 60 * 1000
    }

    const canCancel = (appointment) => {
      if (appointment.status !== 'confirmed' && appointment.status !== 'open' && appointment.status !== 'pending') return false
      
      let appointmentTime
      if (appointment.date && appointment.date.includes(' ')) {
        appointmentTime = new Date(appointment.date)
      } else if (appointment.date_time) {
        appointmentTime = new Date(appointment.date_time)
      } else if (appointment.date && appointment.time) {
        appointmentTime = new Date(`${appointment.date}T${appointment.time}`)
      } else {
        return false
      }
      
      const now = new Date()
      
      // Can cancel up to 24 hours before
      return appointmentTime - now > 24 * 60 * 60 * 1000
    }

    const joinSession = (appointmentId) => {
      // Redirect to the session page
      router.push(`/session/${appointmentId}`)
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

    const bookWithSameTherapist = (appointment) => {
      // Debug logging to see appointment structure
      
      
      // Try to find the therapist ID from various possible fields
      const therapistId = appointment.therapist_id || appointment.user_id || appointment.therapist?.id
      
      
      // Navigate to the new therapist appointment page
      router.push(`/therapist-appointment/${therapistId}`)
    }

    // Load prescription requests
    const loadPrescriptionRequests = async () => {
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/prescription-requests', {
          params: {
            user_id: authStore.user?.id,
            locale: i18n.global.locale.value
          }
        })
        prescriptionRequests.value = response.data.data || []
      } catch (error) {
        console.error('Error loading prescription requests:', error)
      }
    }

    // Show Rochtah booking modal
    const showRochtahBookingModal = async (requestId) => {
      currentRequestId.value = requestId
      showRochtahModal.value = true
      loadingSlots.value = true
      selectedSlot.value = null
      
      try {
        const response = await api.post('/wp-admin/admin-ajax.php', {
          action: 'get_rochtah_available_slots',
          nonce: window.snks_ai_prescription.rochtah_nonce
        })
        
        if (response.data.success) {
          availableSlots.value = response.data.data || []
        } else {
          toast.error(response.data.message || 'Failed to load available slots')
        }
      } catch (error) {
        toast.error('Failed to load available slots')
        console.error('Error loading Rochtah slots:', error)
      } finally {
        loadingSlots.value = false
      }
    }

    // Close Rochtah modal
    const closeRochtahModal = () => {
      showRochtahModal.value = false
      selectedSlot.value = null
      availableSlots.value = []
      currentRequestId.value = null
    }

    // Select a time slot
    const selectSlot = (slot) => {
      selectedSlot.value = slot
    }

    // Show booking confirmation
    const bookRochtahAppointment = () => {
      if (!selectedSlot.value) return
      showBookingConfirmModal.value = true
    }

    // Confirm and book Rochtah appointment
    const confirmRochtahBooking = async () => {
      if (!selectedSlot.value || !currentRequestId.value) return
      
      bookingRochtah.value = true
      
      try {
        const response = await api.post('/wp-admin/admin-ajax.php', {
          action: 'book_rochtah_appointment',
          request_id: currentRequestId.value,
          selected_date: selectedSlot.value.date,
          selected_time: selectedSlot.value.time,
          nonce: window.snks_ai_prescription.rochtah_nonce
        })
        
        if (response.data.success) {
          toast.success(response.data.data.message || 'Appointment booked successfully')
          
          // Close modals
          showBookingConfirmModal.value = false
          closeRochtahModal()
          
          // Reload prescription requests
          await loadPrescriptionRequests()
          
          // Reload appointments to show the new Rochtah appointment
          await loadAppointments()
        } else {
          toast.error(response.data.message || 'Failed to book appointment')
        }
      } catch (error) {
        toast.error('Failed to book appointment')
        console.error('Error booking Rochtah appointment:', error)
      } finally {
        bookingRochtah.value = false
      }
    }



    onMounted(() => {
      loadAppointments()
      loadPrescriptionRequests()
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
      bookWithSameTherapist,
      // Rochtah booking related
      prescriptionRequests,
      showRochtahModal,
      showBookingConfirmModal,
      loadingSlots,
      bookingRochtah,
      availableSlots,
      selectedSlot,
      showRochtahBookingModal,
      closeRochtahModal,
      selectSlot,
      bookRochtahAppointment,
      confirmRochtahBooking
    }
  }
}
</script> 