<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
    
    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b">
          <h3 class="text-xl font-semibold text-gray-900">
            {{ $t('bookAppointment') }}
          </h3>
          <button
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Step 1: Date Selection -->
          <div v-if="currentStep === 1" class="space-y-4">
            <h4 class="text-lg font-medium text-gray-900">{{ $t('selectDate') }}</h4>
            
            <!-- Calendar -->
            <div class="grid grid-cols-7 gap-1">
              <!-- Day headers -->
              <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" 
                   :key="day" 
                   class="p-2 text-center text-sm font-medium text-gray-500">
                {{ day }}
              </div>
              
              <!-- Calendar days -->
              <div v-for="date in availableDates" 
                   :key="date.date" 
                   @click="selectDate(date)"
                   :class="[
                     'p-2 text-center cursor-pointer rounded-lg transition-colors',
                     date.isSelected ? 'bg-blue-500 text-white' : 
                     date.isAvailable ? 'hover:bg-gray-100' : 'text-gray-300 cursor-not-allowed'
                   ]">
                {{ date.day }}
              </div>
            </div>
            
            <div class="flex justify-end">
              <button
                @click="nextStep"
                :disabled="!selectedDate"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition-colors',
                  selectedDate ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                {{ $t('next') }}
              </button>
            </div>
          </div>

          <!-- Step 2: Time Selection -->
          <div v-if="currentStep === 2" class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-lg font-medium text-gray-900">
                {{ $t('selectTime') }} - {{ formatSelectedDate }}
              </h4>
              <button
                @click="currentStep = 1"
                class="text-blue-500 hover:text-blue-600 text-sm"
              >
                {{ $t('changeDate') }}
              </button>
            </div>
            
            <!-- Loading -->
            <div v-if="loadingSlots" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
              <p class="mt-2 text-gray-500">{{ $t('loadingTimeSlots') }}</p>
            </div>
            
            <!-- Time slots -->
            <div v-else-if="availableSlots.length > 0" class="grid grid-cols-3 gap-3">
              <button
                v-for="slot in availableSlots"
                :key="slot.slot_id"
                @click="selectTimeSlot(slot)"
                :class="[
                  'p-3 rounded-lg border text-center transition-colors',
                  selectedTimeSlot?.slot_id === slot.slot_id 
                    ? 'border-blue-500 bg-blue-50 text-blue-700' 
                    : 'border-gray-200 hover:border-blue-300 hover:bg-blue-50'
                ]"
              >
                {{ slot.formatted_time }}
              </button>
            </div>
            
            <!-- No slots available -->
            <div v-else class="text-center py-8">
              <p class="text-gray-500">{{ $t('noTimeSlotsAvailable') }}</p>
            </div>
            
            <div class="flex justify-between">
              <button
                @click="currentStep = 1"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
              >
                {{ $t('back') }}
              </button>
              <button
                @click="addToCart"
                :disabled="!selectedTimeSlot || addingToCart"
                :class="[
                  'px-4 py-2 rounded-lg font-medium transition-colors',
                  selectedTimeSlot && !addingToCart 
                    ? 'bg-blue-500 text-white hover:bg-blue-600' 
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                <span v-if="addingToCart">{{ $t('adding') }}...</span>
                <span v-else>{{ $t('addToCart') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '../stores/cart'
import api from '../services/api'

const { t } = useI18n()
const cartStore = useCartStore()

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  therapist: {
    type: Object,
    required: true
  },
  userId: {
    type: [String, Number],
    required: true
  }
})

// Emits
const emit = defineEmits(['close', 'appointment-added'])

// Reactive data
const currentStep = ref(1)
const selectedDate = ref(null)
const selectedTimeSlot = ref(null)
const availableDates = ref([])
const availableSlots = ref([])
const loadingSlots = ref(false)
const addingToCart = ref(false)

// Computed
const formatSelectedDate = computed(() => {
  if (!selectedDate.value) return ''
  return new Date(selectedDate.value).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Methods
const closeModal = () => {
  emit('close')
  resetModal()
}

const resetModal = () => {
  currentStep.value = 1
  selectedDate.value = null
  selectedTimeSlot.value = null
  availableSlots.value = []
}

const generateAvailableDates = () => {
  const dates = []
  const today = new Date()
  
  // Generate next 30 days
  for (let i = 1; i <= 30; i++) {
    const date = new Date(today)
    date.setDate(today.getDate() + i)
    
    // Skip weekends (Saturday = 6, Sunday = 0) - adjust based on your business logic
    const dayOfWeek = date.getDay()
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6
    
    dates.push({
      date: date.toISOString().split('T')[0],
      day: date.getDate(),
      isAvailable: !isWeekend,
      isSelected: false
    })
  }
  
  availableDates.value = dates
}

const selectDate = (date) => {
  if (!date.isAvailable) return
  
  // Update selection
  availableDates.value.forEach(d => d.isSelected = d.date === date.date)
  selectedDate.value = date.date
}

const nextStep = async () => {
  if (!selectedDate.value) return
  
  currentStep.value = 2
  await loadTimeSlots()
}

const loadTimeSlots = async () => {
  if (!selectedDate.value || !props.therapist.user_id) return
  
  loadingSlots.value = true
  availableSlots.value = []
  
  try {
    const response = await api.get('/api/ai/therapist-availability', {
      params: {
        therapist_id: props.therapist.user_id,
        date: selectedDate.value
      }
    })
    
    availableSlots.value = response.data.available_slots || []
  } catch (error) {
    console.error('Error loading time slots:', error)
    availableSlots.value = []
  } finally {
    loadingSlots.value = false
  }
}

const selectTimeSlot = (slot) => {
  selectedTimeSlot.value = slot
}

const addToCart = async () => {
  if (!selectedTimeSlot.value || !props.userId) return
  
  addingToCart.value = true
  
  try {
    const appointmentData = {
      user_id: props.userId,
      slot_id: selectedTimeSlot.value.slot_id
    }
    
    const result = await cartStore.addToCart(appointmentData)
    
    if (result.success) {
      emit('appointment-added', {
        therapist: props.therapist,
        date: selectedDate.value,
        time: selectedTimeSlot.value.formatted_time,
        slot_id: selectedTimeSlot.value.slot_id
      })
      closeModal()
    } else {
      // Handle error - you might want to show a toast notification
      console.error('Failed to add to cart:', result.message)
    }
  } catch (error) {
    console.error('Error adding to cart:', error)
  } finally {
    addingToCart.value = false
  }
}

// Watch for modal open/close
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    generateAvailableDates()
  }
})

// Initialize
onMounted(() => {
  if (props.isOpen) {
    generateAvailableDates()
  }
})
</script> 