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

// Handle show details event from TherapistCard
const handleShowDetails = (therapist) => {
  console.log('🔍 Show details for therapist:', therapist)
  // You can add any additional logic here if needed
}



// Load data on mount
onMounted(() => {
  loadTherapist()
})
</script>
