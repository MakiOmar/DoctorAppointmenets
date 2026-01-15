<template>
  <div class="space-y-6">
    <!-- Nearest Available Appointment Section -->
    <div v-if="nearestSlot" class="space-y-4">
      <!-- Section Title -->
      <div class="bg-secondary-500 rounded-lg px-4 py-2 text-center w-[300px] mx-auto">
        <h3 class="text-primary-500 font-bold text-sm">{{ $t('therapistDetails.nearestAvailable') }}</h3>
      </div>

      <!-- Date and Time Display -->
      <div class="text-white text-center space-y-1">
        <div class="text-lg font-semibold">{{ formatFullDate(nearestSlot.date) }}</div>
        <div class="text-lg font-semibold">{{ formatTimeSlot(nearestSlot.time) }} {{ $t('dateTime.egyptTime') }}</div>
      </div>

      <!-- Booking Button or Status -->
      <div v-if="!nearestSlot.inCart" class="flex justify-center">
        <button
          @click="addNearestToCart"
          :disabled="cartLoading[nearestSlot.id]"
          class="bg-white text-primary-500 px-6 py-1 rounded-lg font-medium hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-jalsah2"
        >
          <span v-if="cartLoading[nearestSlot.id]" class="flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500 mr-2"></div>
            {{ $t('common.loading') }}
          </span>
          <span class="font-jalsah2 text-[20px] font-semibold" v-else>{{ $t('therapistDetails.bookThisAppointment') }}</span>
        </button>
      </div>

      <!-- Added to Cart Status -->
      <div v-else class="bg-secondary-500 rounded-lg px-4 py-1 flex items-center justify-between w-[300px] mx-auto">
        <span class="text-primary-500 text-[20px] font-jalsah1 leading-tight">{{ formatTimeSlot(nearestSlot.time) }}</span>
        <div class="flex items-center gap-2">
          <span class="text-[#00740b] text-[20px] font-jalsah2">{{ $t('therapistDetails.addedToCart') }}</span>
          <button
            @click="removeNearestFromCart"
            :disabled="cartLoading[nearestSlot.id]"
            class="text-[#b50000] hover:text-[#b50000] font-medium  disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="cartLoading[nearestSlot.id]" class="flex items-center text-[20px]">
              <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-[#b50000] mr-1"></div>
            </span>
            <span class="font-jalsah2 text-[20px] font-semibold" v-else>{{ $t('common.delete') }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- Separator -->
    <div v-if="nearestSlot && otherDates.length > 0" class="h-px bg-white"></div>

    <!-- Other Appointments Section -->
    <div v-if="otherDates.length > 0" class="space-y-4">
      <!-- Section Title -->
      <div class="bg-secondary-500 rounded-lg px-4 py-2 text-center w-[300px] mx-auto">
        <h3 class="text-primary-500 font-bold text-sm">{{ $t('therapistDetails.otherAppointments') }}</h3>
      </div>

      <!-- Date Picker with Navigation -->
      <div class="flex items-center justify-center gap-2">
        <!-- Previous Arrow (Left side) -->
        <button
          v-if="maxDateScrollIndex > 0"
          @click="scrollDatesLeft"
          :disabled="dateScrollIndex === 0"
          class="flex-shrink-0 z-10 hover:opacity-80 transition-opacity order-1 disabled:opacity-30 disabled:cursor-not-allowed"
        >
          <img 
            :src="locale === 'ar' ? '/right-chevron-icon.png' : '/left-chevron-icon.png'"
            :alt="locale === 'ar' ? 'Next' : 'Previous'"
            class="h-6"
          />
        </button>

        <!-- Date Cards Container (with overflow hidden) -->
        <div class="flex-1 overflow-x-hidden" :class="locale === 'ar' ? 'order-2' : 'order-2'">
          <div 
            class="flex gap-3 justify-center px-2" 
            ref="dateScrollContainer"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @touchend="handleTouchEnd"
          >
            <button
              v-for="date in visibleDates"
              :key="date.value"
              @click="selectDate(date)"
              class="flex-shrink-0 px-0 py-3 rounded-lg text-sm font-medium transition-colors w-[65px]"
              :class="selectedDate?.value === date.value
                ? 'bg-secondary-500 text-primary-500'
                : 'bg-white text-primary-500 hover:bg-gray-50 border border-primary-500'"
            >
              <div class="text-center">
                <div class="font-semibold">{{ date.day }}</div>
                <div class="text-xs mt-1">
                  <div>{{ getDateDay(date.date) }}</div>
                  <div>{{ getDateMonth(date.date) }}</div>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Next Arrow (Right side) -->
        <button
          v-if="maxDateScrollIndex > 0"
          @click="scrollDatesRight"
          :disabled="dateScrollIndex >= maxDateScrollIndex"
          class="flex-shrink-0 z-10 hover:opacity-80 transition-opacity order-3 disabled:opacity-30 disabled:cursor-not-allowed"
        >
          <img 
            :src="locale === 'ar' ? '/left-chevron-icon.png' : '/right-chevron-icon.png'"
            :alt="locale === 'ar' ? 'Previous' : 'Next'"
            class="h-6"
          />
        </button>
      </div>

      <!-- Divider -->
      <div v-if="selectedDate && otherTimeSlots.length > 0" class="h-px bg-white"></div>

      <!-- Timezone Note -->
      <p v-if="selectedDate && otherTimeSlots.length > 0" class="text-white text-center text-[20px] font-jalsah2 font-semibold">({{ $t('therapistDetails.allAppointmentsEgyptTime') }})</p>

      <!-- Time Slots -->
      <div v-if="selectedDate && otherTimeSlots.length > 0" class="space-y-2">
        <div
          v-for="slot in otherTimeSlots"
          :key="slot.id || slot.value"
          class="w-full"
        >
          <button
            v-if="!slot.inCart"
            @click="addToCart(slot)"
            :disabled="cartLoading[slot.id]"
            class="w-full px-4 py-3 rounded-lg text-sm font-medium transition-colors bg-white text-primary-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="cartLoading[slot.id]" class="flex items-center justify-center">
              <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500 mr-2"></div>
              {{ $t('common.loading') }}
            </span>
            <span v-else>{{ formatTimeSlot(slot.time) }}</span>
          </button>

          <!-- Added to Cart Status -->
          <div
            v-else
            class="bg-secondary-500 rounded-lg px-4 py-1 flex items-center justify-between w-[300px] mx-auto"
          >
            <span class="text-primary-500 text-[20px] font-jalsah1 leading-tight">{{ formatTimeSlot(slot.time) }}</span>
            <div class="flex items-center gap-2">
              <span class="text-[#00740b] text-[20px] font-jalsah2">{{ $t('therapistDetails.addedToCart') }}</span>
              <button
                @click="removeFromCart(slot)"
                :disabled="cartLoading[slot.id]"
                class="text-[#b50000] hover:text-[#b50000] font-medium disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="cartLoading[slot.id]" class="flex items-center text-[20px]">
                  <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-[#b50000] mr-1"></div>
                </span>
                <span class="font-jalsah2 text-[20px] font-semibold " v-else>{{ $t('common.delete') }}</span>
              </button>
              </div>
          </div>
        </div>
      </div>

      <!-- No Time Slots -->
      <div v-else-if="selectedDate && !loadingDates && otherTimeSlots.length === 0" class="text-center py-8 text-white">
        {{ $t('therapistDetails.noTimeSlots') }}
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loadingDates" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
      <p class="text-white mt-4">{{ $t('therapistDetails.loadingDates') }}</p>
    </div>

    <!-- No Available Dates -->
    <div v-else-if="!nearestSlot && otherDates.length === 0" class="text-center py-12 text-white">
      {{ $t('therapistDetails.noAvailableDates') }}
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import { formatGregorianDate } from '@/utils/dateFormatter'
import Swal from 'sweetalert2'

export default {
  name: 'BookingContent',
  props: {
    therapist: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const { locale, t } = useI18n()
    const cartStore = useCartStore()
    const authStore = useAuthStore()
    const settingsStore = useSettingsStore()
    const toast = useToast()
    const loadingDates = ref(false)
    const availableDates = ref([])
    const selectedDate = ref(null)
    const timeSlots = ref([])
    const cartLoading = ref({})
    const dateScrollIndex = ref(0)
    const dateScrollContainer = ref(null)
    
    // Window width ref for reactivity
    const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 768)
    
    // Responsive dates per view: 3 on mobile, 4 on desktop
    const datesPerView = computed(() => {
      if (windowWidth.value < 768) {
        return 3
      }
      return 4
    })
    
    // Touch navigation state
    const touchStartX = ref(0)
    const touchStartY = ref(0)
    const touchEndX = ref(0)
    const touchEndY = ref(0)

    const getNearestSlotInfo = () => {
      if (props.therapist.earliest_slot_data && props.therapist.earliest_slot_data.date && props.therapist.earliest_slot_data.time) {
        return {
          date: props.therapist.earliest_slot_data.date,
          time: props.therapist.earliest_slot_data.time
        }
      }
      return null
    }

    const nearestSlot = ref(null)

    const initializeNearestSlot = () => {
      const nearestInfo = getNearestSlotInfo()
      if (!nearestInfo) {
        nearestSlot.value = null
        return
      }

      // Find the actual slot ID from available_dates
      let slotId = null
      if (props.therapist.available_dates && Array.isArray(props.therapist.available_dates)) {
        const dateInfo = props.therapist.available_dates.find(d => d.date === nearestInfo.date)
        if (dateInfo) {
          if (dateInfo.slots && Array.isArray(dateInfo.slots)) {
            const slot = dateInfo.slots.find(s => s.time === nearestInfo.time)
            if (slot) slotId = parseInt(slot.slot_id)
          } else if (dateInfo.time === nearestInfo.time) {
            slotId = parseInt(dateInfo.slot_id)
          }
        }
      }

      nearestSlot.value = {
        id: slotId,
        date: nearestInfo.date,
        time: nearestInfo.time,
        inCart: false
      }
    }

    const otherDates = computed(() => {
      const nearestInfo = getNearestSlotInfo()
      if (!nearestInfo) return availableDates.value

      return availableDates.value.filter(date => date.value !== nearestInfo.date)
    })

    const visibleDates = computed(() => {
      const start = dateScrollIndex.value
      const end = start + datesPerView.value
      return otherDates.value.slice(start, end)
    })

    const maxDateScrollIndex = computed(() => {
      return Math.max(0, otherDates.value.length - datesPerView.value)
    })

    const otherTimeSlots = computed(() => {
      if (!selectedDate.value) return []
      return timeSlots.value.filter(slot => {
        const nearestInfo = getNearestSlotInfo()
        if (nearestInfo && slot.date === nearestInfo.date && slot.time === nearestInfo.time) {
          return false
        }
        return slot.date === selectedDate.value.value
      })
    })

    const formatTimeSlot = (time) => {
      if (!time) return ''
      const timeParts = time.split(':')
      const hours = parseInt(timeParts[0])
      const minutes = parseInt(timeParts[1])
      
      if (isNaN(hours) || isNaN(minutes)) {
        return time
      }
      
      const isArabic = locale.value === 'ar'
      const period = isArabic ? (hours >= 12 ? 'م' : 'ص') : (hours >= 12 ? 'PM' : 'AM')
      const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      const formattedMinutes = minutes.toString().padStart(2, '0')
      return `${displayHours}:${formattedMinutes} ${period}`
    }

    const formatFullDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      if (isNaN(date.getTime())) return ''

      if (locale.value === 'ar') {
        const arabicDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
        const arabicMonths = [
          'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
          'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ]
        const dayName = arabicDays[date.getDay()]
        const monthName = arabicMonths[date.getMonth()]
        const day = date.getDate()
        const year = date.getFullYear()
        return `${dayName}، ${day} ${monthName} ${year}`
      } else {
        return date.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      }
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
      return formatGregorianDate(date, locale.value, { 
        month: 'short', 
        day: 'numeric' 
      })
    }

    const getDateDay = (dateString) => {
      if (!dateString) return ''
      // Split by space and get the first part (day number)
      const parts = dateString.trim().split(/\s+/)
      return parts[0] || ''
    }

    const getDateMonth = (dateString) => {
      if (!dateString) return ''
      // Split by space and get everything after the first part (month name)
      const parts = dateString.trim().split(/\s+/)
      return parts.slice(1).join(' ') || ''
    }

    const hasAvailableSlots = (dateKey) => {
      if (!props.therapist.available_dates || !Array.isArray(props.therapist.available_dates)) {
        return false
      }
      
      const dateEntries = props.therapist.available_dates.filter(d => d.date === dateKey)
      if (dateEntries.length === 0) {
        return false
      }
      
      const nearestSlotInfo = getNearestSlotInfo()
      
      for (const dateInfo of dateEntries) {
        if (dateInfo.slots && Array.isArray(dateInfo.slots)) {
          const availableSlots = dateInfo.slots.filter(slot => {
            if (nearestSlotInfo && 
                nearestSlotInfo.date === dateKey && 
                nearestSlotInfo.time === slot.time) {
              return false
            }
            if (slot.attendance_type === 'offline' && slot.period === 45) {
              return false
            }
            return true
          })
          if (availableSlots.length > 0) {
            return true
          }
        } else {
          if (nearestSlotInfo && 
              nearestSlotInfo.date === dateKey && 
              nearestSlotInfo.time === dateInfo.time) {
            continue
          }
          if (dateInfo.attendance_type === 'offline' && dateInfo.period === 45) {
            continue
          }
          return true
        }
      }
      return false
    }

    const loadAvailableDates = async () => {
      loadingDates.value = true
      try {
        if (props.therapist.available_dates && Array.isArray(props.therapist.available_dates)) {
          const dateMap = new Map()
          
          props.therapist.available_dates.forEach(dateInfo => {
            const dateObj = new Date(dateInfo.date)
            const dateKey = dateInfo.date
            
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
          
          const filteredDates = Array.from(dateMap.values()).filter(date => {
            return hasAvailableSlots(date.value)
          })
          
          availableDates.value = filteredDates.sort((a, b) => 
            new Date(a.value) - new Date(b.value)
          )
        } else {
          if (props.therapist.earliest_slot_data && props.therapist.earliest_slot_data.date) {
            const baseDate = new Date(props.therapist.earliest_slot_data.date)
            const dates = []
            
            for (let i = 0; i < 7; i++) {
              const date = new Date(baseDate)
              date.setDate(baseDate.getDate() + i)
              const dateString = date.toISOString().split('T')[0]
              
              dates.push({
                value: dateString,
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

    const checkSlotsCartStatus = async (slots) => {
      if (!authStore.isAuthenticated || slots.length === 0) {
        slots.forEach(slot => slot.inCart = false)
        return
      }
      
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/get-user-cart', {
          params: { user_id: authStore.user.id }
        })
        
        if (response.data.success && Array.isArray(response.data.data)) {
          const cartItems = response.data.data
          const cartSlotIds = new Set(cartItems.map(item => parseInt(item.ID)))
          
          slots.forEach(slot => {
            slot.inCart = cartSlotIds.has(parseInt(slot.id))
          })
        } else {
          slots.forEach(slot => slot.inCart = false)
        }
      } catch (err) {
        console.error('Error checking cart status:', err)
        slots.forEach(slot => slot.inCart = false)
      }
    }

    const selectDate = async (date) => {
      selectedDate.value = date
      loadingDates.value = true
      try {
        if (props.therapist.available_dates && Array.isArray(props.therapist.available_dates)) {
          const selectedDateInfo = props.therapist.available_dates.find(d => d.date === date.value)
          
          if (selectedDateInfo) {
            const nearestSlotInfo = getNearestSlotInfo()
            
            if (selectedDateInfo.slots && Array.isArray(selectedDateInfo.slots)) {
              const processedSlots = selectedDateInfo.slots
                .map(slot => ({
                  id: parseInt(slot.slot_id),
                  value: slot.time,
                  time: slot.time,
                  date: date.value,
                  end_time: slot.end_time,
                  period: slot.period,
                  clinic: slot.clinic,
                  attendance_type: slot.attendance_type,
                  date_time: `${date.value} ${slot.time}`,
                  inCart: false
                }))
                .filter(slot => {
                  if (nearestSlotInfo && 
                      nearestSlotInfo.date === date.value && 
                      nearestSlotInfo.time === slot.time) {
                    return false
                  }
                  if (slot.attendance_type === 'offline' && slot.period === 45) {
                    return false
                  }
                  return true
                })
              
              await checkSlotsCartStatus(processedSlots)
              timeSlots.value = processedSlots
            } else {
              const timeSlot = {
                id: parseInt(selectedDateInfo.slot_id),
                value: selectedDateInfo.time,
                time: selectedDateInfo.time,
                date: date.value,
                end_time: selectedDateInfo.end_time,
                period: selectedDateInfo.period,
                clinic: selectedDateInfo.clinic,
                attendance_type: selectedDateInfo.attendance_type,
                date_time: `${date.value} ${selectedDateInfo.time}`,
                inCart: false
              }
              
              if (nearestSlotInfo && 
                  nearestSlotInfo.date === date.value && 
                  nearestSlotInfo.time === selectedDateInfo.time) {
                timeSlots.value = []
                return
              }
              
              if (timeSlot.attendance_type === 'offline' && timeSlot.period === 45) {
                timeSlots.value = []
                return
              }
              
              await checkSlotsCartStatus([timeSlot])
              timeSlots.value = [timeSlot]
            }
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

    const scrollDatesLeft = () => {
      if (dateScrollIndex.value > 0) {
        dateScrollIndex.value--
      }
    }

    const scrollDatesRight = () => {
      if (dateScrollIndex.value < maxDateScrollIndex.value) {
        dateScrollIndex.value++
      }
    }
    
    // Touch navigation handlers
    const handleTouchStart = (e) => {
      touchStartX.value = e.touches[0].clientX
      touchStartY.value = e.touches[0].clientY
    }
    
    const handleTouchMove = (e) => {
      // Allow default scrolling behavior
    }
    
    const handleTouchEnd = (e) => {
      touchEndX.value = e.changedTouches[0].clientX
      touchEndY.value = e.changedTouches[0].clientY
      handleSwipe()
    }
    
    const handleSwipe = () => {
      const deltaX = touchStartX.value - touchEndX.value
      const deltaY = touchStartY.value - touchEndY.value
      
      // Only handle horizontal swipes (ignore vertical scrolling)
      if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
        if (deltaX > 0) {
          // Swipe left - go to next
          scrollDatesRight()
        } else {
          // Swipe right - go to previous
          scrollDatesLeft()
        }
      }
    }

    const showDifferentTherapistConfirmation = async (message) => {
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
      
      return result.isConfirmed
    }

    const addToCart = async (slot) => {
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      cartLoading.value[slot.id] = true
      
      try {
        const countryCode = settingsStore.userCountryCode || 'EG'
        const result = await cartStore.addToCart({
          slot_id: slot.id,
          user_id: authStore.user.id,
          country_code: countryCode
        })
        
        if (result.success) {
          slot.inCart = true
          toast.success(t('therapistDetails.appointmentAdded'), {
            timeout: 8000
          })
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else if (result.requiresConfirmation) {
          const confirmed = await showDifferentTherapistConfirmation(t('therapistDetails.differentTherapistMessage'))
          if (confirmed) {
            const countryCode = settingsStore.userCountryCode || 'EG'
            const confirmResult = await cartStore.addToCartWithConfirmation({
              slot_id: slot.id,
              user_id: authStore.user.id,
              country_code: countryCode
            })
            
            if (confirmResult.success) {
              slot.inCart = true
              toast.success(t('therapistDetails.appointmentAdded'), {
                timeout: 8000
              })
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
        cartLoading.value[slot.id] = false
      }
    }

    const removeFromCart = async (slot) => {
      if (!authStore.isAuthenticated) {
        toast.error(t('common.pleaseLogin'))
        return
      }
      
      cartLoading.value[slot.id] = true
      
      try {
        const result = await cartStore.removeFromCart(slot.id, authStore.user.id)
        
        if (result.success) {
          slot.inCart = false
          toast.success(t('therapistDetails.appointmentRemoved'))
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else {
          toast.error(result.message || t('common.error'))
        }
      } catch (err) {
        toast.error(t('common.error'))
      } finally {
        cartLoading.value[slot.id] = false
      }
    }

    const addNearestToCart = async () => {
      if (!nearestSlot.value || !nearestSlot.value.id) return

      const slot = {
        id: nearestSlot.value.id,
        date: nearestSlot.value.date,
        time: nearestSlot.value.time,
        inCart: false
      }

      await addToCart(slot)
      // Update nearest slot status
      if (nearestSlot.value) {
        nearestSlot.value.inCart = slot.inCart
      }
    }

    const removeNearestFromCart = async () => {
      if (!nearestSlot.value || !nearestSlot.value.id) return

      await removeFromCart({ id: nearestSlot.value.id })
      if (nearestSlot.value) {
        nearestSlot.value.inCart = false
      }
    }

    // Check nearest slot cart status
    const checkNearestSlotCartStatus = async () => {
      if (!nearestSlot.value || !nearestSlot.value.id || !authStore.isAuthenticated) {
        if (nearestSlot.value) {
          nearestSlot.value.inCart = false
        }
        return
      }

      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/get-user-cart', {
          params: { user_id: authStore.user.id }
        })
        
        if (response.data.success && Array.isArray(response.data.data)) {
          const cartItems = response.data.data
          const cartSlotIds = new Set(cartItems.map(item => parseInt(item.ID)))
          if (nearestSlot.value) {
            nearestSlot.value.inCart = cartSlotIds.has(nearestSlot.value.id)
          }
        }
      } catch (err) {
        console.error('Error checking nearest slot cart status:', err)
        if (nearestSlot.value) {
          nearestSlot.value.inCart = false
        }
      }
    }

    // Handle window resize to update dates per view
    const handleResize = () => {
      if (typeof window !== 'undefined') {
        windowWidth.value = window.innerWidth
        // Reset scroll index if needed when switching between mobile/desktop
        if (dateScrollIndex.value > maxDateScrollIndex.value) {
          dateScrollIndex.value = Math.max(0, maxDateScrollIndex.value)
        }
      }
    }
    
    // Load dates on mount
    onMounted(async () => {
      initializeNearestSlot()
      await loadAvailableDates()
      // Check nearest slot cart status after dates are loaded
      if (nearestSlot.value) {
        await checkNearestSlotCartStatus()
      }
      
      // Add resize listener to update dates per view on window resize
      window.addEventListener('resize', handleResize)
    })
    
    onUnmounted(() => {
      window.removeEventListener('resize', handleResize)
    })

    // Watch for cart updates to refresh slot status
    watch(() => cartStore.itemCount, async () => {
      if (selectedDate.value && timeSlots.value.length > 0) {
        await checkSlotsCartStatus(timeSlots.value)
      }
      // Also check nearest slot status
      await checkNearestSlotCartStatus()
    })

    return {
      locale,
      loadingDates,
      nearestSlot,
      otherDates,
      visibleDates,
      selectedDate,
      otherTimeSlots,
      cartLoading,
      dateScrollIndex,
      maxDateScrollIndex,
      dateScrollContainer,
      formatTimeSlot,
      formatFullDate,
      getDateDay,
      getDateMonth,
      selectDate,
      scrollDatesLeft,
      scrollDatesRight,
      handleTouchStart,
      handleTouchMove,
      handleTouchEnd,
      addToCart,
      removeFromCart,
      addNearestToCart,
      removeNearestFromCart,
      checkNearestSlotCartStatus
    }
  }
}
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
