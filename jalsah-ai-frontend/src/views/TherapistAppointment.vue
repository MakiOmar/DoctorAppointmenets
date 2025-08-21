<template>
  <div class="container mx-auto px-4 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-600 text-lg mb-4">{{ error }}</div>
        <button @click="loadTherapist" class="btn-primary">
          {{ $t('common.retry') }}
        </button>
      </div>
      
      <!-- Therapist Content -->
      <div v-else-if="therapist" class="max-w-4xl mx-auto">
        <!-- Page Title -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 mb-2">
            {{ $t('therapistAppointment.title') }}
          </h1>
          <p class="text-gray-600">
            {{ $t('therapistAppointment.subtitle') }}
          </p>
        </div>
        
                 <!-- Therapist Card -->
         <div class="mb-8">
           <TherapistCard 
             :therapist="therapist" 
             :current-diagnosis-display-order="null"
             :suitability-message="null"
             @show-details="handleShowDetails"
           />
         </div>
        
        <!-- Booking Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-xl font-semibold text-gray-900 mb-6">
            {{ $t('therapistAppointment.bookSession') }}
          </h3>
          
          <!-- Available Slots -->
          <div v-if="availableSlots.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div 
              v-for="slot in availableSlots" 
              :key="slot.id"
              @click="selectSlot(slot)"
              :class="[
                'p-4 border rounded-lg cursor-pointer transition-colors',
                selectedSlot?.id === slot.id 
                  ? 'border-blue-500 bg-blue-50' 
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="font-medium text-gray-900">
                {{ formatDate(slot.date) }}
              </div>
              <div class="text-sm text-gray-600">
                {{ slot.time }} - {{ slot.end_time }}
              </div>
              <div class="text-xs text-gray-500 mt-1">
                {{ slot.attendance_type || $t('therapist.inPerson') }}
              </div>
            </div>
          </div>
          
          <!-- No Slots Available -->
          <div v-else class="text-center py-8 text-gray-500">
            {{ $t('therapistAppointment.noSlotsAvailable') }}
          </div>
          
          <!-- Book Button -->
          <div v-if="selectedSlot" class="flex justify-center">
            <button 
              @click="bookAppointment"
              :disabled="booking"
              class="btn-primary px-8 py-3 text-lg"
            >
              <span v-if="booking" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></span>
              {{ booking ? $t('therapistAppointment.booking') : $t('therapistAppointment.bookNow') }}
            </button>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import { useToast } from 'vue-toastification'
import TherapistCard from '../components/TherapistCard.vue'
import api from '../services/api'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()
const toast = useToast()

// Reactive data
const loading = ref(true)
const error = ref(null)
const therapist = ref(null)
const availableSlots = ref([])
const selectedSlot = ref(null)
const booking = ref(false)

// Load therapist data
const loadTherapist = async () => {
  try {
    loading.value = true
    error.value = null
    
    const therapistId = route.params.therapistId
    console.log('🔍 Loading therapist:', therapistId)
    
    const response = await api.get(`/api/ai/therapists/${therapistId}`)
    
    if (response.data.success) {
      therapist.value = response.data.data
      console.log('✅ Therapist loaded:', therapist.value)
      
      // Load available slots
      await loadAvailableSlots()
    } else {
      error.value = response.data.error || t('therapistAppointment.loadError')
    }
  } catch (err) {
    console.error('❌ Error loading therapist:', err)
    error.value = t('therapistAppointment.loadError')
  } finally {
    loading.value = false
  }
}

// Load available slots for the therapist
const loadAvailableSlots = async () => {
  try {
    const therapistId = route.params.therapistId
    const response = await api.get(`/api/ai/therapists/${therapistId}/available-dates`)
    
    if (response.data.success) {
      availableSlots.value = response.data.data || []
      console.log('✅ Available slots loaded:', availableSlots.value)
    }
  } catch (err) {
    console.error('❌ Error loading available slots:', err)
    // Don't show error for slots, just log it
  }
}

// Select a time slot
const selectSlot = (slot) => {
  selectedSlot.value = slot
  console.log('✅ Slot selected:', slot)
}

// Book the appointment
const bookAppointment = async () => {
  if (!selectedSlot.value) return
  
  try {
    booking.value = true
    
    const response = await api.post('/api/ai/add-appointment-to-cart', {
      slot_id: selectedSlot.value.id,
      therapist_id: route.params.therapistId
    })
    
    if (response.data.success) {
      toast.success(t('therapistAppointment.addedToCart'))
      // Redirect to cart
      router.push('/cart')
    } else {
      toast.error(response.data.error || t('therapistAppointment.bookingError'))
    }
  } catch (err) {
    console.error('❌ Error booking appointment:', err)
    toast.error(t('therapistAppointment.bookingError'))
  } finally {
    booking.value = false
  }
}

// Handle show details event from TherapistCard
const handleShowDetails = (therapist) => {
  console.log('🔍 Show details for therapist:', therapist)
  // You can add any additional logic here if needed
}

// Format date
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ar-SA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Load data on mount
onMounted(() => {
  loadTherapist()
})
</script>
