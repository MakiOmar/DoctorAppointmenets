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

        <!-- Therapists List -->
        <div v-else class="space-y-6">
          <TherapistCard
            v-for="(therapist, index) in displayedTherapists" 
            :key="therapist.id"
            :therapist="therapist"
            :diagnosis-id="route.params.diagnosisId"
            :position="index + 1"
            @click="viewTherapist"
            @book="bookAppointment"
          />
          
          <!-- Show More Button -->
          <div v-if="hasMoreTherapists && !showAllTherapists" class="text-center pt-6">
            <button
              @click="showMoreTherapists"
              class="btn-secondary"
            >
              {{ $t('diagnosisResults.showMore') }} ({{ sortedTherapists.length - displayedTherapists.length }} {{ $t('diagnosisResults.moreTherapists') }})
            </button>
          </div>
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
import { useSettingsStore } from '@/stores/settings'
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
    const { t, locale } = useI18n()
    const settingsStore = useSettingsStore()
    
    const loading = ref(true)
    const matchedTherapists = ref([])
    const diagnosisResult = ref({
      title: '',
      description: ''
    })
    const displayedTherapists = ref([])
    const showAllTherapists = ref(false)

    // Computed property to sort therapists by display_order for current diagnosis
    const sortedTherapists = computed(() => {
      if (!matchedTherapists.value.length) return []
      
      const diagnosisId = route.params.diagnosisId
      
      return [...matchedTherapists.value].sort((a, b) => {
        // Get display_order for current diagnosis for both therapists
        const aDiagnosis = a.diagnoses?.find(d => d.id.toString() === diagnosisId.toString())
        const bDiagnosis = b.diagnoses?.find(d => d.id.toString() === diagnosisId.toString())
        
        const aOrder = parseInt(aDiagnosis?.display_order || '0')
        const bOrder = parseInt(bDiagnosis?.display_order || '0')
        
        // Sort from lowest to highest
        return aOrder - bOrder
      })
    })

    // Computed property to get displayed therapists based on limit
    const displayedTherapists = computed(() => {
      const limit = settingsStore.getDiagnosisResultsLimit
      if (limit === 0 || showAllTherapists.value) {
        return sortedTherapists.value
      }
      return sortedTherapists.value.slice(0, limit)
    })

    // Computed property to check if there are more therapists to show
    const hasMoreTherapists = computed(() => {
      const limit = settingsStore.getDiagnosisResultsLimit
      if (limit === 0) return false
      return sortedTherapists.value.length > limit
    })

    const loadDiagnosisResult = () => {
      // Get diagnosis data from localStorage or route params
      const diagnosisData = localStorage.getItem('diagnosis_data')
      const diagnosisId = route.params.diagnosisId
      
      if (diagnosisId) {
        // Try to load diagnosis details from API
        loadDiagnosisDetails(diagnosisId)
      } else if (diagnosisData) {
        // Use stored diagnosis data for simulation
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
        console.log('Loading diagnosis details for ID:', diagnosisId)
        
        // Check if diagnosisId is numeric (ID) or string (name)
        if (/^\d+$/.test(diagnosisId)) {
          // It's a numeric ID, try to load from API
          console.log('Making API call to:', `/api/ai/diagnoses/${diagnosisId}`)
          const response = await api.get(`/api/ai/diagnoses/${diagnosisId}`)
          
          console.log('API response:', response.data)
          
          if (response.data.success && response.data.data) {
            let diagnosis = response.data.data
            console.log('API response data:', diagnosis)
            
            // Check if the response is an array (list of diagnoses) or a single diagnosis
            if (Array.isArray(diagnosis)) {
              // Find the specific diagnosis by ID
              diagnosis = diagnosis.find(d => d.id == diagnosisId)
              console.log('Found diagnosis in list:', diagnosis)
              
              if (!diagnosis) {
                console.log('Diagnosis not found in list')
                // Fallback to default
                diagnosisResult.value = {
                  title: t('diagnosisResults.defaultTitle'),
                  description: t('diagnosisResults.defaultDescription')
                }
                return
              }
            }
            
            console.log('Diagnosis data:', diagnosis)
            
            // Use the localized name from the backend, with fallback to manual localization
            let localizedName = diagnosis.name
            let localizedDescription = diagnosis.description
            
            // If backend didn't provide localized name, handle it on frontend
            if (diagnosis.name_en && diagnosis.name_ar) {
              const currentLocale = locale.value || 'en'
              console.log('Current locale:', currentLocale)
              localizedName = currentLocale === 'ar' ? diagnosis.name_ar : diagnosis.name_en
              localizedDescription = currentLocale === 'ar' ? (diagnosis.description_ar || diagnosis.description) : (diagnosis.description_en || diagnosis.description)
            }
            
            console.log('Final localized name:', localizedName)
            console.log('Final localized description:', localizedDescription)
            
            diagnosisResult.value = {
              title: localizedName,
              description: localizedDescription
            }
          } else {
            console.log('API call failed or no data returned')
            // Fallback to default if API fails
            diagnosisResult.value = {
              title: t('diagnosisResults.defaultTitle'),
              description: t('diagnosisResults.defaultDescription')
            }
          }
        } else {
          // It's a diagnosis name, use it directly
          const decodedName = decodeURIComponent(diagnosisId)
          diagnosisResult.value = {
            title: decodedName,
            description: t('diagnosisResults.defaultDescription')
          }
        }
      } catch (error) {
        console.error('Error loading diagnosis details:', error)
        console.error('Error details:', error.response?.data)
        // If it's a name, use it directly; otherwise fallback to default
        if (!/^\d+$/.test(diagnosisId)) {
          const decodedName = decodeURIComponent(diagnosisId)
          diagnosisResult.value = {
            title: decodedName,
            description: t('diagnosisResults.defaultDescription')
          }
        } else {
          // Fallback to default on error
          diagnosisResult.value = {
            title: t('diagnosisResults.defaultTitle'),
            description: t('diagnosisResults.defaultDescription')
          }
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
          // Check if we should search by name or ID
          if (settingsStore && settingsStore.isDiagnosisSearchByName) {
            // Load all therapists and filter by diagnosis name on frontend
            response = await api.get('/api/ai/therapists')
            if (response.data.data) {
              // Get diagnosis name from result or URL parameter
              let diagnosisName = ''
              if (diagnosisResult.value && diagnosisResult.value.title) {
                diagnosisName = diagnosisResult.value.title.toLowerCase()
              } else {
                // Use URL parameter directly (decoded)
                diagnosisName = decodeURIComponent(diagnosisId).toLowerCase()
              }
              
              if (diagnosisName) {
                // Filter therapists by diagnosis name
                matchedTherapists.value = response.data.data.filter(therapist => 
                  therapist.diagnoses?.some(diagnosis => 
                    diagnosis.name?.toLowerCase().includes(diagnosisName) ||
                    diagnosis.name_en?.toLowerCase().includes(diagnosisName)
                  )
                )
              } else {
                matchedTherapists.value = []
              }
            } else {
              matchedTherapists.value = []
            }
          } else {
            // Check if diagnosisId is numeric (ID) or string (name)
            if (/^\d+$/.test(diagnosisId)) {
              // Load therapists by diagnosis ID (default behavior)
              response = await api.get(`/api/ai/therapists/by-diagnosis/${diagnosisId}`)
              matchedTherapists.value = response.data.data || []
              console.log('Therapists loaded by diagnosis ID:', matchedTherapists.value)
            } else {
              // If it's a name but ID search is enabled, load all therapists
              response = await api.get('/api/ai/therapists')
              matchedTherapists.value = response.data.data || []
              console.log('Therapists loaded by name search:', matchedTherapists.value)
            }
          }
        } else {
          // Load all therapists (for simulation)
          response = await api.get('/api/ai/therapists')
          matchedTherapists.value = response.data.data || []
        }
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

    const showMoreTherapists = () => {
      showAllTherapists.value = true
    }

    onMounted(() => {
      loadDiagnosisResult()
      loadMatchedTherapists()
    })

    return {
      loading,
      matchedTherapists,
      sortedTherapists,
      displayedTherapists,
      hasMoreTherapists,
      showAllTherapists,
      diagnosisResult,
      route,
      rediagnose,
      browseAllTherapists,
      viewTherapist,
      bookAppointment,
      showMoreTherapists
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

/* RTL button layout */
.rtl .flex.justify-center.space-x-4 {
  flex-direction: row;
}

.rtl .space-x-4 > * + * {
  margin-right: 1rem;
  margin-left: 0;
}

/* RTL list improvements */
.rtl .space-y-6 > * {
  direction: rtl;
}

/* RTL responsive adjustments */
@media (max-width: 768px) {
  .rtl .flex.justify-center.space-x-4 {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .rtl .space-x-4 > * + * {
    margin: 0;
  }
}
</style> 