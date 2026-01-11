<template>
  <BaseModal :is-open="isOpen" @close="handleClose" @update:isOpen="handleUpdateIsOpen">
    <!-- Header with Close and Cart -->
    <template #header>
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <!-- Close Button -->
        <button
          @click="handleClose"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          :aria-label="$t('common.close')"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <!-- Cart Icon with Badge -->
        <router-link
          to="/cart"
          class="relative p-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          <img
            v-if="cartIconExists"
            src="/home/Layer-26.png"
            alt="Cart"
            class="h-6"
            @error="cartIconExists = false"
          />
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 48 48"
            class="w-6 h-6 text-gray-600 fill-current"
          >
            <title>Cart</title>
            <g id="troley-2" data-name="troley">
              <path d="M15,39a3,3,0,1,0,3-3A3,3,0,0,0,15,39Zm4,0a1,1,0,1,1-1-1A1,1,0,0,1,19,39Z"/>
              <path d="M31,39a3,3,0,1,0,3-3A3,3,0,0,0,31,39Zm4,0a1,1,0,1,1-1-1A1,1,0,0,1,35,39Z"/>
              <circle cx="28.55" cy="20.55" r="1.45"/>
              <path d="M23.45,16.9A1.45,1.45,0,1,0,22,15.45,1.45,1.45,0,0,0,23.45,16.9Z"/>
              <path d="M23,22a1,1,0,0,0,.71-.29l6-6a1,1,0,0,0-1.42-1.42l-6,6a1,1,0,0,0,0,1.42A1,1,0,0,0,23,22Z"/>
              <path d="M7,10A1,1,0,0,0,8,9,1,1,0,0,1,9,8h2.26l5.4,17.27,1.38,5A1,1,0,0,0,19,31H32a1,1,0,0,1,0,2H20a1,1,0,0,0,0,2H32a3,3,0,0,0,0-6H19.76l-.83-3H32.47a6.92,6.92,0,0,0,3.58-1,7,7,0,0,0,3-3.46,6.45,6.45,0,0,0,.21-.62L42,11.27a1,1,0,0,0-.16-.87A1,1,0,0,0,41,10H14L13,6.7A1,1,0,0,0,12,6H9A3,3,0,0,0,6,9,1,1,0,0,0,7,10Zm32.67,2L38,18l-.68,2.37A5,5,0,0,1,32.47,24H18.36l-1.87-6-1.88-6Z"/>
            </g>
          </svg>
          <span
            v-if="cartItemCount > 0"
            class="absolute -top-1 -right-1 bg-secondary-500 text-primary-500 text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center min-w-[20px] px-1"
          >
            {{ cartItemCount > 99 ? '99+' : cartItemCount }}
          </span>
        </router-link>
      </div>
    </template>

    <!-- Content -->
    <div class="p-6" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
      <!-- Therapist Info -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ therapist.name }}</h2>
        <p v-if="therapist.doctor_specialty" class="text-lg text-gray-600">
          {{ therapist.doctor_specialty }}
        </p>
      </div>

      <!-- Booking Section -->
      <div class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('therapistDetails.bookAppointment') }}</h3>
        
        <!-- Date Selection -->
        <div v-if="loadingDates" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
          <p class="text-gray-600 mt-4">{{ $t('therapistDetails.loadingDates') }}</p>
        </div>
        
        <div v-else-if="availableDates.length > 0" class="space-y-4">
          <!-- Date Carousel -->
          <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">{{ $t('therapistDetails.selectDate') }}</h4>
            <div class="flex overflow-x-auto gap-3 pb-2 scrollbar-hide">
              <button
                v-for="date in availableDates"
                :key="date.value"
                @click="selectDate(date)"
                class="flex-shrink-0 px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
                :class="selectedDate?.value === date.value 
                  ? 'bg-secondary-500 text-primary-500 border-secondary-500' 
                  : 'bg-white text-primary-500 border-gray-300 hover:border-primary-400'"
              >
                <div class="text-center">
                  <div class="font-semibold">{{ date.day }}</div>
                  <div class="text-xs">{{ date.date }}</div>
                </div>
              </button>
            </div>
          </div>

          <!-- Time Slots Grid -->
          <div v-if="selectedDate && timeSlots.length > 0" class="space-y-3">
            <h4 class="text-sm font-medium text-gray-700">{{ $t('therapistDetails.availableTimes') }}</h4>
            <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
              <div
                v-for="slot in timeSlots"
                :key="slot.id || slot.value"
                class="relative"
              >
                <button
                  v-if="!slot.inCart"
                  @click="addToCart(slot)"
                  :disabled="cartLoading[slot.id]"
                  class="w-full px-3 py-2 text-sm rounded border transition-colors bg-white text-primary-500 border-gray-300 hover:border-primary-400 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="cartLoading[slot.id]" class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500 mr-2"></div>
                    {{ $t('common.loading') }}
                  </span>
                  <span v-else>{{ formatTimeSlot(slot.time) }}</span>
                </button>
                <div
                  v-else
                  class="w-full px-3 py-2 text-sm rounded border bg-secondary-500 text-primary-500 border-secondary-500 flex items-center justify-between"
                >
                  <span>{{ formatTimeSlot(slot.time) }}</span>
                  <button
                    @click="removeFromCart(slot)"
                    :disabled="cartLoading[slot.id]"
                    class="ml-2 text-primary-500 hover:text-primary-700 text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Remove from cart"
                  >
                    <span v-if="cartLoading[slot.id]" class="flex items-center">
                      <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-primary-500 mr-1"></div>
                    </span>
                    <span v-else>{{ $t('common.remove') }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- No Time Slots -->
          <div v-else-if="selectedDate && timeSlots.length === 0" class="text-center py-8 text-gray-500">
            {{ $t('therapistDetails.noTimeSlots') }}
          </div>
        </div>

        <!-- No Available Dates -->
        <div v-else class="text-center py-8 text-gray-500">
          {{ $t('therapistDetails.noAvailableDates') }}
        </div>
      </div>
    </div>
  </BaseModal>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import BaseModal from './BaseModal.vue'
import { formatGregorianDate } from '@/utils/dateFormatter'
import Swal from 'sweetalert2'
import { formatPrice as formatPriceUtil, getCurrencySymbol } from '@/utils/currency'

export default {
  name: 'BookAppointmentPopup',
  components: {
    BaseModal
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    therapist: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'update:isOpen'],
  setup(props, { emit }) {
    const { locale, t } = useI18n()
    const cartStore = useCartStore()
    const authStore = useAuthStore()
    const settingsStore = useSettingsStore()
    const toast = useToast()
    const cartIconExists = ref(true)
    const loadingDates = ref(false)
    const availableDates = ref([])
    const selectedDate = ref(null)
    const timeSlots = ref([])
    const cartLoading = ref({})

    const cartItemCount = computed(() => cartStore.itemCount)

    const handleClose = () => {
      emit('close')
      emit('update:isOpen', false)
    }

    const handleUpdateIsOpen = (value) => {
      emit('update:isOpen', value)
    }

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

    const getNearestSlotInfo = () => {
      if (props.therapist.earliest_slot_data && props.therapist.earliest_slot_data.date && props.therapist.earliest_slot_data.time) {
        return {
          date: props.therapist.earliest_slot_data.date,
          time: props.therapist.earliest_slot_data.time
        }
      }
      return null
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

    watch(() => props.isOpen, (newValue) => {
      if (newValue && availableDates.value.length === 0) {
        loadAvailableDates()
      }
    })

    return {
      locale,
      cartItemCount,
      cartIconExists,
      loadingDates,
      availableDates,
      selectedDate,
      timeSlots,
      cartLoading,
      handleClose,
      handleUpdateIsOpen,
      formatTimeSlot,
      selectDate,
      addToCart,
      removeFromCart
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

.rtl {
  direction: rtl;
  text-align: right;
}
</style>