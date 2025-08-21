<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4 rtl:space-x-reverse">
            <button
              @click="goBack"
              class="p-2 text-gray-600 hover:text-gray-800 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <div>
              <h1 class="text-xl font-semibold text-gray-900">{{ $t('session.title') }}</h1>
              <p v-if="sessionData" class="text-sm text-gray-600">{{ $t('session.with') }} {{ sessionData.therapist_name }}</p>
            </div>
          </div>
          
          <!-- Session Status -->
          <div class="flex items-center space-x-3 rtl:space-x-reverse">
            <div class="flex items-center space-x-2 rtl:space-x-reverse">
              <div class="w-2 h-2 rounded-full" :class="statusColor"></div>
              <span class="text-sm font-medium" :class="statusTextColor">{{ sessionStatus }}</span>
            </div>
            
            <!-- Timer -->
            <div v-if="showTimer" class="text-sm text-gray-600">
              {{ formatTimeRemaining }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-96">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        <p class="mt-4 text-gray-600">{{ $t('session.loading') }}</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-96">
      <div class="text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('session.error') }}</h3>
        <p class="text-gray-600 mb-4">{{ error }}</p>
        <button
          @click="loadSession"
          class="btn-primary px-4 py-2"
        >
          {{ $t('common.retry') }}
        </button>
      </div>
    </div>

    <!-- Session Not Found -->
    <div v-else-if="!sessionData" class="flex items-center justify-center min-h-96">
      <div class="text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('session.notFound') }}</h3>
        <p class="text-gray-600 mb-4">{{ $t('session.notFoundDescription') }}</p>
        <router-link to="/appointments" class="btn-primary px-4 py-2">
          {{ $t('session.backToAppointments') }}
        </router-link>
      </div>
    </div>

    <!-- Session Content -->
    <div v-else class="container mx-auto px-4 py-8">
      <!-- Session Info Card -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Therapist Info -->
            <div class="flex items-center space-x-4 rtl:space-x-reverse">
              <img
                v-if="sessionData && sessionData.therapist_image_url"
                :src="sessionData.therapist_image_url"
                :alt="sessionData.therapist_name"
                class="w-16 h-16 rounded-full object-cover"
              />
              <div v-else class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-medium text-gray-900">{{ sessionData ? sessionData.therapist_name : '' }}</h3>
                <p class="text-sm text-gray-600">{{ $t('session.therapist') }}</p>
              </div>
            </div>

            <!-- Session Time -->
            <div>
              <h4 class="font-medium text-gray-900">{{ $t('session.time') }}</h4>
              <p class="text-sm text-gray-600">{{ formatSessionTime }}</p>
            </div>

            <!-- Session Duration -->
            <div>
              <h4 class="font-medium text-gray-900">{{ $t('session.duration') }}</h4>
              <p class="text-sm text-gray-600">{{ sessionData ? sessionData.period : '' }} {{ $t('session.minutes') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Session Actions -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('session.actions') }}</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Join Session Button -->
            <button
              v-if="canJoinSession"
              @click="joinSession"
              :disabled="joiningSession"
              class="btn-primary w-full py-3 flex items-center justify-center space-x-2 rtl:space-x-reverse"
            >
              <span v-if="joiningSession" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
              <span>{{ joiningSession ? $t('session.joining') : $t('session.join') }}</span>
            </button>

            <!-- Waiting for Therapist -->
            <div v-else-if="waitingForTherapist" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-yellow-600"></div>
                <div>
                  <h4 class="font-medium text-yellow-800">{{ $t('session.waitingForTherapist') }}</h4>
                  <p class="text-sm text-yellow-700">{{ $t('session.waitingDescription') }}</p>
                </div>
              </div>
            </div>

            <!-- Session Not Available -->
            <div v-else class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h4 class="font-medium text-gray-800">{{ $t('session.notAvailable') }}</h4>
                  <p class="text-sm text-gray-600">{{ sessionNotAvailableReason }}</p>
                </div>
              </div>
            </div>

            <!-- End Session Button (for therapists) -->
            <button
              v-if="isTherapist && canEndSession"
              @click="endSession"
              :disabled="endingSession"
              class="btn-secondary w-full py-3 flex items-center justify-center space-x-2 rtl:space-x-reverse"
            >
              <span v-if="endingSession" class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-600"></span>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              <span>{{ endingSession ? $t('session.ending') : $t('session.end') }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Session Instructions -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-3">{{ $t('session.instructions') }}</h3>
        <ul class="space-y-2 text-sm text-blue-800">
          <li class="flex items-start space-x-2 rtl:space-x-reverse">
            <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $t('session.instruction1') }}</span>
          </li>
          <li class="flex items-start space-x-2 rtl:space-x-reverse">
            <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $t('session.instruction2') }}</span>
          </li>
          <li class="flex items-start space-x-2 rtl:space-x-reverse">
            <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $t('session.instruction3') }}</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Meeting Room Modal -->
    <div
      v-if="showMeetingRoom"
      class="fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center"
    >
      <div class="bg-white rounded-lg shadow-xl w-full h-full max-w-6xl mx-4">
        <div class="flex items-center justify-between p-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">{{ $t('session.meetingRoom') }}</h3>
          <button
            @click="closeMeetingRoom"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="flex-1 p-4">
          <div id="meeting" class="w-full h-full min-h-96"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useToast } from 'vue-toastification'
import api from '../services/api'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

// Reactive data
const loading = ref(true)
const error = ref(null)
const sessionData = ref(null)
const joiningSession = ref(false)
const endingSession = ref(false)
const showMeetingRoom = ref(false)
const timeRemaining = ref(0)
const timer = ref(null)

// Computed properties
const sessionStatus = computed(() => {
  if (!sessionData.value) return ''
  
  const statusMap = {
    'pending': t('session.status.pending'),
    'confirmed': t('session.status.confirmed'),
    'completed': t('session.status.completed'),
    'cancelled': t('session.status.cancelled'),
    'no_show': t('session.status.noShow')
  }
  
  return statusMap[sessionData.value.session_status] || sessionData.value.session_status
})

const statusColor = computed(() => {
  if (!sessionData.value) return 'bg-gray-400'
  
  const colorMap = {
    'pending': 'bg-yellow-400',
    'confirmed': 'bg-green-400',
    'completed': 'bg-blue-400',
    'cancelled': 'bg-red-400',
    'no_show': 'bg-gray-400'
  }
  
  return colorMap[sessionData.value.session_status] || 'bg-gray-400'
})

const statusTextColor = computed(() => {
  if (!sessionData.value) return 'text-gray-600'
  
  const colorMap = {
    'pending': 'text-yellow-700',
    'confirmed': 'text-green-700',
    'completed': 'text-blue-700',
    'cancelled': 'text-red-700',
    'no_show': 'text-gray-700'
  }
  
  return colorMap[sessionData.value.session_status] || 'text-gray-600'
})

const formatSessionTime = computed(() => {
  if (!sessionData.value) return ''
  
  const currentLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US'
  const date = new Date(sessionData.value.date_time)
  return date.toLocaleDateString(currentLocale, {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

const formatTimeRemaining = computed(() => {
  if (timeRemaining.value <= 0) return t('session.timeExpired')
  
  const hours = Math.floor(timeRemaining.value / 3600)
  const minutes = Math.floor((timeRemaining.value % 3600) / 60)
  const seconds = timeRemaining.value % 60
  
  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
  }
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const canJoinSession = computed(() => {
  if (!sessionData.value) return false
  
  // Only confirmed sessions can be joined
  if (sessionData.value.session_status !== 'confirmed') return false
  
  const appointmentTime = new Date(sessionData.value.date_time)
  const now = new Date()
  const timeDiff = appointmentTime - now
  
  // Can join 5 minutes before and up to 15 minutes after
  return timeDiff >= -5 * 60 * 1000 && timeDiff <= 15 * 60 * 1000
})

const waitingForTherapist = computed(() => {
  if (!sessionData.value) return false
  
  // Show waiting state if session is confirmed but therapist hasn't joined yet
  return sessionData.value.session_status === 'confirmed' && !sessionData.value.therapist_joined
})

const sessionNotAvailableReason = computed(() => {
  if (!sessionData.value) return ''
  
  if (sessionData.value.session_status !== 'confirmed') {
    return t('session.reason.notConfirmed')
  }
  
  const appointmentTime = new Date(sessionData.value.date_time)
  const now = new Date()
  const timeDiff = appointmentTime - now
  
  if (timeDiff < -5 * 60 * 1000) {
    return t('session.reason.tooEarly')
  }
  
  if (timeDiff > 15 * 60 * 1000) {
    return t('session.reason.tooLate')
  }
  
  return t('session.reason.unknown')
})

const showTimer = computed(() => {
  if (!sessionData.value) return false
  
  const appointmentTime = new Date(sessionData.value.date_time)
  const now = new Date()
  const timeDiff = appointmentTime - now
  
  // Show timer if session is within 1 hour before or after
  return Math.abs(timeDiff) <= 60 * 60 * 1000
})

const isTherapist = computed(() => {
  return authStore.user?.role === 'doctor' || authStore.user?.role === 'therapist'
})

const canEndSession = computed(() => {
  if (!sessionData.value || !isTherapist.value) return false
  
  // Only the assigned therapist can end the session
  return sessionData.value.therapist_id === authStore.user?.id
})

// Methods
const loadSession = async () => {
  loading.value = true
  error.value = null
  
  try {
    console.log('🔍 Loading session for ID:', route.params.id)
    const response = await api.get(`/wp-json/jalsah-ai/v1/session/${route.params.id}`)
    
    console.log('📋 Session API Response:', response.data)
    
    if (response.data.success) {
      sessionData.value = response.data.data
      console.log('✅ Session data loaded:', sessionData.value)
      startTimer()
    } else {
      error.value = response.data.error || t('session.loadError')
      console.error('❌ Session load failed:', error.value)
    }
  } catch (err) {
    console.error('❌ Error loading session:', err)
    error.value = t('session.loadError')
  } finally {
    loading.value = false
  }
}

const startTimer = () => {
  if (!sessionData.value) return
  
  const updateTimer = () => {
    const appointmentTime = new Date(sessionData.value.date_time)
    const now = new Date()
    const timeDiff = Math.floor((appointmentTime - now) / 1000)
    
    timeRemaining.value = timeDiff
    
    // Stop timer if session is more than 1 hour past
    if (timeDiff < -3600) {
      clearInterval(timer.value)
    }
  }
  
  updateTimer()
  timer.value = setInterval(updateTimer, 1000)
}

const joinSession = async () => {
  if (!canJoinSession.value) return
  
  joiningSession.value = true
  
  try {
    // Redirect to the main site's meeting room
    const meetingUrl = `${import.meta.env.VITE_MAIN_SITE_URL}/meeting-room/?room_id=${sessionData.value.ID}`
    
    // Open in new tab
    window.open(meetingUrl, '_blank')
    
    // Also show the meeting room modal
    showMeetingRoom.value = true
    
    toast.success(t('session.joined'))
  } catch (err) {
    console.error('Error joining session:', err)
    toast.error(t('session.joinError'))
  } finally {
    joiningSession.value = false
  }
}

const endSession = async () => {
  if (!canEndSession.value) return
  
  if (!confirm(t('session.confirmEnd'))) return
  
  endingSession.value = true
  
  try {
    const response = await api.post(`/wp-json/jalsah-ai/v1/session/${sessionData.value.ID}/end`)
    
    if (response.data.success) {
      toast.success(t('session.ended'))
      await loadSession() // Reload session data
    } else {
      toast.error(response.data.error || t('session.endError'))
    }
  } catch (err) {
    console.error('Error ending session:', err)
    toast.error(t('session.endError'))
  } finally {
    endingSession.value = false
  }
}

const closeMeetingRoom = () => {
  showMeetingRoom.value = false
}

const goBack = () => {
  router.back()
}

// Lifecycle
onMounted(() => {
  loadSession()
})

onUnmounted(() => {
  if (timer.value) {
    clearInterval(timer.value)
  }
})
</script>
