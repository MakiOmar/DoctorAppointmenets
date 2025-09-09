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
        <!-- Price Badge (removed since price is now shown next to name) -->
        <!-- Order Number Badge (only show on diagnosis results) -->
        <div 
          v-if="showOrderBadge && currentDiagnosisDisplayOrder" 
          class="absolute top-2 left-2 bg-yellow-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-lg"
          :class="locale === 'ar' ? 'right-2 left-auto' : 'left-2 right-auto'"
        >
          {{ therapistPosition }}
        </div>

      </div>

      <!-- Therapist Info -->
      <div class="flex-1 flex flex-col justify-between min-h-32">
        <!-- Top Section: Name, Rating, Bio -->
        <div class="space-y-4">
          <div>
            <div class="flex items-center justify-between mb-2">
              <div>
                <h3 class="text-xl font-semibold text-gray-900">{{ therapist.name }}</h3>
                <p v-if="therapist.doctor_specialty" class="text-sm text-gray-600 mt-1">{{ therapist.doctor_specialty }}</p>
              </div>
              <div class="text-lg font-semibold text-primary-600">
                {{ formatPrice(therapist.price?.others) }}
                <span class="text-sm text-gray-500 font-normal">- 45 {{ $t('common.minutes') }}</span>
              </div>
            </div>
            
            <div v-if="settingsStore && settingsStore.isRatingsEnabled" class="flex items-center gap-2">
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
            <h4 class="text-sm font-semibold text-primary-900 mb-2">{{ $t('therapists.whyBestForDiagnosis') }}</h4>
            <p class="text-sm text-primary-800">
              {{ suitabilityMessage }}
            </p>
          </div>

          <!-- Soonest Available Appointment - Always Visible -->
          <div v-if="earliestSlot" class="mt-4 p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">{{ $t('therapistDetails.earliestAvailable') }}</p>
                <p class="font-medium text-gray-900">{{ formatSlot(earliestSlot) }}</p>
              </div>
              
              <!-- Show different buttons based on cart status -->
              <div v-if="!earliestSlot.inCart">
                <button 
                  @click="bookEarliestSlot"
                  class="btn-primary px-4 py-2 text-sm"
                  :disabled="bookingLoading"
                >
                  <span v-if="bookingLoading" class="flex items-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    {{ $t('common.loading') }}
                  </span>
                  <span v-else>{{ $t('therapistDetails.bookThis') }}</span>
                </button>
              </div>
              
              <div v-else class="flex items-center space-x-2">
                <span class="text-sm text-green-600 font-medium">{{ $t('therapistDetails.inCart') }}</span>
                <button 
                  @click="removeEarliestSlotFromCart"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                  :disabled="bookingLoading"
                >
                  {{ $t('common.remove') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Certificates Section (Always Visible) -->
        <div v-if="therapist.certificates && therapist.certificates.length > 0" class="mt-6">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.certificates') }}</h4>
          
          <!-- Certificates Grid -->
          <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
            <div 
              v-for="(cert, index) in therapist.certificates.slice(0, 8)" 
              :key="cert.id"
              class="bg-white rounded-lg border border-gray-200 overflow-hidden"
            >
              <div class="w-full h-24">
                <img 
                  v-if="cert.is_image"
                  :src="cert.thumbnail_url || cert.url" 
                  :alt="cert.name"
                  class="w-full h-full object-cover cursor-pointer hover:opacity-80 transition-opacity"
                  @click="openLightbox(index)"
                />
                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                  <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Show More Certificates Link -->
          <div v-if="therapist.certificates.length > 8" class="mt-3 text-center">
            <button 
              @click.stop="showTherapistDetails"
              class="text-sm text-primary-600 hover:text-primary-800"
            >
              {{ $t('therapistDetails.viewAllCertificates') }}
            </button>
          </div>
        </div>

        <!-- View Details Button -->
        <div class="flex mt-6">
          <button
            @click.stop="showTherapistDetails"
            class="btn-primary px-6 py-2"
            data-action="view-details"
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
      
      <!-- Booking Section - Always show -->
      <div class="space-y-6">
        <!-- Booking Section -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.bookAppointment') }}</h4>
          

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
                  <div
                    v-for="slot in timeSlots"
                    :key="slot.value"
                    class="relative"
                  >
                    <button
                      v-if="!slot.inCart"
                      @click="addToCart(slot)"
                      :disabled="cartLoading[slot.id]"
                      class="w-full px-3 py-2 text-sm rounded border transition-colors border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span v-if="cartLoading[slot.id]" class="flex items-center justify-center">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></div>
                        {{ $t('common.loading') }}
                      </span>
                      <span v-else>{{ formatTimeSlot(slot.time) }}</span>
                    </button>
                    <div
                      v-else
                      class="w-full px-3 py-2 text-sm rounded border border-green-600 bg-green-50 text-green-700 flex items-center justify-between"
                    >
                      <span>{{ formatTimeSlot(slot.time) }}</span>
                      <button
                        @click="removeFromCart(slot)"
                        :disabled="cartLoading[slot.id]"
                        class="ml-2 text-red-600 hover:text-red-800 text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Remove from cart"
                      >
                        <span v-if="cartLoading[slot.id]" class="flex items-center">
                          <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-red-600 mr-1"></div>
                          {{ $t('common.loading') }}
                        </span>
                        <span v-else>{{ $t('common.remove') }}</span>
                      </button>
                    </div>
                  </div>
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
      
      <!-- Bio Section - Only show when details are loaded -->
      <div v-if="details && details.bio" class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('therapistDetails.bio') }}</h4>
        <p class="text-gray-700 leading-relaxed">{{ details.bio }}</p>
      </div>
    </div>
    
    <!-- Lightbox Component -->
    <Lightbox
      :is-open="lightboxOpen"
      :images="certificateImages"
      :initial-index="lightboxIndex"
      @close="closeLightbox"
    />
  </div>
</template>

<script>
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import StarRating from './StarRating.vue'
import Lightbox from './Lightbox.vue'
import Swal from 'sweetalert2'

export default {
  name: 'TherapistCard',
  components: {
    StarRating,
    Lightbox
  },
  props: {
    therapist: {
      type: Object,
      required: true
    },
    diagnosisId: {
      type: [String, Number],
      default: null
    },
    position: {
      type: Number,
      default: null
    },
    settingsStore: {
      type: Object,
      required: false,
      default: null
    },
    showOrderBadge: {
      type: Boolean,
      default: false
    },
    openTherapistId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['click', 'book', 'show-details', 'hide-details'],
  setup(props, { emit }) {
    const { t, locale } = useI18n()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    const toast = useToast()
    const router = useRouter()
    
    const showDetails = ref(false)
    
    // Computed property to get display_order for current diagnosis
    const currentDiagnosisDisplayOrder = computed(() => {
      if (!props.diagnosisId || !props.therapist.diagnoses) {
        return null
      }
      
      // Find the diagnosis that matches the current diagnosis ID
      const currentDiagnosis = props.therapist.diagnoses.find(diagnosis => 
        diagnosis.id.toString() === props.diagnosisId.toString()
      )
      
      return currentDiagnosis?.display_order || null
    })

    // Computed property to get therapist position (1, 2, 3, etc.)
    const therapistPosition = computed(() => {
      return props.position || null
    })

    // Format price with currency symbol
    const formatPrice = (price) => {
      if (!price || price === 0) {
        return locale.value === 'ar' ? 'اتصل للاستفسار' : 'Contact for pricing'
      }
      
      // Use ج.م for Arabic, $ for English
      const currencySymbol = locale.value === 'ar' ? 'ج.م' : '$'
      return `${currencySymbol}${price}`
    }

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
    const cartLoading = ref({}) // Track loading state for each slot
    const earliestSlot = ref(null)
    
    // Lightbox state
    const lightboxOpen = ref(false)
    const lightboxIndex = ref(0)

    const getAverageRating = (therapist) => {
      if (!props.settingsStore || !props.settingsStore.isRatingsEnabled) {
        return 0
      }
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
      console.log('🔍 showTherapistDetails called')
      console.log('🔍 Current showDetails:', showDetails.value)
      console.log('🔍 Current therapist ID:', props.therapist.id)
      console.log('🔍 Current openTherapistId:', props.openTherapistId)
      
      if (showDetails.value) {
        // If details are currently shown, hide them
        console.log('🔍 Hiding details')
        showDetails.value = false
        emit('hide-details')
      } else {
        // If details are hidden, show them
        console.log('🔍 Showing details')
        showDetails.value = true
        emit('show-details')
        if (!details.value) {
          loadTherapistDetails()
        }
      }
    }

    // Watch for changes in openTherapistId to sync the showDetails state
    watch(() => props.openTherapistId, (newOpenId) => {
      const currentTherapistId = props.therapist.id.toString()
      const newOpenIdStr = newOpenId?.toString()
      
      if (newOpenIdStr === currentTherapistId) {
        // This therapist should be open
        if (!showDetails.value) {
          showDetails.value = true
          if (!details.value) {
            loadTherapistDetails()
          }
        }
      } else {
        // This therapist should be closed
        if (showDetails.value) {
          showDetails.value = false
        }
      }
    }, { immediate: true })

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
        // First, try to use the earliest_slot_data from the therapist object
        if (props.therapist.earliest_slot_data) {
          earliestSlot.value = {
            ...props.therapist.earliest_slot_data,
            id: parseInt(props.therapist.earliest_slot_data.id) // Ensure ID is a number
          }
          // Check if this slot is in cart
          if (earliestSlot.value.id) {
            await checkSlotCartStatus(earliestSlot.value)
          }
          return
        }
        
        // Fallback to API call if earliest_slot_data is not available
        const response = await fetch(`/api/ai/therapists/${props.therapist.id}/earliest-slot`)
        const data = await response.json()
        
        if (data.success && data.data) {
          earliestSlot.value = data.data
          return
        }
        
        // Final fallback to therapist.earliest_slot if no timetable slots found
        // Only use this if the value is meaningful (not 0)
        if (props.therapist.earliest_slot && parseInt(props.therapist.earliest_slot) > 0) {
          // Convert the earliest_slot value to a proper slot object
          // The earliest_slot field contains minutes from now
          const minutesFromNow = parseInt(props.therapist.earliest_slot)
          const now = new Date()
          const earliestTime = new Date(now.getTime() + minutesFromNow * 60000)
          
          earliestSlot.value = {
            date: earliestTime.toISOString().split('T')[0],
            time: earliestTime.toTimeString().split(' ')[0].substring(0, 5),
            period: 45 // Default period for AI sessions
          }
        }
      } catch (err) {
        // Silently fail - earliest slot is not critical
      }
    }

    const loadAvailableDates = async () => {
      loadingDates.value = true
      try {
        // Use the actual available dates from the therapist data
        if (props.therapist.available_dates && Array.isArray(props.therapist.available_dates)) {
          // Deduplicate dates by creating a Map with date as key
          const dateMap = new Map()
          
          props.therapist.available_dates.forEach(dateInfo => {
            const dateObj = new Date(dateInfo.date)
            const dateKey = dateInfo.date
            
            // If date doesn't exist or this slot has earlier time, update it
            if (!dateMap.has(dateKey) || 
                (dateInfo.earliest_time && dateMap.get(dateKey).earliest_time && 
                 dateInfo.earliest_time < dateMap.get(dateKey).earliest_time)) {
              dateMap.set(dateKey, {
                value: dateInfo.date,
                day: formatShortDay(dateObj),
                date: formatShortDate(dateObj),
                earliest_time: dateInfo.earliest_time,
                slot_count: dateInfo.slot_count
              })
            }
          })
          
          // Convert Map back to array and sort by date
          availableDates.value = Array.from(dateMap.values()).sort((a, b) => 
            new Date(a.value) - new Date(b.value)
          )
        } else {
          // Fallback to generating dates from earliest_slot_data
          if (props.therapist.earliest_slot_data && props.therapist.earliest_slot_data.date) {
            const baseDate = new Date(props.therapist.earliest_slot_data.date)
            const dates = []
            
            // Generate next 7 days starting from the earliest slot date
            for (let i = 0; i < 7; i++) {
              const date = new Date(baseDate)
              date.setDate(baseDate.getDate() + i)
              
              dates.push({
                value: date.toISOString().split('T')[0],
                        day: formatShortDay(date),
        date: formatShortDate(date)
              })
            }
            
            availableDates.value = dates
          } else {
            availableDates.value = []
          }
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
        // Since the time-slots endpoint has routing issues, let's generate time slots
        // based on the available_dates data we already have
        if (props.therapist.available_dates && Array.isArray(props.therapist.available_dates)) {
          const selectedDateInfo = props.therapist.available_dates.find(d => d.date === date.value)
          
          if (selectedDateInfo) {
            // Create a time slot using the real slot data from the database
            const timeSlot = {
              id: parseInt(selectedDateInfo.slot_id), // Ensure ID is a number
              value: selectedDateInfo.time,
              time: selectedDateInfo.time,
              end_time: selectedDateInfo.end_time,
              period: selectedDateInfo.period,
              clinic: selectedDateInfo.clinic,
              attendance_type: selectedDateInfo.attendance_type,
              date_time: `${date.value} ${selectedDateInfo.time}`,
              inCart: false
            }
            
            // Check if this slot is in the user's cart
            await checkSlotCartStatus(timeSlot)
            timeSlots.value = [timeSlot]
          } else {
            timeSlots.value = []
          }
        } else {
          // Fallback to API call
          const response = await fetch(`/api/ai/therapists/${props.therapist.id}/time-slots?date=${date.value}`)
          const data = await response.json()
          if (data.success && Array.isArray(data.data)) {
            const slots = data.data.map(slot => ({
              ...slot,
              id: parseInt(slot.id), // Ensure ID is a number
              inCart: false
            }))
            
            // Check cart status for all slots
            await checkSlotsCartStatus(slots)
            timeSlots.value = slots
          } else {
            timeSlots.value = []
          }
        }
      } catch (err) {
        timeSlots.value = []
      } finally {
        loadingDates.value = false
      }
    }

    // Check if a single slot is in the user's cart
    const checkSlotCartStatus = async (slot) => {
      if (!authStore.isAuthenticated) {
        slot.inCart = false
        return
      }
      
      try {
        // Check if this slot is in the user's cart by calling the cart API
        const response = await api.get('/wp-json/jalsah-ai/v1/get-user-cart', {
          params: { user_id: authStore.user.id }
        })
        
        if (response.data.success && Array.isArray(response.data.data)) {
          const cartItems = response.data.data
          // Debug logging
          console.log('🔍 Checking cart status for slot:', slot.id)
          console.log('🔍 Cart items:', cartItems.map(item => ({ ID: item.ID, date_time: item.date_time })))
          
          slot.inCart = cartItems.some(item => parseInt(item.ID) === parseInt(slot.id))
          console.log('🔍 Slot in cart:', slot.inCart)
        } else {
          slot.inCart = false
        }
      } catch (err) {
        console.error('Error checking cart status:', err)
        slot.inCart = false
      }
    }
    
    // Check cart status for multiple slots
    const checkSlotsCartStatus = async (slots) => {
      if (!authStore.isAuthenticated || slots.length === 0) {
        slots.forEach(slot => slot.inCart = false)
        return
      }
      
      try {
        // Get user's cart once
        const response = await api.get('/wp-json/jalsah-ai/v1/get-user-cart', {
          params: { user_id: authStore.user.id }
        })
        
        if (response.data.success && Array.isArray(response.data.data)) {
          const cartItems = response.data.data
          const cartSlotIds = new Set(cartItems.map(item => parseInt(item.ID)))
          
          // Debug logging
          console.log('🔍 Checking cart status for slots:', slots.map(s => s.id))
          console.log('🔍 Cart slot IDs:', Array.from(cartSlotIds))
          
          // Update each slot's cart status
          slots.forEach(slot => {
            slot.inCart = cartSlotIds.has(parseInt(slot.id))
            console.log(`🔍 Slot ${slot.id} in cart:`, slot.inCart)
          })
        } else {
          slots.forEach(slot => slot.inCart = false)
        }
      } catch (err) {
        console.error('Error checking cart status:', err)
        slots.forEach(slot => slot.inCart = false)
      }
    }

    const addToCart = async (slot) => {
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      // Set loading state for this slot
      cartLoading.value[slot.id] = true
      
      try {
        // Use the cart store with new REST API
        const result = await cartStore.addToCart({
          slot_id: slot.id,
          user_id: authStore.user.id
        })
        
        console.log('🎯 Cart result:', result)
        
        if (result.success) {
          slot.inCart = true
          toast.success(t('therapistDetails.appointmentAdded'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else if (result.requiresConfirmation) {
          // Show confirmation dialog for different therapist
          const confirmed = await showDifferentTherapistConfirmation(t('therapistDetails.differentTherapistMessage'))
          if (confirmed) {
            // User confirmed, add to cart with confirmation
            const confirmResult = await cartStore.addToCartWithConfirmation({
              slot_id: slot.id,
              user_id: authStore.user.id
            })
            
            if (confirmResult.success) {
              slot.inCart = true
              toast.success(t('therapistDetails.appointmentAdded'))
              // Emit event to update cart
              window.dispatchEvent(new CustomEvent('cart-updated'))
            } else {
              toast.error(confirmResult.message || t('common.error'))
            }
          }
        } else {
          toast.error(result.message || t('common.error'))
        }
      } catch (err) {
        toast.error(t('common.error'))
      } finally {
        // Clear loading state
        cartLoading.value[slot.id] = false
      }
    }

    const removeFromCart = async (slot) => {
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      // Set loading state for this slot
      cartLoading.value[slot.id] = true
      
      try {
        // Use the cart store with new REST API
        const result = await cartStore.removeFromCart(slot.id, authStore.user.id)
        
        if (result.success) {
          slot.inCart = false
          toast.success(t('therapistDetails.appointmentRemoved'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else {
          toast.error(result.message || t('common.error'))
        }
      } catch (err) {
        toast.error(t('common.error'))
      } finally {
        // Clear loading state
        cartLoading.value[slot.id] = false
      }
    }

    const bookEarliestSlot = async () => {
      if (!earliestSlot.value) return
      
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      bookingLoading.value = true
      try {
        // Use the cart store with new REST API
        const result = await cartStore.addToCart({
          slot_id: earliestSlot.value.id,
          user_id: authStore.user.id
        })
        
        if (result.success) {
          toast.success(t('therapistDetails.appointmentAdded'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
          
          // Redirect directly to checkout page
          router.push('/checkout')
        } else if (result.requiresConfirmation) {
          // Show confirmation dialog for different therapist
          const confirmed = await showDifferentTherapistConfirmation(t('therapistDetails.differentTherapistMessage'))
          if (confirmed) {
            // User confirmed, add to cart with confirmation
            const confirmResult = await cartStore.addToCartWithConfirmation({
              slot_id: earliestSlot.value.id,
              user_id: authStore.user.id
            })
            
            if (confirmResult.success) {
              toast.success(t('therapistDetails.appointmentAdded'))
              // Emit event to update cart
              window.dispatchEvent(new CustomEvent('cart-updated'))
              
              // Redirect directly to checkout page
              router.push('/checkout')
            } else {
              toast.error(confirmResult.message || t('common.error'))
            }
          }
        } else {
          // Check if it's a token expiration error
          if (result.message && result.message.includes('Please login again')) {
            toast.error(t('common.sessionExpired'))
            // Clear auth data and redirect to login
            authStore.logout()
            // Redirect to login page
            window.location.href = '/login'
          } else {
            toast.error(result.message || t('common.error'))
          }
        }
      } catch (err) {
        toast.error(t('common.error'))
      } finally {
        bookingLoading.value = false
      }
    }

    // Show confirmation dialog for different therapist
    const showDifferentTherapistConfirmation = async (message) => {
      console.log('🔔 Showing confirmation dialog:', message)
      // Use SweetAlert2 for confirmation dialog
      const result = await Swal.fire({
        title: t('therapistDetails.differentTherapistTitle'),
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: t('common.yes'),
        cancelButtonText: t('common.cancel'),
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
      })
      
      console.log('🔔 Confirmation result:', result.isConfirmed)
      return result.isConfirmed
    }

    const removeEarliestSlotFromCart = async () => {
      if (!earliestSlot.value || !earliestSlot.value.id) return
      
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      bookingLoading.value = true
      try {
        // Use the cart store to remove from cart
        const result = await cartStore.removeFromCart(earliestSlot.value.id, authStore.user.id)
        
        if (result.success) {
          earliestSlot.value.inCart = false
          toast.success(t('therapistDetails.appointmentRemoved'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else {
          toast.error(result.message || t('common.error'))
        }
      } catch (err) {
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
        // Invalid time format
        return time // Return original time if parsing fails
      }
      
      const period = hours >= 12 ? t('dateTime.pm') : t('dateTime.am')
      const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      return `${displayHours}:${minutes} ${period}`
    }

    const formatShortDay = (date) => {
      const isArabic = locale.value === 'ar'
      
      if (isArabic) {
        const arabicFullDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
        return arabicFullDays[date.getDay()]
      } else {
        return date.toLocaleDateString('en-US', { weekday: 'short' })
      }
    }

    const formatShortDate = (date) => {
      const isArabic = locale.value === 'ar'
      
      if (isArabic) {
        const arabicFullMonths = [
          'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
          'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ]
        const monthName = arabicFullMonths[date.getMonth()]
        const day = date.getDate()
        return `${day} ${monthName}`
      } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      }
    }

    const formatShortDateWithDay = (date) => {
      const isArabic = locale.value === 'ar'
      
      if (isArabic) {
        const arabicFullDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
        const arabicFullMonths = [
          'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
          'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ]
        const dayName = arabicFullDays[date.getDay()]
        const monthName = arabicFullMonths[date.getMonth()]
        const day = date.getDate()
        return `${dayName}، ${day} ${monthName}`
      } else {
        return date.toLocaleDateString('en-US', { 
          weekday: 'short', 
          month: 'short', 
          day: 'numeric' 
        })
      }
    }

    const calculateEndTime = (startTime, durationMinutes) => {
      const [hours, minutes] = startTime.split(':').map(Number)
      const startDate = new Date()
      startDate.setHours(hours, minutes, 0, 0)
      
      const endDate = new Date(startDate.getTime() + durationMinutes * 60000)
      return endDate.toTimeString().slice(0, 5)
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
      // Handle both "09:00" and "09:00:00" formats
      const timeParts = time.split(':')
      const hours = parseInt(timeParts[0])
      const minutes = parseInt(timeParts[1]) // Parse minutes as integer
      
      if (isNaN(hours) || isNaN(minutes)) {
        return t('therapists.noSlotsAvailable')
      }
      
      const period = hours >= 12 ? t('dateTime.pm') : t('dateTime.am')
      const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      const formattedTime = `${displayHours}:${minutes} ${period}`
      
      const dateStr = formatShortDateWithDay(date)
      
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
      // Only use this if the value is meaningful (not 0)
      if (!slotData && therapist.earliest_slot && parseInt(therapist.earliest_slot) > 0) {
        try {
          // Convert minutes from now to actual date/time
          const minutesFromNow = parseInt(therapist.earliest_slot)
          const now = new Date()
          const earliestTime = new Date(now.getTime() + minutesFromNow * 60000)
          
          if (!isNaN(earliestTime.getTime())) {
            slotDate = earliestTime
            slotData = {
              date: earliestTime.toISOString().split('T')[0],
              time: earliestTime.toTimeString().split(' ')[0].substring(0, 5)
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
            date: formatShortDateWithDay(slotDate),
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

    // Load earliest slot and therapist details when component is mounted
    onMounted(() => {
      loadEarliestSlot()
      loadTherapistDetails() // Load details since booking form is shown by default
      
      // Listen for cart updates to refresh slot cart status
      const handleCartUpdate = () => {
        if (timeSlots.value.length > 0) {
          checkSlotsCartStatus(timeSlots.value)
        }
      }
      
      window.addEventListener('cart-updated', handleCartUpdate)
      
      // Cleanup listener on unmount
      onUnmounted(() => {
        window.removeEventListener('cart-updated', handleCartUpdate)
      })
    })

    // Lightbox functions
    const openLightbox = (index) => {
      lightboxIndex.value = index
      lightboxOpen.value = true
    }

    const closeLightbox = () => {
      lightboxOpen.value = false
    }

    // Get certificate images for lightbox
    const certificateImages = computed(() => {
      if (!props.therapist.certificates) return []
      
      return props.therapist.certificates
        .filter(cert => cert.is_image)
        .map(cert => ({
          url: cert.url,
          name: cert.name,
          alt: cert.name
        }))
    })

    return {
      getAverageRating,
      suitabilityMessage,
      currentDiagnosisDisplayOrder,
      therapistPosition,
      formatPrice,
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
      cartLoading,
      earliestSlot,
      selectDate,
      addToCart,
      removeFromCart,
      bookEarliestSlot,
      removeEarliestSlotFromCart,
      formatSlot,
      loadTherapistDetails,
      // Lightbox
      lightboxOpen,
      lightboxIndex,
      openLightbox,
      closeLightbox,
      certificateImages
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