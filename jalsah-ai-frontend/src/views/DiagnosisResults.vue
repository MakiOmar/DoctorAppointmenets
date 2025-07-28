<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Diagnosis Result Header -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          {{ $t('diagnosisResults.title') }}
        </h1>
        <p class="text-lg text-gray-600 mb-6">
          {{ $t('diagnosisResults.subtitle') }}
        </p>
        
        <!-- Diagnosis Result Card -->
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6 mb-8">
          <h2 class="text-2xl font-semibold text-gray-900 mb-2">
            {{ diagnosisResult.title }}
          </h2>
          <p class="text-gray-600 mb-4">
            {{ diagnosisResult.description }}
          </p>
          <div class="flex justify-center space-x-4" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-4'">
            <button
              @click="rediagnose"
              class="btn-secondary"
            >
              {{ $t('diagnosisResults.rediagnose') }}
            </button>
            <button
              @click="browseAllTherapists"
              class="btn-primary"
            >
              {{ $t('diagnosisResults.browseAll') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Matched Therapists Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
          {{ $t('diagnosisResults.matchedTherapists') }}
        </h2>
        
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600">{{ $t('diagnosisResults.loadingTherapists') }}</p>
        </div>

        <!-- No Therapists Found -->
        <div v-else-if="matchedTherapists.length === 0" class="text-center py-12">
          <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ $t('diagnosisResults.noTherapistsFound') }}
          </h3>
          <p class="text-gray-600 mb-6">
            {{ $t('diagnosisResults.noTherapistsDescription') }}
          </p>
          <button
            @click="browseAllTherapists"
            class="btn-primary"
          >
            {{ $t('diagnosisResults.browseAllTherapists') }}
          </button>
        </div>

        <!-- Therapists Grid -->
        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <TherapistCard
            v-for="therapist in matchedTherapists" 
            :key="therapist.id"
            :therapist="therapist"
            :diagnosis-id="route.params.diagnosisId"
            @click="viewTherapist"
            @book="bookAppointment"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import StarRating from '@/components/StarRating.vue'
import TherapistCard from '@/components/TherapistCard.vue'

export default {
  name: 'DiagnosisResults',
  components: {
    StarRating,
    TherapistCard
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const toast = useToast()
    const { t } = useI18n()
    
    const loading = ref(true)
    const matchedTherapists = ref([])
    const diagnosisResult = ref({
      title: '',
      description: ''
    })

    const loadDiagnosisResult = () => {
      // Get diagnosis data from localStorage or route params
      const diagnosisData = localStorage.getItem('diagnosis_data')
      const diagnosisId = route.params.diagnosisId
      
      if (diagnosisId) {
        // Load diagnosis details from API
        loadDiagnosisDetails(diagnosisId)
      } else if (diagnosisData) {
        // Use stored diagnosis data (for simulation)
        const data = JSON.parse(diagnosisData)
        simulateDiagnosisResult(data)
      } else {
        // Fallback to default
        diagnosisResult.value = {
          title: t('diagnosisResults.defaultTitle'),
          description: t('diagnosisResults.defaultDescription')
        }
      }
    }

    const loadDiagnosisDetails = async (diagnosisId) => {
      try {
        const response = await api.get(`/api/ai/diagnoses/${diagnosisId}`)
        const diagnosis = response.data.data
        diagnosisResult.value = {
          title: diagnosis.name,
          description: diagnosis.description
        }
      } catch (error) {
        console.error('Error loading diagnosis details:', error)
        // Fallback to default
        diagnosisResult.value = {
          title: t('diagnosisResults.defaultTitle'),
          description: t('diagnosisResults.defaultDescription')
        }
      }
    }

    const simulateDiagnosisResult = (diagnosisData) => {
      // Simulate AI diagnosis result based on form data
      const { mood, selectedSymptoms, impact } = diagnosisData
      
      let title = ''
      let description = ''
      
      // Simple simulation logic
      if (selectedSymptoms.includes('anxiety') && selectedSymptoms.includes('panic')) {
        title = t('diagnosisResults.simulatedResults.anxiety.title')
        description = t('diagnosisResults.simulatedResults.anxiety.description')
      } else if (selectedSymptoms.includes('depression') && selectedSymptoms.includes('hopelessness')) {
        title = t('diagnosisResults.simulatedResults.depression.title')
        description = t('diagnosisResults.simulatedResults.depression.description')
      } else if (selectedSymptoms.includes('stress') && impact === 'severe') {
        title = t('diagnosisResults.simulatedResults.stress.title')
        description = t('diagnosisResults.simulatedResults.stress.description')
      } else {
        title = t('diagnosisResults.simulatedResults.general.title')
        description = t('diagnosisResults.simulatedResults.general.description')
      }
      
      diagnosisResult.value = { title, description }
    }

    const loadMatchedTherapists = async () => {
      loading.value = true
      try {
        const diagnosisId = route.params.diagnosisId
        let response
        
        if (diagnosisId) {
          // Load therapists by diagnosis
          response = await api.get(`/api/ai/therapists/by-diagnosis/${diagnosisId}`)
        } else {
          // Load all therapists (for simulation)
          response = await api.get('/api/ai/therapists')
        }
        
        matchedTherapists.value = response.data.data || []
      } catch (error) {
        toast.error(t('diagnosisResults.errorLoadingTherapists'))
        console.error('Error loading matched therapists:', error)
      } finally {
        loading.value = false
      }
    }

    const rediagnose = () => {
      // Clear stored diagnosis data
      localStorage.removeItem('diagnosis_data')
      // Redirect to homepage
      router.push('/')
    }

    const browseAllTherapists = () => {
      router.push('/therapists')
    }

    const viewTherapist = (therapistId) => {
      router.push(`/therapist/${therapistId}`)
    }

    const bookAppointment = (therapistId) => {
      router.push(`/booking/${therapistId}`)
    }

    onMounted(() => {
      loadDiagnosisResult()
      loadMatchedTherapists()
    })

    return {
      loading,
      matchedTherapists,
      diagnosisResult,
      route,
      rediagnose,
      browseAllTherapists,
      viewTherapist,
      bookAppointment
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
</style> 