<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <div class="text-red-600 text-lg mb-4">{{ error }}</div>
      <button @click="loadAppointment" class="btn-primary">
        {{ $t('common.retry') }}
      </button>
    </div>
    
    <!-- Reschedule Content -->
    <div v-else-if="appointment && therapist" class="max-w-4xl mx-auto">
      <!-- Page Title -->
      <div class="mb-8">
        <button 
          @click="$router.go(-1)"
          class="flex items-center text-primary-600 hover:text-primary-700 mb-4"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          {{ $t('common.back') }}
        </button>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ $t('reschedule.title') }}
        </h1>
        <p class="text-gray-600">
          {{ $t('reschedule.subtitle') }}
        </p>
      </div>
      
      <!-- Current Appointment Info -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('reschedule.currentAppointment') }}</h2>
        
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-600">{{ $t('reschedule.therapist') }}</p>
            <p class="font-medium text-gray-900">{{ therapist.name }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">{{ $t('reschedule.currentDate') }}</p>
            <p class="font-medium text-gray-900">{{ formatDate(appointment.date) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">{{ $t('reschedule.currentTime') }}</p>
            <p class="font-medium text-gray-900">{{ formatTime(appointment.time) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">{{ $t('reschedule.duration') }}</p>
            <p class="font-medium text-gray-900">{{ appointment.session_type || '45' }} {{ $t('common.minutes') }}</p>
          </div>
        </div>
      </div>
      
      <!-- New Appointment Selection -->
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('reschedule.selectNewTime') }}</h2>
        
        <!-- Date Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $t('reschedule.selectDate') }}
          </label>
          <div class="flex space-x-2 overflow-x-auto pb-2">
            <button
              v-for="date in availableDates"
              :key="date.date"
              @click="selectDate(date)"
              :class="[
                'px-4 py-2 rounded-lg border text-sm font-medium whitespace-nowrap',
                date.isSelected
                  ? 'border-blue-500 bg-blue-50 text-blue-700'
                  : date.isAvailable
                  ? 'border-gray-300 text-gray-700 hover:border-blue-300'
                  : 'border-gray-200 text-gray-400 cursor-not-allowed'
              ]"
              :disabled="!date.isAvailable"
            >
              {{ formatDateShort(date.date) }}
            </button>
          </div>
        </div>
        
        <!-- Time Slots -->
        <div v-if="selectedDate && availableSlots.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $t('reschedule.selectTime') }}
          </label>
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <button
              v-for="slot in availableSlots"
              :key="slot.slot_id"
              @click="selectTimeSlot(slot)"
              :class="[
                'p-3 rounded-lg border text-sm font-medium',
                selectedTimeSlot?.slot_id === slot.slot_id
                  ? 'border-blue-500 bg-blue-50 text-blue-700'
                  : 'border-gray-300 text-gray-700 hover:border-blue-300'
              ]"
            >
              {{ slot.formatted_time }}
            </button>
          </div>
        </div>
        
        <!-- No Slots Available -->
        <div v-else-if="selectedDate && !loadingSlots" class="text-center py-8">
          <p class="text-gray-500">{{ $t('reschedule.noSlotsAvailable') }}</p>
        </div>
        
        <!-- Loading Slots -->
        <div v-else-if="loadingSlots" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
          <p class="text-gray-500">{{ $t('common.loading') }}</p>
        </div>
        
        <!-- Reschedule Button -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <button
            @click="rescheduleAppointment"
            :disabled="!selectedTimeSlot || rescheduling"
            :class="[
              'w-full py-3 px-4 rounded-lg font-medium transition-colors',
              selectedTimeSlot && !rescheduling
                ? 'bg-blue-600 text-white hover:bg-blue-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
            ]"
          >
            <span v-if="rescheduling" class="flex items-center justify-center">
              <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
              {{ $t('reschedule.rescheduling') }}
            </span>
            <span v-else>{{ $t('reschedule.confirmReschedule') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import api from '../services/api'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const toast = useToast()

// Reactive data
const loading = ref(true)
const error = ref(null)
const appointment = ref(null)
const therapist = ref(null)
const selectedDate = ref(null)
const selectedTimeSlot = ref(null)
const availableDates = ref([])
const availableSlots = ref([])
const loadingSlots = ref(false)
const rescheduling = ref(false)

// Load appointment data
const loadAppointment = async () => {
  try {
    loading.value = true
    error.value = null
    
    const response = await api.get(`/api/ai/appointments/${route.params.appointmentId}`)
    appointment.value = response.data.data
    
    // Load therapist data
    const therapistResponse = await api.get(`/api/ai/therapists/${appointment.value.therapist_id}`)
    therapist.value = therapistResponse.data.data
    
    // Generate available dates (next 30 days)
    generateAvailableDates()
    
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load appointment'
    console.error('Error loading appointment:', err)
  } finally {
    loading.value = false
  }
}

// Generate available dates
const generateAvailableDates = () => {
  const dates = []
  const today = new Date()
  
  for (let i = 1; i <= 30; i++) {
    const date = new Date(today)
    date.setDate(today.getDate() + i)
    
    const dateStr = date.toISOString().split('T')[0]
    
    dates.push({
      date: dateStr,
      isAvailable: true,
      isSelected: false
    })
  }
  
  availableDates.value = dates
}

// Select date
const selectDate = (date) => {
  if (!date.isAvailable) return
  
  // Update selection
  availableDates.value.forEach(d => d.isSelected = d.date === date.date)
  selectedDate.value = date.date
  selectedTimeSlot.value = null
}

// Load time slots for selected date
const loadTimeSlots = async () => {
  if (!selectedDate.value || !therapist.value?.user_id) return
  
  loadingSlots.value = true
  availableSlots.value = []
  
  try {
    const response = await api.get('/api/ai/therapist-availability', {
      params: {
        therapist_id: therapist.value.user_id,
        date: selectedDate.value
      }
    })
    
    availableSlots.value = response.data.available_slots || []
  } catch (err) {
    console.error('Error loading time slots:', err)
    availableSlots.value = []
  } finally {
    loadingSlots.value = false
  }
}

// Select time slot
const selectTimeSlot = (slot) => {
  selectedTimeSlot.value = slot
}

// Reschedule appointment
const rescheduleAppointment = async () => {
  if (!selectedTimeSlot.value) return
  
  rescheduling.value = true
  
  try {
    const response = await api.put(`/api/ai/appointments/${route.params.appointmentId}/reschedule`, {
      new_appointment_id: selectedTimeSlot.value.slot_id
    })
    
    toast.success(t('reschedule.success'))
    
    // Redirect to appointments page
    router.push('/appointments')
    
  } catch (err) {
    const errorMessage = err.response?.data?.message || t('reschedule.error')
    toast.error(errorMessage)
    console.error('Error rescheduling appointment:', err)
  } finally {
    rescheduling.value = false
  }
}

// Format date
const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

// Format date short
const formatDateShort = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', { 
    weekday: 'short', 
    month: 'short', 
    day: 'numeric' 
  })
}

// Format time
const formatTime = (timeStr) => {
  if (!timeStr) return ''
  return timeStr
}

// Watch for date selection
watch(selectedDate, (newDate) => {
  if (newDate) {
    loadTimeSlots()
  }
})

onMounted(() => {
  loadAppointment()
})
</script>
