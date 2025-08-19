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
        <!-- Section Heading -->
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
          {{ $t('diagnosisResults.matchedTherapists') }}
        </h2>
        
        <!-- Sorting Controls -->
        <div v-if="matchedTherapists.length > 0" class="mb-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Order Sorting -->
            <div class="flex flex-col">
              <label for="orderSort" class="text-sm font-medium text-gray-700 mb-2">
                {{ $t('diagnosisResults.sortByOrder') }}
              </label>
              <select 
                id="orderSort" 
                v-model="orderSort" 
                @change="updateSorting"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm"
              >
                <option value="">{{ $t('diagnosisResults.any') }}</option>
                <option value="asc">{{ $t('diagnosisResults.orderAsc') }}</option>
                <option value="desc">{{ $t('diagnosisResults.orderDesc') }}</option>
              </select>
            </div>

            <!-- Price Sorting -->
            <div class="flex flex-col">
              <label for="priceSort" class="text-sm font-medium text-gray-700 mb-2">
                {{ $t('diagnosisResults.sortByPrice') }}
              </label>
              <select 
                id="priceSort" 
                v-model="priceSort" 
                @change="updateSorting"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm"
              >
                <option value="">{{ $t('diagnosisResults.any') }}</option>
                <option value="lowest">{{ $t('diagnosisResults.priceLowest') }}</option>
                <option value="highest">{{ $t('diagnosisResults.priceHighest') }}</option>
              </select>
            </div>

            <!-- Appointment Sorting -->
            <div class="flex flex-col">
              <label for="appointmentSort" class="text-sm font-medium text-gray-700 mb-2">
                {{ $t('diagnosisResults.sortByAppointment') }}
              </label>
              <select 
                id="appointmentSort" 
                v-model="appointmentSort" 
                @change="updateSorting"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm"
              >
                <option value="">{{ $t('diagnosisResults.any') }}</option>
                <option value="nearest">{{ $t('diagnosisResults.appointmentNearest') }}</option>
                <option value="farthest">{{ $t('diagnosisResults.appointmentFarthest') }}</option>
              </select>
            </div>
          </div>
        </div>
        
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
            :position="therapist.originalPosition"
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
import { ref, computed, onMounted, watch } from 'vue'
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
    const showAllTherapists = ref(false)
    const orderSort = ref('') // Order sorting: '', 'asc', 'desc'
    const priceSort = ref('') // Price sorting: '', 'lowest', 'highest'
    const appointmentSort = ref('') // Appointment sorting: '', 'nearest', 'farthest'

    // Computed property to get therapists with their original system positions
    const therapistsWithOriginalPositions = computed(() => {
      if (!matchedTherapists.value.length) return []
      
      const diagnosisId = route.params.diagnosisId
      
      return matchedTherapists.value.map((therapist) => {
        // Get the frontend_order from the diagnosis data
        const diagnosis = therapist.diagnoses?.find(d => d.id.toString() === diagnosisId.toString())
        const frontendOrder = parseInt(diagnosis?.frontend_order || '0')
        
        return {
          ...therapist,
          originalPosition: frontendOrder || 1, // Use frontend_order from API, fallback to 1
          displayOrder: parseInt(diagnosis?.display_order || '0') // Keep display_order for sorting
        }
      })
    })

    // Computed property to sort therapists based on selected sorting criteria
    const sortedTherapists = computed(() => {
      if (!therapistsWithOriginalPositions.value.length) return []
      
      return [...therapistsWithOriginalPositions.value].sort((a, b) => {
        // Priority: Order > Price > Appointment
        // If multiple sorting criteria are selected, order takes precedence
        
        // Order sorting
        if (orderSort.value) {
          if (orderSort.value === 'asc') {
            return a.displayOrder - b.displayOrder
          } else if (orderSort.value === 'desc') {
            return b.displayOrder - a.displayOrder
          }
        }
        
        // Price sorting
        if (priceSort.value) {
          // Get the actual price value from the price object structure
          const getPriceValue = (therapist) => {
            if (!therapist.price) return 0
            // For demo therapists, price is { others: number }
            // For regular therapists, price is { countries: [], others: number }
            return parseInt(therapist.price.others || 0)
          }
          
          const aPrice = getPriceValue(a)
          const bPrice = getPriceValue(b)
          
          if (priceSort.value === 'lowest') {
            return aPrice - bPrice
          } else if (priceSort.value === 'highest') {
            return bPrice - aPrice
          }
        }
        
        // Appointment sorting
        if (appointmentSort.value) {
          // Get the actual datetime from earliest_slot_data for proper sorting
          const getEarliestDateTime = (therapist) => {
            if (therapist.earliest_slot_data && therapist.earliest_slot_data.date && therapist.earliest_slot_data.time) {
              try {
                const dateTime = new Date(therapist.earliest_slot_data.date + ' ' + therapist.earliest_slot_data.time)
                return isNaN(dateTime.getTime()) ? new Date('9999-12-31') : dateTime
              } catch (error) {
                return new Date('9999-12-31')
              }
            }
            // Fallback to earliest_slot (minutes from now) if no real data
            if (therapist.earliest_slot && parseInt(therapist.earliest_slot) > 0) {
              try {
                const minutesFromNow = parseInt(therapist.earliest_slot)
                return new Date(Date.now() + minutesFromNow * 60000)
              } catch (error) {
                return new Date('9999-12-31')
              }
            }
            return new Date('9999-12-31') // No slot available
          }
          
          const aDateTime = getEarliestDateTime(a)
          const bDateTime = getEarliestDateTime(b)
          
          if (appointmentSort.value === 'nearest') {
            return aDateTime - bDateTime
          } else if (appointmentSort.value === 'farthest') {
            return bDateTime - aDateTime
          }
        }
        
        // Default: sort by order ascending if no sorting criteria are selected
        return a.displayOrder - b.displayOrder
      })
    })

    // Computed property to get displayed therapists based on limit
    const displayedTherapists = computed(() => {
      // If no therapists loaded yet, return empty array
      if (!sortedTherapists.value || sortedTherapists.value.length === 0) {
        return []
      }
      
      // Wait for settings to be initialized
      if (!settingsStore.isInitialized) {
        return sortedTherapists.value
      }
      
      const limit = settingsStore.getDiagnosisResultsLimit
      const showMoreEnabled = settingsStore.isShowMoreButtonEnabled
      
      // If show more button is disabled, the backend already limited the results
      // So we should show all therapists that were returned from the API
      if (!showMoreEnabled) {
        return sortedTherapists.value
      }
      
      // If show more button is enabled
      if (limit === 0) {
        return sortedTherapists.value
      }
      
      // If user clicked "show all", show all therapists
      if (showAllTherapists.value) {
        return sortedTherapists.value
      }
      
      // Otherwise, show limited therapists (respecting the limit)
      const limited = sortedTherapists.value.slice(0, limit)
      return limited
    })

    // Computed property to check if there are more therapists to show
    const hasMoreTherapists = computed(() => {
      // Wait for settings to be initialized
      if (!settingsStore.isInitialized) {
        return false
      }
      
      // Check if show more button is enabled
      if (!settingsStore.isShowMoreButtonEnabled) {
        return false
      }
      
      const limit = settingsStore.getDiagnosisResultsLimit
      if (limit === 0) return false
      return sortedTherapists.value.length > limit
    })

    const loadDiagnosisResult = async () => {
      // Get diagnosis data from localStorage or route params
      const diagnosisData = localStorage.getItem('diagnosis_data')
      const diagnosisId = route.params.diagnosisId
      
      if (diagnosisId) {
        // Try to load diagnosis details from API
        await loadDiagnosisDetails(diagnosisId)
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
        
        // Check if diagnosisId is numeric (ID) or string (name)
        if (/^\d+$/.test(diagnosisId)) {
          // It's a numeric ID, try to load from API
          const response = await api.get(`/api/ai/diagnoses/${diagnosisId}`)
          
          if (response.data.success && response.data.data) {
            let diagnosis = response.data.data
            
            // Check if the response is an array (list of diagnoses) or a single diagnosis
            if (Array.isArray(diagnosis)) {
              // Find the specific diagnosis by ID
              diagnosis = diagnosis.find(d => d.id == diagnosisId)
              
              if (!diagnosis) {
                // Fallback to default
                diagnosisResult.value = {
                  title: t('diagnosisResults.defaultTitle'),
                  description: t('diagnosisResults.defaultDescription')
                }
                return
              }
            }
            
            // Use the localized name from the backend, with fallback to manual localization
            let localizedName = diagnosis.name
            let localizedDescription = diagnosis.description
            
            // If backend didn't provide localized name, handle it on frontend
            if (diagnosis.name_en && diagnosis.name_ar) {
              const currentLocale = locale.value || 'en'
              localizedName = currentLocale === 'ar' ? diagnosis.name_ar : diagnosis.name_en
              localizedDescription = currentLocale === 'ar' ? (diagnosis.description_ar || diagnosis.description) : (diagnosis.description_en || diagnosis.description)
            }
            
            diagnosisResult.value = {
              title: localizedName,
              description: localizedDescription
            }
          } else {
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
      let retryCount = 0
      const maxRetries = 2
      
      while (retryCount <= maxRetries) {
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
                  const allFilteredTherapists = response.data.data.filter(therapist => 
                    therapist.diagnoses?.some(diagnosis => 
                      diagnosis.name?.toLowerCase().includes(diagnosisName) ||
                      diagnosis.name_en?.toLowerCase().includes(diagnosisName)
                    )
                  )
                  
                  // Apply limit if show more button is disabled
                  if (!settingsStore.isShowMoreButtonEnabled && settingsStore.getDiagnosisResultsLimit > 0) {
                    matchedTherapists.value = allFilteredTherapists.slice(0, settingsStore.getDiagnosisResultsLimit)
                  } else {
                    matchedTherapists.value = allFilteredTherapists
                  }
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
              } else {
                // If it's a name but ID search is enabled, load all therapists
                response = await api.get('/api/ai/therapists')
                matchedTherapists.value = response.data.data || []
              }
            }
          } else {
            // Load all therapists (for simulation)
            response = await api.get('/api/ai/therapists')
            matchedTherapists.value = response.data.data || []
          }
          
          // If we get here, the request was successful
          break
          
        } catch (error) {
          retryCount++
          console.error(`Error loading matched therapists (attempt ${retryCount}):`, error)
          
          if (retryCount > maxRetries) {
            toast.error(t('diagnosisResults.errorLoadingTherapists'))
            matchedTherapists.value = []
          } else {
            // Wait a bit before retrying
            await new Promise(resolve => setTimeout(resolve, 1000 * retryCount))
          }
        }
      }
      
      loading.value = false
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

    const updateSorting = () => {
      // Reset show all therapists when sorting criteria change
      showAllTherapists.value = false
    }

    onMounted(async () => {
      // Ensure settings are loaded
      if (!settingsStore.isInitialized) {
        settingsStore.initializeSettings()
        // Wait a bit for settings to be initialized
        await new Promise(resolve => setTimeout(resolve, 500))
      }
      
      // Load diagnosis details first, then therapists
      await loadDiagnosisResult()
      await loadMatchedTherapists()
    })

    // Watch for settings changes and reload if needed
    watch(() => settingsStore.isInitialized, (newVal) => {
      // Settings have been initialized
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
      orderSort,
      priceSort,
      appointmentSort,
      rediagnose,
      browseAllTherapists,
      viewTherapist,
      bookAppointment,
      showMoreTherapists,
      updateSorting
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