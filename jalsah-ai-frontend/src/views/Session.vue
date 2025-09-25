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
                         <!-- Join Session Button (for patients) -->
             <button
               v-if="canJoinSession && !isTherapist"
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

             <!-- Start Meeting Button (for therapists) -->
             <button
               v-if="canJoinSession && isTherapist"
               @click="startMeeting"
               :disabled="startingMeeting"
               class="btn-primary w-full py-3 flex items-center justify-center space-x-2 rtl:space-x-reverse"
             >
               <span v-if="startingMeeting" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
               <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
               </svg>
               <span>{{ startingMeeting ? $t('session.starting') : $t('session.startMeeting') }}</span>
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
            <div v-else-if="sessionNotAvailableReason" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
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

                         <!-- Mark as Completed Section (for therapists) -->
             <div v-if="isTherapist && canEndSession" class="space-y-4">
               <h4 class="font-medium text-gray-900">{{ $t('session.markCompletedTitle') }}</h4>
               
               <!-- Mark as Completed Button -->
               <button
                 @click="markAsCompleted"
                 :disabled="endingSession"
                 class="btn-secondary w-full py-3 flex items-center justify-center space-x-2 rtl:space-x-reverse"
               >
                 <span v-if="endingSession" class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-600"></span>
                 <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                 </svg>
                 <span>{{ endingSession ? $t('session.marking') : $t('session.markCompleted') }}</span>
               </button>
             </div>
             

          </div>
        </div>
      </div>

    </div>

         <!-- Meeting Room Modal -->
     <div
       v-if="showMeetingRoom"
       class="fixed inset-0 z-50 bg-black bg-opacity-90"
     >
       <!-- Header -->
       <div class="absolute top-0 left-0 right-0 z-10 bg-white bg-opacity-95 backdrop-blur-sm border-b border-gray-200">
         <div class="flex items-center justify-between p-4">
           <div class="flex items-center space-x-3 rtl:space-x-reverse">
             <div class="w-3 h-3 bg-red-500 rounded-full"></div>
             <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
             <div class="w-3 h-3 bg-green-500 rounded-full"></div>
           </div>
           <h3 class="text-lg font-medium text-gray-900">{{ $t('session.meetingRoom') }}</h3>
           <button
             @click="closeMeetingRoom"
             class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-100"
           >
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
             </svg>
           </button>
         </div>
       </div>
       
       <!-- Meeting Container -->
       <div class="pt-16 h-full">
         <div id="meeting" class="w-full h-full" style="min-height: calc(100vh - 4rem);">
                       <!-- Loading state while Jitsi loads -->
            <div v-if="!jitsiLoaded" class="flex items-center justify-center h-full bg-gray-900">
              <div class="text-center text-white">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent mx-auto mb-4"></div>
                <p class="text-lg font-medium">{{ $t('session.loadingMeeting') }}</p>
                <p class="text-sm text-gray-400 mt-2">{{ $t('session.connecting') }}</p>
                
                <!-- Manual show button after 5 seconds -->
                <button 
                  v-if="showManualButton"
                  @click="forceShowMeeting"
                  class="mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                >
                  {{ $t('session.showMeeting') }}
                </button>
              </div>
            </div>
         </div>
       </div>
     </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useToast } from 'vue-toastification'
import Swal from 'sweetalert2'
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
const startingMeeting = ref(false)
const showMeetingRoom = ref(false)
const timeRemaining = ref(0)
const timer = ref(null)
const jitsiLoaded = ref(false)
const meetAPI = ref(null)
const showManualButton = ref(false)
const sessionRefreshTimer = ref(null)


// Computed properties
const sessionStatus = computed(() => {
  if (!sessionData.value) return ''
  
  const statusMap = {
    'pending': t('session.status.pending'),
    'confirmed': t('session.status.confirmed'),
    'open': t('session.status.confirmed'), // Treat 'open' as 'confirmed' for AI sessions
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
    'open': 'bg-green-400', // Treat 'open' as 'confirmed' for AI sessions
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
    'open': 'text-green-700', // Treat 'open' as 'confirmed' for AI sessions
    'completed': 'text-blue-700',
    'cancelled': 'text-red-700',
    'no_show': 'text-gray-700'
  }
  
  return colorMap[sessionData.value.session_status] || 'text-gray-600'
})

const formatSessionTime = computed(() => {
  if (!sessionData.value) return ''
  
  const date = new Date(sessionData.value.date_time)
  
  // Arabic month names
  const arabicMonths = {
    1: 'يناير', 2: 'فبراير', 3: 'مارس', 4: 'أبريل',
    5: 'مايو', 6: 'يونيو', 7: 'يوليو', 8: 'أغسطس',
    9: 'سبتمبر', 10: 'أكتوبر', 11: 'نوفمبر', 12: 'ديسمبر'
  }
  
  // Arabic day names
  const arabicDays = {
    0: 'الأحد', 1: 'الإثنين', 2: 'الثلاثاء', 3: 'الأربعاء',
    4: 'الخميس', 5: 'الجمعة', 6: 'السبت'
  }
  
  // Arabic time periods
  const arabicTimePeriods = {
    AM: 'ص', PM: 'م'
  }
  
  const dayName = arabicDays[date.getDay()]
  const day = date.getDate()
  const monthName = arabicMonths[date.getMonth() + 1]
  const year = date.getFullYear()
  
  // Format time in 12-hour format with translated AM/PM
  let hours = date.getHours()
  const minutes = date.getMinutes()
  const period = hours >= 12 ? t('dateTime.pm') : t('dateTime.am')
  
  if (hours > 12) {
    hours -= 12
  } else if (hours === 0) {
    hours = 12
  }
  
  const timeString = `${hours}:${minutes.toString().padStart(2, '0')} ${period}`
  
  return `${dayName}، ${day} ${monthName} ${year} الساعة ${timeString}`
})

const formatTimeRemaining = computed(() => {
   // Don't show "time expired" since we removed time restrictions
   if (timeRemaining.value <= 0) return ''
   
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
   
   // Don't allow joining if session is completed or cancelled
   if (sessionData.value.session_status === 'completed' || sessionData.value.session_status === 'cancelled') {
     return false
   }
   
   // Check if current user is eligible (therapist or client)
   const currentUserId = Number(authStore.user?.id)
   const sessionTherapistId = Number(sessionData.value.therapist_id || sessionData.value.user_id)
   const sessionClientId = Number(sessionData.value.client_id)
  
  
  return currentUserId === sessionTherapistId || currentUserId === sessionClientId
})

const waitingForTherapist = computed(() => {
  if (!sessionData.value) return false
  
  // Only show waiting state for patients, not therapists
  if (isTherapist.value) return false
  
  // Show waiting state if session is confirmed/open but therapist hasn't joined yet
  const isConfirmed = sessionData.value.session_status === 'confirmed' || sessionData.value.session_status === 'open'
  const waiting = isConfirmed && !sessionData.value.therapist_joined
  
  
  return waiting
})

const sessionNotAvailableReason = computed(() => {
   if (!sessionData.value) return ''
   
   // Check if session is completed or cancelled
   if (sessionData.value.session_status === 'completed') {
     return t('session.reason.completed')
   }
   
   if (sessionData.value.session_status === 'cancelled') {
     return t('session.reason.cancelled')
   }
  
   // Check if current user is eligible (therapist or client)
   const currentUserId = Number(authStore.user?.id)
   const sessionTherapistId = Number(sessionData.value.therapist_id || sessionData.value.user_id)
   const sessionClientId = Number(sessionData.value.client_id)
  
  
   if (currentUserId !== sessionTherapistId && currentUserId !== sessionClientId) {
     return t('session.reason.notAuthorized')
   }
  
   // If we reach here, the session is available and user is authorized
   // Return empty string to indicate no reason (session is available)
   return ''
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
  return Number(sessionData.value.therapist_id) === Number(authStore.user?.id)
})



// Methods
const loadSession = async () => {
  loading.value = true
  error.value = null
  
  try {
    
    const response = await api.get(`/wp-json/jalsah-ai/v1/session/${route.params.id}`)
    
    
    
         if (response.data.success) {
      sessionData.value = response.data.data
      
        startTimer()
        startSessionRefresh() // Start refreshing session data
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
     
     // Keep timer running even after session time has passed
     // since we removed time restrictions
   }
   
   updateTimer()
   timer.value = setInterval(updateTimer, 1000)
 }

const startSessionRefresh = () => {
  if (!sessionData.value) return
  
  // Refresh session data every 5 seconds to check for therapist joined status
  sessionRefreshTimer.value = setInterval(async () => {
    try {

      const response = await api.get(`/wp-json/jalsah-ai/v1/session/${sessionData.value.ID}`)
      
      if (response.data.success) {
        const newData = response.data.data

        
        // Update session data
        sessionData.value = newData
      }
    } catch (err) {
      console.error('❌ Error refreshing session data:', err)
    }
  }, 5000) // Refresh every 5 seconds
}

const joinSession = async () => {
  if (!canJoinSession.value) return
  
  joiningSession.value = true
  
  try {
    
    
    // Show the meeting room modal within our frontend
    showMeetingRoom.value = true
    
    toast.success(t('session.joined'))
  } catch (err) {
    console.error('Error joining session:', err)
    toast.error(t('session.joinError'))
  } finally {
    joiningSession.value = false
  }
}

const startMeeting = async () => {
  if (!canJoinSession.value || !isTherapist.value) return
  
  startingMeeting.value = true
  
  try {
    
    
    // Notify backend that therapist is starting the meeting
    await notifyTherapistJoined(sessionData.value.ID)
    
    // Show the meeting room modal within our frontend
    showMeetingRoom.value = true
    
    toast.success(t('session.meetingStarted'))
  } catch (err) {
    console.error('Error starting meeting:', err)
    toast.error(t('session.startError'))
  } finally {
    startingMeeting.value = false
  }
}

const markAsCompleted = async () => {
   if (!canEndSession.value) return
   
   // Use SweetAlert2 for confirmation
   const result = await Swal.fire({
     title: t('session.confirmMarkCompletedTitle'),
     text: t('session.confirmMarkCompleted'),
     icon: 'question',
     showCancelButton: true,
     confirmButtonColor: '#3085d6',
     cancelButtonColor: '#6b7280',
     confirmButtonText: t('session.confirmMarkCompletedYes'),
     cancelButtonText: t('session.confirmMarkCompletedNo')
   })
   
   if (!result.isConfirmed) return
   
   endingSession.value = true
   
   try {
     const response = await api.post(`/wp-json/jalsah-ai/v1/session/${sessionData.value.ID}/end`, {
       attendance: 'yes' // Default to 'yes' since we're not tracking attendance
     })
     
     if (response.data.success) {
       toast.success(t('session.markedCompleted'))
       await loadSession() // Reload session data
     } else {
       toast.error(response.data.error || t('session.markCompletedError'))
     }
   } catch (err) {
     console.error('Error marking session as completed:', err)
     if (err.response?.data?.error) {
       toast.error(err.response.data.error)
     } else {
       toast.error(t('session.markCompletedError'))
     }
   } finally {
     endingSession.value = false
   }
 }



const closeMeetingRoomAutomatic = () => {
  showMeetingRoom.value = false
  // Clean up Jitsi meeting
  if (meetAPI.value) {
    meetAPI.value.dispose()
    meetAPI.value = null
  }
  jitsiLoaded.value = false
  showManualButton.value = false
  
  // Redirect patients back to appointments page when they exit the meeting
  if (!isTherapist.value) {
    // Add a small delay to ensure the modal closes properly
    setTimeout(() => {
      router.push('/appointments')
    }, 300)
  }
}

const closeMeetingRoom = async () => {
  // For patients, show confirmation before leaving
  if (!isTherapist.value) {
    const result = await Swal.fire({
      title: t('session.leaveMeetingTitle'),
      text: t('session.leaveMeetingMessage'),
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#6b7280',
      confirmButtonText: t('session.leaveMeeting'),
      cancelButtonText: t('session.stayInMeeting')
    })
    
    if (!result.isConfirmed) {
      return // User chose to stay in the meeting
    }
  }
  
  showMeetingRoom.value = false
  // Clean up Jitsi meeting
  if (meetAPI.value) {
    meetAPI.value.dispose()
    meetAPI.value = null
  }
  jitsiLoaded.value = false
  showManualButton.value = false
  
  // Redirect patients back to appointments page when they exit the meeting
  if (!isTherapist.value) {
    // Add a small delay to ensure the modal closes properly
    setTimeout(() => {
      router.push('/appointments')
    }, 300)
  }
}

const initializeJitsiMeeting = () => {
  if (!sessionData.value || !showMeetingRoom.value) return
  
  
  
  // Check if JitsiMeetExternalAPI is already available
  if (typeof JitsiMeetExternalAPI !== 'undefined') {
    
    startJitsiMeeting()
    return
  }
  
  // Load Jitsi external API script
  const script = document.createElement('script')
  script.src = 'https://s.jalsah.app/external_api.js'
  script.onload = () => {
    
    setTimeout(() => {
      startJitsiMeeting()
    }, 500) // Give it a moment to initialize
  }
  script.onerror = (error) => {
    console.error('❌ Failed to load Jitsi script:', error)
    toast.error(t('session.meetingError'))
    jitsiLoaded.value = false
  }
  document.head.appendChild(script)
}

const startJitsiMeeting = () => {
  if (!sessionData.value) return
  
  const roomID = sessionData.value.ID
  const userName = authStore.user?.name || authStore.user?.username || 'User'
  const isTherapist = authStore.user?.role === 'doctor' || authStore.user?.role === 'therapist'
  
     const options = {
     parentNode: document.querySelector('#meeting'),
     roomName: `${roomID} جلسة`,
     width: '100%',
     height: '100%',
     configOverwrite: {
       prejoinPageEnabled: false,
       startWithAudioMuted: false,
       startWithVideoMuted: false,
       disableAudioLevels: false,
       enableClosePage: true,
       enableWelcomePage: false,
       participantsPane: {
         enabled: true,
         hideModeratorSettingsTab: false,
         hideMoreActionsButton: false,
         hideMuteAllButton: false
       },
       toolbarButtons: [
         'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
         'fodeviceselection', 'hangup', 'profile', 'chat', 'recording', 
         'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
         'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
       ]
     },
     interfaceConfigOverwrite: {
       prejoinPageEnabled: false,
       APP_NAME: 'Jalsah',
       DEFAULT_BACKGROUND: "#1a1a1a",
       SHOW_JITSI_WATERMARK: false,
       HIDE_DEEP_LINKING_LOGO: true,
       SHOW_BRAND_WATERMARK: false,
       SHOW_WATERMARK_FOR_GUESTS: false,
       SHOW_POWERED_BY: false,
       DISPLAY_WELCOME_FOOTER: false,
       JITSI_WATERMARK_LINK: 'https://jalsah.app',
       PROVIDER_NAME: 'Jalsah',
       DEFAULT_LOGO_URL: 'https://jalsah.app/wp-content/uploads/2024/08/watermark.svg',
       DEFAULT_WELCOME_PAGE_LOGO_URL: 'https://jalsah.app/wp-content/uploads/2024/08/watermark.svg',
       TOOLBAR_ALWAYS_VISIBLE: true,
       TOOLBAR_BUTTONS: [
         'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
         'fodeviceselection', 'hangup', 'profile', 'chat', 'recording', 
         'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
         'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
       ]
     }
   }
  
     try {
 
     
     // Try the main Jitsi server first
     try {
       meetAPI.value = new JitsiMeetExternalAPI("s.jalsah.app", options)
     } catch (serverError) {
       console.warn('⚠️ Main server failed, trying fallback:', serverError)
       // Fallback to meet.jit.si if main server fails
       meetAPI.value = new JitsiMeetExternalAPI("meet.jit.si", options)
     }
     meetAPI.value.executeCommand('displayName', userName)
     
     // Add event listeners
     meetAPI.value.addListener('videoConferenceJoined', () => {
       
       jitsiLoaded.value = true
       
       // If therapist joined, notify the backend
       if (isTherapist) {
         notifyTherapistJoined(roomID)
       }
     })
     
     meetAPI.value.addListener('videoConferenceLeft', () => {
       
       closeMeetingRoomAutomatic()
     })
     
     meetAPI.value.addListener('readyToClose', () => {
       
       closeMeetingRoomAutomatic()
     })
     
     meetAPI.value.addListener('participantJoined', () => {
       
     })
     
     meetAPI.value.addListener('participantLeft', () => {
       
     })
     
     // Show manual button after 5 seconds if still loading
     setTimeout(() => {
       if (!jitsiLoaded.value) {
 
         showManualButton.value = true
       }
     }, 5000) // 5 seconds
     
     // Set a timeout to show the meeting even if not fully loaded
     setTimeout(() => {
       if (!jitsiLoaded.value) {
 
         jitsiLoaded.value = true
         showManualButton.value = false
       }
     }, 15000) // 15 seconds timeout
     
   } catch (error) {
     console.error('❌ Error initializing Jitsi meeting:', error)
     toast.error(t('session.meetingError'))
     jitsiLoaded.value = false
   }
}

const notifyTherapistJoined = async (roomID) => {
  try {
    const response = await api.post(`/wp-json/jalsah-ai/v1/session/${roomID}/therapist-join`)
    if (response.data.success) {
      
    }
  } catch (error) {
    console.error('❌ Error updating therapist joined status:', error)
  }
}

const forceShowMeeting = () => {
  
  jitsiLoaded.value = true
  showManualButton.value = false
}

const goBack = () => {
  router.back()
}

// Watchers
watch(showMeetingRoom, (newValue) => {
  if (newValue) {
    // Initialize Jitsi meeting when modal opens
    setTimeout(() => {
      initializeJitsiMeeting()
    }, 100)
  }
})

// Lifecycle
onMounted(() => {
  loadSession()
})

onUnmounted(() => {
  if (timer.value) {
    clearInterval(timer.value)
  }
  if (sessionRefreshTimer.value) {
    clearInterval(sessionRefreshTimer.value)
  }
})
</script>
