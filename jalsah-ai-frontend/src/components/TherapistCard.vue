<template>
  <div class="card hover:shadow-lg transition-shadow">
    <div class="flex items-start gap-6" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
      <!-- Therapist Image -->
      <div class="relative flex-shrink-0">
        <img 
          :src="therapist.photo || '/default-therapist.svg'" 
          :alt="therapist.name"
          class="w-32 h-32 rounded-lg"
          :class="therapist.photo ? 'object-cover' : 'object-contain bg-gray-100 p-4'"
        />
        <div class="absolute top-2 right-2 bg-primary-600 text-white px-2 py-1 rounded-full text-sm font-medium">
          {{ therapist.price?.others || $t('common.contact') }}
        </div>
      </div>

      <!-- Therapist Info -->
      <div class="flex-1 flex flex-col justify-between min-h-32">
        <!-- Top Section: Name, Rating, Bio -->
        <div class="space-y-4">
          <div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ therapist.name }}</h3>
            
            <div class="flex items-center gap-2">
              <StarRating :rating="therapist.rating || 0" />
              <span class="text-sm text-gray-600">
                {{ (therapist.rating || 0).toFixed(1) }} ({{ therapist.total_ratings || 0 }} {{$t('therapistDetail.reviews')}})
              </span>
            </div>
          </div>

          <p class="text-gray-600 text-sm line-clamp-2 leading-relaxed">
            {{ therapist.bio || $t('therapists.bioDefault') }}
          </p>

          <!-- Specializations/Diagnoses -->
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="diagnosis in therapist.diagnoses?.slice(0, 3)" 
              :key="diagnosis.id"
              class="bg-primary-100 text-primary-800 text-xs px-3 py-1 rounded-full"
            >
              {{ diagnosis.name }}
            </span>
            <span v-if="therapist.diagnoses?.length > 3" class="text-xs text-gray-500 px-2 py-1">
              {{ $t('therapists.more', { count: therapist.diagnoses.length - 3 }) }}
            </span>
          </div>

          <!-- Suitability Message (only show if provided) -->
          <div v-if="suitabilityMessage" class="bg-primary-50 border border-primary-200 rounded-lg p-3">
            <p class="text-sm text-primary-800">
              {{ suitabilityMessage }}
            </p>
          </div>
        </div>

        <!-- Bottom Section: Availability and View Details Button -->
        <div class="flex items-center justify-between mt-6" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
          <!-- Next Available Slot -->
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ formatEarliestSlot(therapist) }}</span>
          </div>

          <!-- View Details Button -->
          <button
            @click.stop="showTherapistDetails"
            class="btn-primary px-6 py-2"
          >
            {{ showDetails ? $t('common.hide') : $t('common.viewDetails') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Expanded Details Section -->
    <div v-if="showDetails" class="mt-6 border-t border-gray-200 pt-6">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
        <p class="text-gray-600 mt-2">{{ $t('therapistDetails.loading') }}</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8">
        <p class="text-red-600">{{ $t('therapistDetails.error') }}</p>
        <button @click="loadTherapistDetails" class="btn-secondary mt-2">
          {{ $t('common.retry') }}
        </button>
      </div>
      
      <!-- Details Content -->
      <div v-else-if="details" class="space-y-6">
        <!-- Bio Section -->
        <div v-if="details.bio" class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('therapistDetails.bio') }}</h4>
          <p class="text-gray-700 leading-relaxed">{{ details.bio }}</p>
        </div>

        <!-- Certificates Carousel -->
        <div v-if="details.certificates && details.certificates.length > 0" class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.certificates') }}</h4>
          
          <!-- Carousel Container -->
          <div class="relative">
            <!-- Carousel Track -->
            <div class="flex overflow-x-auto gap-4 pb-4 scrollbar-hide" ref="carouselTrack">
              <div 
                v-for="(cert, index) in details.certificates" 
                :key="cert.id"
                class="flex-shrink-0 w-48"
              >
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                  <div class="aspect-square">
                    <img 
                      v-if="cert.is_image"
                      :src="cert.thumbnail_url || cert.url" 
                      :alt="cert.name"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                      <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </div>
                  </div>
                  <div class="p-3">
                    <p class="text-sm text-gray-900 truncate">{{ cert.name }}</p>
                    <p class="text-xs text-gray-500">{{ cert.file_size }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Carousel Navigation -->
            <button 
              v-if="canScrollLeft"
              @click="scrollCarousel('left')"
              class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-50"
            >
              <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            
            <button 
              v-if="canScrollRight"
              @click="scrollCarousel('right')"
              class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-50"
            >
              <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Booking Section -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.bookAppointment') }}</h4>
          
          <!-- Soonest Available Appointment -->
          <div v-if="earliestSlot" class="mb-4 p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">{{ $t('therapistDetails.earliestAvailable') }}</p>
                <p class="font-medium text-gray-900">{{ formatSlot(earliestSlot) }}</p>
              </div>
              <button 
                @click="bookEarliestSlot"
                class="btn-primary px-4 py-2 text-sm"
                :disabled="bookingLoading"
              >
                {{ bookingLoading ? $t('common.loading') : $t('therapistDetails.bookThis') }}
              </button>
            </div>
          </div>

          <!-- Book Another Appointment Button -->
          <button 
            @click="showDateSelection = !showDateSelection"
            class="btn-secondary w-full py-3"
          >
            {{ showDateSelection ? $t('common.hide') : $t('therapistDetails.bookAnother') }}
          </button>

          <!-- Date Selection Carousel -->
          <div v-if="showDateSelection" class="mt-4">
            <div v-if="loadingDates" class="text-center py-4">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600 mx-auto"></div>
              <p class="text-sm text-gray-600 mt-2">{{ $t('therapistDetails.loadingDates') }}</p>
            </div>
            
            <div v-else-if="availableDates.length > 0" class="space-y-4">
              <!-- Date Carousel -->
              <div class="flex overflow-x-auto gap-3 pb-2 scrollbar-hide">
                <button
                  v-for="date in availableDates"
                  :key="date.value"
                  @click="selectDate(date)"
                  class="flex-shrink-0 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                  :class="selectedDate?.value === date.value 
                    ? 'border-primary-600 bg-primary-50 text-primary-700' 
                    : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400'"
                >
                  <div class="text-center">
                    <div class="font-semibold">{{ date.day }}</div>
                    <div class="text-xs">{{ date.date }}</div>
                  </div>
                </button>
              </div>

              <!-- Time Slots Grid -->
              <div v-if="selectedDate && timeSlots.length > 0" class="bg-white rounded-lg border border-gray-200 p-4">
                <h5 class="font-medium text-gray-900 mb-3">{{ $t('therapistDetails.availableTimes') }}</h5>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                  <button
                    v-for="slot in timeSlots"
                    :key="slot.value"
                    @click="addToCart(slot)"
                    class="px-3 py-2 text-sm rounded border transition-colors"
                    :class="slot.inCart 
                      ? 'border-green-600 bg-green-50 text-green-700' 
                      : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50'"
                    :disabled="slot.inCart"
                  >
                    {{ formatTimeSlot(slot.time) }}
                    <span v-if="slot.inCart" class="block text-xs">✓ {{ $t('common.added') }}</span>
                  </button>
                </div>
              </div>

              <!-- No Time Slots -->
              <div v-else-if="selectedDate && timeSlots.length === 0" class="text-center py-4 text-gray-500">
                {{ $t('therapistDetails.noTimeSlots') }}
              </div>
            </div>

            <!-- No Available Dates -->
            <div v-else class="text-center py-4 text-gray-500">
              {{ $t('therapistDetails.noAvailableDates') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, ref, watch, nextTick, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import StarRating from './StarRating.vue'

export default {
  name: 'TherapistCard',
  components: {
    StarRating
  },
  props: {
    therapist: {
      type: Object,
      required: true
    },
    diagnosisId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['click', 'book'],
  setup(props) {
    const { t, locale } = useI18n()
    const authStore = useAuthStore()
    const toast = useToast()
    
    const showDetails = ref(false)
    const loading = ref(false)
    const error = ref(null)
    const details = ref(null)
    
    // Carousel state
    const carouselTrack = ref(null)
    const canScrollLeft = ref(false)
    const canScrollRight = ref(false)
    
    // Booking state
    const showDateSelection = ref(false)
    const loadingDates = ref(false)
    const availableDates = ref([])
    const selectedDate = ref(null)
    const timeSlots = ref([])
    const bookingLoading = ref(false)
    const earliestSlot = ref(null)

    const getAverageRating = (therapist) => {
      if (!therapist.diagnoses || therapist.diagnoses.length === 0) {
        return 0
      }
      const validRatings = therapist.diagnoses.filter(d => d.rating && !isNaN(d.rating) && d.rating > 0)
      if (validRatings.length === 0) {
        return 0
      }
      const total = validRatings.reduce((sum, d) => sum + Math.min(d.rating || 0, 5), 0)
      const average = total / validRatings.length
      return Math.min(average, 5)
    }

    const suitabilityMessage = computed(() => {
      if (!props.diagnosisId || !props.therapist.diagnoses) return null
      
      const diagnosis = props.therapist.diagnoses.find(d => d.id.toString() === props.diagnosisId.toString())
      return diagnosis?.suitability_message || null
    })

    const showTherapistDetails = () => {
      showDetails.value = !showDetails.value
      if (showDetails.value && !details.value) {
        loadTherapistDetails()
      }
    }

    const loadTherapistDetails = async () => {
      loading.value = true
      error.value = null
      
      try {
        const response = await fetch(`/api/ai/therapists/${props.therapist.id}/details`)
        const data = await response.json()
        
        if (data.success) {
          details.value = data.data
          // Load earliest slot
          loadEarliestSlot()
        } else {
          error.value = data.message || t('therapistDetails.loadError')
        }
      } catch (err) {
        error.value = t('therapistDetails.error')
      } finally {
        loading.value = false
      }
    }

    const loadEarliestSlot = async () => {
      try {
        const response = await fetch(`/api/ai/therapists/${props.therapist.id}/earliest-slot`)
        const data = await response.json()
        if (data.success) {
          earliestSlot.value = data.data
        }
      } catch (err) {
        // Silently fail - earliest slot is not critical
      }
    }

    const loadAvailableDates = async () => {
      loadingDates.value = true
      try {
        const response = await fetch(`/api/ai/therapists/${props.therapist.id}/available-dates`)
        const data = await response.json()
        if (data.success) {
          availableDates.value = data.data.map(date => ({
            value: date.date,
            day: new Date(date.date).toLocaleDateString(locale.value, { weekday: 'short' }),
            date: new Date(date.date).toLocaleDateString(locale.value, { month: 'short', day: 'numeric' })
          }))
        }
      } catch (err) {
        availableDates.value = []
      } finally {
        loadingDates.value = false
      }
    }

    const selectDate = async (date) => {
      selectedDate.value = date
      loadingDates.value = true
      try {
        const response = await fetch(`/api/ai/therapists/${props.therapist.id}/time-slots?date=${date.value}`)
        const data = await response.json()
        if (data.success) {
          timeSlots.value = data.data.map(slot => ({
            ...slot,
            inCart: false
          }))
        }
      } catch (err) {
        timeSlots.value = []
      } finally {
        loadingDates.value = false
      }
    }

    const addToCart = async (slot) => {
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      try {
        const response = await fetch('/api/ai/cart/add', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authStore.token}`
          },
          body: JSON.stringify({
            therapist_id: props.therapist.id,
            date: selectedDate.value.value,
            time: slot.value,
            duration: 30 // Default duration
          })
        })
        
        const data = await response.json()
        if (data.success) {
          slot.inCart = true
          toast.success(t('therapistDetails.appointmentAdded'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else {
          toast.error(data.error || t('common.error'))
        }
      } catch (err) {
        console.error('Error adding to cart:', err)
        toast.error(t('common.error'))
      }
    }

    const bookEarliestSlot = async () => {
      if (!earliestSlot.value) return
      
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      // Debug logging
      console.log('Auth store token:', authStore.token)
      console.log('Auth store user:', authStore.user)
      console.log('Is authenticated:', authStore.isAuthenticated)
      
      bookingLoading.value = true
      try {
        const requestBody = {
          therapist_id: props.therapist.id,
          date: earliestSlot.value.date,
          time: earliestSlot.value.time,
          duration: 30
        }
        
        console.log('Request body:', requestBody)
        console.log('Authorization header:', `Bearer ${authStore.token}`)
        
        const response = await fetch('/api/ai/cart/add', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${authStore.token}`
          },
          body: JSON.stringify(requestBody)
        })
        
        console.log('Response status:', response.status)
        const data = await response.json()
        console.log('Response data:', data)
        
        if (data.success) {
          toast.success(t('therapistDetails.appointmentAdded'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else {
          toast.error(data.error || t('common.error'))
        }
      } catch (err) {
        console.error('Error booking earliest slot:', err)
        toast.error(t('common.error'))
      } finally {
        bookingLoading.value = false
      }
    }

    const scrollCarousel = (direction) => {
      if (!carouselTrack.value) return
      
      const scrollAmount = 200
      const currentScroll = carouselTrack.value.scrollLeft
      
      if (direction === 'left') {
        carouselTrack.value.scrollLeft = currentScroll - scrollAmount
      } else {
        carouselTrack.value.scrollLeft = currentScroll + scrollAmount
      }
    }

    const updateCarouselButtons = () => {
      if (!carouselTrack.value) return
      
      canScrollLeft.value = carouselTrack.value.scrollLeft > 0
      canScrollRight.value = carouselTrack.value.scrollLeft < 
        (carouselTrack.value.scrollWidth - carouselTrack.value.clientWidth)
    }

    const formatTimeSlot = (time) => {
      if (!time) return ''
      
      // Convert 24-hour format to 12-hour format with AM/PM
      const timeParts = time.split(':')
      const hours = parseInt(timeParts[0])
      const minutes = parseInt(timeParts[1]) // Parse minutes as integer
      
      if (isNaN(hours) || isNaN(minutes)) {
        console.log('Invalid time format in formatTimeSlot:', time)
        return time // Return original time if parsing fails
      }
      
      const period = hours >= 12 ? t('dateTime.pm') : t('dateTime.am')
      const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      return `${displayHours}:${minutes} ${period}`
    }

    const formatSlot = (slot) => {
      if (!slot) return ''
      
      // Handle the date parsing - the API returns date in Y-m-d format
      let date
      if (slot.date) {
        date = new Date(slot.date)
      } else if (slot.date_time) {
        date = new Date(slot.date_time)
      } else {
        return t('therapists.noSlotsAvailable')
      }
      
      // Check if date is valid
      if (isNaN(date.getTime())) {
        return t('therapists.noSlotsAvailable')
      }
      
      const time = slot.time
      if (!time) {
        return t('therapists.noSlotsAvailable')
      }
      
      // Convert 24-hour format to 12-hour format with AM/PM
      const timeParts = time.split(':')
      const hours = parseInt(timeParts[0])
      const minutes = parseInt(timeParts[1]) // Parse minutes as integer
      
      if (isNaN(hours) || isNaN(minutes)) {
        console.log('Invalid time format:', time, 'parts:', timeParts)
        return t('therapists.noSlotsAvailable')
      }
      
      const period = hours >= 12 ? t('dateTime.pm') : t('dateTime.am')
      const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      const formattedTime = `${displayHours}:${minutes} ${period}`
      
      const dateStr = date.toLocaleDateString(locale.value, { 
        weekday: 'short', 
        month: 'short', 
        day: 'numeric' 
      })
      
      return `${dateStr} ${t('dateTime.at')} ${formattedTime}`
    }

    const formatEarliestSlot = (therapist) => {
      let slotData = null
      let slotDate = null
      
      // First try to use the earliestSlot ref (loaded from API)
      if (earliestSlot.value && earliestSlot.value.date && earliestSlot.value.time) {
        try {
          slotDate = new Date(earliestSlot.value.date + ' ' + earliestSlot.value.time)
          if (!isNaN(slotDate.getTime())) {
            slotData = {
              date: earliestSlot.value.date,
              time: earliestSlot.value.time
            }
          }
        } catch (error) {
          console.warn('Error parsing earliestSlot ref:', error)
        }
      }
      
      // Fallback to therapist.earliest_slot if available
      if (!slotData && therapist.earliest_slot) {
        try {
          slotDate = new Date(therapist.earliest_slot)
          if (!isNaN(slotDate.getTime())) {
            slotData = {
              date: slotDate.toISOString().split('T')[0],
              time: slotDate.toTimeString().split(' ')[0].substring(0, 5)
            }
          }
        } catch (error) {
          console.warn('Error parsing therapist.earliest_slot:', error)
        }
      }
      
      // If no valid slot data found
      if (!slotData || !slotDate) {
        return t('therapists.noSlotsAvailable')
      }
      
      try {
        const currentLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US'
        
        // Convert 24-hour format to 12-hour format with AM/PM
        const timeParts = slotData.time.split(':')
        const hours = parseInt(timeParts[0])
        const minutes = parseInt(timeParts[1])
        
        if (isNaN(hours) || isNaN(minutes)) {
          return t('therapists.noSlotsAvailable')
        }
        
        const period = hours >= 12 ? t('dateTime.pm') : t('dateTime.am')
        const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
        const formattedTime = `${displayHours}:${minutes.toString().padStart(2, '0')} ${period}`
        
        if (slotDate.toDateString() === new Date().toDateString()) {
          return t('therapists.availableToday', { 
            time: formattedTime
          })
        } else {
          return t('therapists.availableOn', { 
            date: slotDate.toLocaleDateString(currentLocale, { weekday: 'short', month: 'short', day: 'numeric' }),
            time: formattedTime
          })
        }
      } catch (error) {
        console.error('Error formatting earliest slot:', error)
        return t('therapists.noSlotsAvailable')
      }
    }

    // Watch for date selection changes
    watch(showDateSelection, (newVal) => {
      if (newVal && availableDates.value.length === 0) {
        loadAvailableDates()
      }
    })

    // Update carousel buttons on scroll
    watch(carouselTrack, () => {
      if (carouselTrack.value) {
        carouselTrack.value.addEventListener('scroll', updateCarouselButtons)
        nextTick(() => updateCarouselButtons())
      }
    })

    // Load earliest slot when component is mounted
    onMounted(() => {
      loadEarliestSlot()
      
      // Debug authentication status
      console.log('=== Authentication Debug ===')
      console.log('Auth store token:', authStore.token)
      console.log('Auth store user:', authStore.user)
      console.log('Is authenticated:', authStore.isAuthenticated)
      console.log('localStorage jalsah_token:', localStorage.getItem('jalsah_token'))
      console.log('localStorage jalsah_user:', localStorage.getItem('jalsah_user'))
      console.log('============================')
    })

    return {
      getAverageRating,
      suitabilityMessage,
      formatEarliestSlot,
      formatTimeSlot,
      locale,
      showDetails,
      showTherapistDetails,
      loading,
      error,
      details,
      carouselTrack,
      canScrollLeft,
      canScrollRight,
      scrollCarousel,
      showDateSelection,
      loadingDates,
      availableDates,
      selectedDate,
      timeSlots,
      bookingLoading,
      earliestSlot,
      selectDate,
      addToCart,
      bookEarliestSlot,
      formatSlot,
      loadTherapistDetails
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* RTL Support */
.rtl {
  direction: rtl;
  text-align: right;
}

.rtl .space-x-reverse > :not([hidden]) ~ :not([hidden]) {
  --tw-space-x-reverse: 1;
}

.rtl .md\:space-x-reverse > :not([hidden]) ~ :not([hidden]) {
  --tw-space-x-reverse: 1;
}

/* RTL specific spacing adjustments */
.rtl .flex.items-center {
  flex-direction: row;
}

.rtl .flex.flex-wrap {
  flex-direction: row;
}

.rtl .gap-1 > * {
  margin-left: 0.25rem;
  margin-right: 0;
}

.rtl .gap-1 > *:first-child {
  margin-left: 0;
}

/* RTL button positioning */
.rtl .absolute.top-2.right-2 {
  right: auto;
  left: 0.5rem;
}

/* RTL layout adjustments */
.rtl .flex.flex-row-reverse {
  flex-direction: row-reverse;
}

.rtl .flex.flex-row-reverse .gap-6 > * {
  margin-left: 1.5rem;
  margin-right: 0;
}

.rtl .flex.flex-row-reverse .gap-6 > *:first-child {
  margin-left: 0;
}

/* RTL button positioning */
.rtl .absolute.top-2.right-2 {
  right: auto;
  left: 0.5rem;
}
</style> 