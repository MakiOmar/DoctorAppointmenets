<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Diagnosis Result Header -->
      <div class="mb-8">
        <div class="max-w-2xl mx-auto bg-[#F8F6F1] rounded-2xl border border-[#EEDEC4] p-8 text-center">
          <!-- Main Title -->
          <h1 class="text-3xl mb-4 font-jalsah1 text-center" style="color: #162E52;">
            {{ $t('diagnosisResults.title') }}
          </h1>
          <!-- Subtitle -->
          <p class="text-lg font-jalsah1" style="color: #9F8F75;" v-if="diagnosisResult.title_en || diagnosisResult.title_ar">
            <span v-if="diagnosisResult.title_ar" class="font-jalsah2">{{ diagnosisResult.title_ar }}</span>
          </p>


          <div class="flex justify-center">
            <button
              @click="rediagnose"
              class="btn-secondary bg-primary-500 text-white text-[20px] mt-[20px]"
            >
              {{ $t('diagnosisResults.rediagnose') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Matched Therapists Section -->
      <div class="mb-8">
        <!-- Sorting Controls -->
        <div v-if="matchedTherapists.length > 0" class="mb-6 bg-white rounded-[10px] p-4">
          <div class="flex flex-col lg:flex-row gap-4 items-center">
            
            <!-- Best Button (Reset to Default) -->
            <div class="w-full lg:w-1/3">
              <button
                @click="setSorting('best')"
                class="w-full px-4 py-2 rounded-lg text-[20px] transition-colors"
                :class="activeSort === 'best' || activeSort === '' 
                  ? 'bg-secondary-500 text-primary-500' 
                  : 'bg-primary-500 text-white'"
              >
                {{ $t('therapists.sorting.bestSimple') }}
              </button>
            </div>
            <!-- Nearest Slot Button -->
            <div class="w-full lg:w-1/3">
              <button
                @click="setSorting('nearest')"
                class="w-full px-4 py-2 rounded-lg text-[20px] transition-colors"
                :class="activeSort === 'nearest' 
                  ? 'bg-secondary-500 text-primary-500' 
                  : 'bg-primary-500 text-white'"
              >
                {{ $t('therapists.sorting.nearest') }}
              </button>
            </div>
            <!-- Lowest Price Button -->
            <div class="w-full lg:w-1/3">
              <button
                @click="setSorting('price-low')"
                class="w-full px-4 py-2 rounded-lg text-[20px] transition-colors"
                :class="activeSort === 'price-low' 
                  ? 'bg-secondary-500 text-primary-500' 
                  : 'bg-primary-500 text-white'"
              >
                {{ $t('therapists.sorting.priceLow') }}
              </button>
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
          <h3 class="text-lg text-gray-900 mb-2">
            {{ $t('diagnosisResults.noTherapistsFound') }}
          </h3>
          <p class="text-gray-600">
            {{ $t('diagnosisResults.noTherapistsDescription') }}
          </p>
        </div>

        <!-- Therapists List -->
        <div v-else class="therapists-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <TherapistCard
            v-for="(therapist, index) in displayedTherapists" 
            :key="therapist.id"
            :ref="index === 0 ? 'firstTherapistCard' : null"
            :therapist="therapist"
            :diagnosis-id="route.params.diagnosisId"
            :position="therapist.originalPosition"
            :show-order-badge="true"
            :card-variant="'diagnosis-results'"
            :open-therapist-id="openTherapistId"
            @click="viewTherapist"
            @book="bookAppointment"
            @show-details="handleShowDetails(therapist.id)"
            @hide-details="handleHideDetails()"
            @open-about="handleOpenAbout"
            @open-booking="handleOpenBooking"
            @open-why="handleOpenWhy"
            @open-certificates="handleOpenCertificates"
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

    <!-- Popups -->
    <AboutTherapistPopup
      v-if="selectedTherapist"
      :is-open="showAboutPopup"
      :therapist="selectedTherapist"
      @close="closeAboutPopup"
      @update:isOpen="showAboutPopup = $event"
      @open-certificates="handleOpenCertificates(selectedTherapist.id)"
    />

    <BookAppointmentPopup
      v-if="selectedTherapist"
      :is-open="showBookingPopup"
      :therapist="selectedTherapist"
      @close="closeBookingPopup"
      @update:isOpen="showBookingPopup = $event"
    />

    <WhyThisTherapistPopup
      v-if="selectedTherapist && route.params.diagnosisId"
      :is-open="showWhyPopup"
      :therapist="selectedTherapist"
      :diagnosis-id="route.params.diagnosisId"
      @close="closeWhyPopup"
      @update:isOpen="showWhyPopup = $event"
    />

    <CertificatesPopup
      v-if="selectedTherapist"
      :is-open="showCertificatesPopup"
      :therapist="selectedTherapist"
      @close="closeCertificatesPopup"
      @update:isOpen="showCertificatesPopup = $event"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import StarRating from '@/components/StarRating.vue'
import TherapistCard from '@/components/TherapistCard.vue'
import AboutTherapistPopup from '@/components/AboutTherapistPopup.vue'
import BookAppointmentPopup from '@/components/BookAppointmentPopup.vue'
import WhyThisTherapistPopup from '@/components/WhyThisTherapistPopup.vue'
import CertificatesPopup from '@/components/CertificatesPopup.vue'

export default {
  name: 'DiagnosisResults',
  components: {
    StarRating,
    TherapistCard,
    AboutTherapistPopup,
    BookAppointmentPopup,
    WhyThisTherapistPopup,
    CertificatesPopup
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const toast = useToast()
    const { t, locale } = useI18n()
    const settingsStore = useSettingsStore()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    const matchedTherapists = ref([])
    const diagnosisResult = ref({
      title: '',
      title_en: '',
      title_ar: '',
      description: ''
    })
    const showAllTherapists = ref(false)
    const firstTherapistCard = ref(null)
    const openTherapistId = ref(null) // Track which therapist's details are currently open
    // Sorting controls - single active sort (same as therapists page)
    const activeSort = ref('') // Active sorting: '', 'best', 'price-low', 'nearest'
    
    // Popup states
    const showAboutPopup = ref(false)
    const showBookingPopup = ref(false)
    const showWhyPopup = ref(false)
    const showCertificatesPopup = ref(false)
    const selectedTherapist = ref(null)

    // Computed property to get therapists with their original system positions
    const therapistsWithOriginalPositions = computed(() => {
      if (!matchedTherapists.value.length) return []
      
      const diagnosisId = route.params.diagnosisId
      
      const result = matchedTherapists.value.map((therapist, index) => {
        // Get the frontend_order from the diagnosis data
        const diagnosis = diagnosisId ? therapist.diagnoses?.find(d => d.id.toString() === diagnosisId.toString()) : null
        const frontendOrder = parseInt(diagnosis?.frontend_order || '0')
        const displayOrder = parseInt(diagnosis?.display_order || '0')
        
        // Preserve original backend order - use index as the primary sort key for "best" sorting
        // since backend already returns them in correct order by frontend_order
        // frontendOrder is used for display badge, but original index preserves backend order
        return {
          ...therapist,
          originalPosition: frontendOrder > 0 ? frontendOrder : (index + 1), // For badge display
          displayOrder: displayOrder, // Keep display_order for secondary sorting
          frontendOrder: frontendOrder > 0 ? frontendOrder : 999999, // Use 999999 for 0 values to push them to end
          originalIndex: index // Preserve original backend order index for stable sorting
        }
      })
      
      return result
    })

    // Helper function to get earliest slot time (same as therapists page)
    const getEarliestSlotTime = (therapist) => {
      // First try to use earliest_slot_data if available
      if (therapist.earliest_slot_data && therapist.earliest_slot_data.date && therapist.earliest_slot_data.time) {
        try {
          const slotDate = new Date(`${therapist.earliest_slot_data.date} ${therapist.earliest_slot_data.time}`)
          if (!isNaN(slotDate.getTime())) {
            return slotDate.getTime()
          }
        } catch (error) {
          console.warn('Error parsing earliest_slot_data:', error)
        }
      }
      
      // Fallback to earliest_slot (minutes from now)
      if (therapist.earliest_slot && parseInt(therapist.earliest_slot) > 0) {
        const minutesFromNow = parseInt(therapist.earliest_slot)
        const slotDate = new Date(Date.now() + minutesFromNow * 60000)
        return slotDate.getTime()
      }
      
      // If it's a full datetime string, parse it
      const slotDate = new Date(therapist.earliest_slot || '9999-12-31')
      return isNaN(slotDate.getTime()) ? 999999 : slotDate.getTime()
    }

    // Computed property to calculate "best" order positions (sequential 1, 2, 3...)
    // This is used to preserve the original order badge regardless of current sorting
    const therapistsWithBestOrder = computed(() => {
      if (!therapistsWithOriginalPositions.value.length) return []
      
      // Filter out therapists with no available slots
      let filtered = therapistsWithOriginalPositions.value.filter(therapist => {
        return therapist.earliest_slot_data && 
               therapist.earliest_slot_data.date && 
               therapist.earliest_slot_data.time
      })
      
      // Sort by "best" order (frontend_order, display_order, originalIndex)
      let bestSorted = [...filtered]
      bestSorted.sort((a, b) => {
        // Primary sort: frontendOrder (but treat 0 as 999999 to push to end)
        const orderA = a.frontendOrder > 0 ? a.frontendOrder : 999999
        const orderB = b.frontendOrder > 0 ? b.frontendOrder : 999999
        if (orderA !== orderB) {
          return orderA - orderB
        }
        // Secondary sort: displayOrder (if frontendOrder is same or both 0)
        if (a.displayOrder !== b.displayOrder) {
          return a.displayOrder - b.displayOrder
        }
        // Tertiary sort: originalIndex (preserve backend order as final tiebreaker)
        return a.originalIndex - b.originalIndex
      })
      
      // Create a map of therapist ID to best order position
      const bestOrderMap = new Map()
      bestSorted.forEach((therapist, index) => {
        bestOrderMap.set(therapist.id, index + 1)
      })
      
      // Add bestOrderPosition to each therapist
      return therapistsWithOriginalPositions.value.map(therapist => ({
        ...therapist,
        bestOrderPosition: bestOrderMap.get(therapist.id) || 999999
      }))
    })

    // Computed property to sort therapists (same logic as therapists page)
    const sortedTherapists = computed(() => {
      if (!therapistsWithBestOrder.value.length) return []
      
      // Filter out therapists with no available slots
      let filtered = therapistsWithBestOrder.value.filter(therapist => {
        // Check if therapist has available slots
        return therapist.earliest_slot_data && 
               therapist.earliest_slot_data.date && 
               therapist.earliest_slot_data.time
      })
      
      let sorted = [...filtered]

      // Apply active sorting
      switch (activeSort.value) {
        case 'best':
        case '':
          // Default sorting (reset to original backend order) - preserve the order from backend
          // Backend already returns therapists sorted by frontend_order ASC, display_order ASC, name ASC
          // So we use originalIndex to maintain that order after filtering
          sorted.sort((a, b) => {
            // Primary sort: frontendOrder (but treat 0 as 999999 to push to end)
            const orderA = a.frontendOrder > 0 ? a.frontendOrder : 999999
            const orderB = b.frontendOrder > 0 ? b.frontendOrder : 999999
            if (orderA !== orderB) {
              return orderA - orderB
            }
            // Secondary sort: displayOrder (if frontendOrder is same or both 0)
            if (a.displayOrder !== b.displayOrder) {
              return a.displayOrder - b.displayOrder
            }
            // Tertiary sort: originalIndex (preserve backend order as final tiebreaker)
            return a.originalIndex - b.originalIndex
          })
          
          break
          
        case 'price-low':
          // Sort by price (lowest to highest)
          sorted.sort((a, b) => {
            const priceA = a.price?.others || 0
            const priceB = b.price?.others || 0
            return priceA - priceB
          })
          break
          
        case 'nearest':
          // Sort by earliest appointment (nearest to farthest)
          sorted.sort((a, b) => {
            const timeA = getEarliestSlotTime(a)
            const timeB = getEarliestSlotTime(b)
            return timeA - timeB
          })
          break
          
        default:
          // Default sorting (no specific sorting applied) - preserve backend order
          sorted.sort((a, b) => {
            // Primary sort: frontendOrder (but treat 0 as 999999 to push to end)
            const orderA = a.frontendOrder > 0 ? a.frontendOrder : 999999
            const orderB = b.frontendOrder > 0 ? b.frontendOrder : 999999
            if (orderA !== orderB) {
              return orderA - orderB
            }
            // Secondary sort: displayOrder (if frontendOrder is same or both 0)
            if (a.displayOrder !== b.displayOrder) {
              return a.displayOrder - b.displayOrder
            }
            // Tertiary sort: originalIndex (preserve backend order as final tiebreaker)
            return a.originalIndex - b.originalIndex
          })
          break
      }

      // Always use bestOrderPosition for badge display, regardless of current sorting
      // This preserves the original "best" order number (1, 2, 3, 4...) even when sorted differently
      return sorted.map(therapist => ({
        ...therapist,
        originalPosition: therapist.bestOrderPosition || 999999
      }))
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
      return sortedTherapists.value.slice(0, limit)
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
      }
    }

    const loadDiagnosisDetails = async (diagnosisId) => {
      try {
        
        // Check if diagnosisId is numeric (ID) or string (name)
        if (/^\d+$/.test(diagnosisId)) {
          // First, try to load ai_diagnosis from user meta if user is authenticated
          let nameEn = ''
          let nameAr = ''
          let useAiDiagnosis = false
          
          if (authStore.user && authStore.token) {
            try {
              const userDiagnosisResponse = await api.get('/api/ai/user-diagnosis-results', {
                headers: {
                  'Authorization': `Bearer ${authStore.token}`
                }
              })
              
              if (userDiagnosisResponse.data.success && userDiagnosisResponse.data.data.current_diagnosis) {
                const userDiagnosis = userDiagnosisResponse.data.data.current_diagnosis
                
                // Use ai_diagnosis if it exists (it's just a text label, no need to match diagnosis_id)
                if (userDiagnosis.ai_diagnosis) {
                  const aiDiagnosis = userDiagnosis.ai_diagnosis.trim()
                  
                  // Parse ai_diagnosis which may be in format "Arabic – English" or single language
                  const parts = aiDiagnosis.split('–').map(p => p.trim())
                  
                  if (parts.length >= 2) {
                    // Format: "Arabic – English"
                    nameAr = parts[0]
                    nameEn = parts[1]
                  } else {
                    // Single language - try to detect if it's Arabic or English
                    const hasArabic = /[\u0600-\u06FF]/.test(aiDiagnosis)
                    if (hasArabic) {
                      nameAr = aiDiagnosis
                      nameEn = aiDiagnosis // Use same for both if only Arabic provided
                    } else {
                      nameEn = aiDiagnosis
                      nameAr = aiDiagnosis // Use same for both if only English provided
                    }
                  }
                  
                  useAiDiagnosis = true
                }
              }
            } catch (userDiagnosisError) {
              // Silently fail - will fall back to system diagnosis
              console.warn('Could not load user diagnosis result:', userDiagnosisError)
            }
          }
          
          // If ai_diagnosis is not available, load system diagnosis as fallback
          if (!useAiDiagnosis) {
            const response = await api.get(`/api/ai/diagnoses/${diagnosisId}`)
            
            if (response.data.success && response.data.data) {
              let diagnosis = response.data.data
              
              // Check if the response is an array (list of diagnoses) or a single diagnosis
              if (Array.isArray(diagnosis)) {
                // Find the specific diagnosis by ID
                diagnosis = diagnosis.find(d => d.id == diagnosisId)
                
                if (!diagnosis) {
                  return
                }
              }
              
              // Use the localized name from the backend, with fallback to manual localization
              nameEn = diagnosis.name_en || diagnosis.name
              nameAr = diagnosis.name_ar || diagnosis.name
              
              // If backend didn't provide localized name, handle it on frontend
              if (diagnosis.name_en && diagnosis.name_ar) {
                nameEn = diagnosis.name_en
                nameAr = diagnosis.name_ar
              }
            }
          }
          
          // Determine localized name and description
          const currentLocale = locale.value || 'en'
          const localizedName = currentLocale === 'ar' ? nameAr : nameEn
          
          // Load description from system diagnosis (always use system description)
          let localizedDescription = ''
          try {
            const response = await api.get(`/api/ai/diagnoses/${diagnosisId}`)
            if (response.data.success && response.data.data) {
              let diagnosis = response.data.data
              if (Array.isArray(diagnosis)) {
                diagnosis = diagnosis.find(d => d.id == diagnosisId)
              }
              if (diagnosis) {
                localizedDescription = currentLocale === 'ar' 
                  ? (diagnosis.description_ar || diagnosis.description) 
                  : (diagnosis.description_en || diagnosis.description)
              }
            }
          } catch (error) {
            console.warn('Could not load diagnosis description:', error)
          }
          
          diagnosisResult.value = {
            title: localizedName,
            title_en: nameEn,
            title_ar: nameAr,
            description: localizedDescription
          }
        } else {
          // It's a diagnosis name, use it directly
          const decodedName = decodeURIComponent(diagnosisId)
          diagnosisResult.value = {
            title: decodedName,
            title_en: decodedName,
            title_ar: decodedName,
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
            title_en: decodedName,
            title_ar: decodedName,
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
      
      diagnosisResult.value = { 
        title, 
        title_en: title,
        title_ar: title,
        description 
      }
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
            // Check if diagnosisId is numeric (ID) or string (name)
            
            // If diagnosisId is numeric, always use ID-based search regardless of settings
            if (/^\d+$/.test(diagnosisId)) {
                        // Load therapists by diagnosis ID
          response = await api.get(`/api/ai/therapists/by-diagnosis/${diagnosisId}`)

          matchedTherapists.value = response.data.data || []
            } else {
              // For non-numeric IDs, check if we should search by name
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
      
      // Auto-click first therapist after loading
      if (matchedTherapists.value.length > 0) {
        nextTick(() => {
          setTimeout(() => {
            if (displayedTherapists.value.length > 0) {
              openTherapistId.value = displayedTherapists.value[0].id
            }
          }, 1000)
        })
      }
    }

    const rediagnose = () => {
      // Clear stored diagnosis data
      localStorage.removeItem('diagnosis_data')
      // Redirect to diagnosis page
      router.push('/diagnosis')
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

    const handleShowDetails = (therapistId) => {
      openTherapistId.value = therapistId
    }

    const handleHideDetails = () => {
      openTherapistId.value = null
    }

    const handleOpenAbout = (therapistId) => {
      selectedTherapist.value = matchedTherapists.value.find(t => t.id === therapistId)
      showAboutPopup.value = true
    }

    const handleOpenBooking = (therapistId) => {
      selectedTherapist.value = matchedTherapists.value.find(t => t.id === therapistId)
      showBookingPopup.value = true
    }

    const handleOpenWhy = (therapistId) => {
      selectedTherapist.value = matchedTherapists.value.find(t => t.id === therapistId)
      showWhyPopup.value = true
    }

    const handleOpenCertificates = (therapistId) => {
      selectedTherapist.value = matchedTherapists.value.find(t => t.id === therapistId)
      showCertificatesPopup.value = true
    }

    const closeAboutPopup = () => {
      showAboutPopup.value = false
      selectedTherapist.value = null
    }

    const closeBookingPopup = () => {
      showBookingPopup.value = false
      selectedTherapist.value = null
    }

    const closeWhyPopup = () => {
      showWhyPopup.value = false
      selectedTherapist.value = null
    }

    const closeCertificatesPopup = () => {
      showCertificatesPopup.value = false
      selectedTherapist.value = null
    }

    // Sorting button handlers (same as therapists page)
    const setSorting = (sortType) => {
      // If clicking 'best' or clicking the same sort, reset to default (empty string)
      if (sortType === 'best' || activeSort.value === sortType) {
        activeSort.value = ''
        return
      }
      
      // Apply new sorting
      activeSort.value = sortType
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

    // Watch for sorting changes to reset show all state
    // This ensures newly shown items follow the current sorting rule
    watch(activeSort, () => {
      updateSorting()
    })

    // Watch for when therapists are loaded to auto-click first therapist
    watch(displayedTherapists, (newTherapists) => {
      if (newTherapists.length > 0) {
        // Wait for DOM to be ready, then auto-click first therapist
        nextTick(() => {
          setTimeout(() => {
            if (firstTherapistCard.value && firstTherapistCard.value[0]) {
              // Set the first therapist as the open therapist
              if (matchedTherapists.value.length > 0) {
                openTherapistId.value = matchedTherapists.value[0].id
              }
            }
          }, 500)
        })
      }
    }, { immediate: false })

    return {
      loading,
      matchedTherapists,
      sortedTherapists,
      displayedTherapists,
      hasMoreTherapists,
      showAllTherapists,
      diagnosisResult,
      route,
      activeSort,
      setSorting,
      firstTherapistCard,
      openTherapistId,
      rediagnose,
      viewTherapist,
      bookAppointment,
      showMoreTherapists,
      updateSorting,
      handleShowDetails,
      handleHideDetails,
      // Popups
      showAboutPopup,
      showBookingPopup,
      showWhyPopup,
      showCertificatesPopup,
      selectedTherapist,
      handleOpenAbout,
      handleOpenBooking,
      handleOpenWhy,
      handleOpenCertificates,
      closeAboutPopup,
      closeBookingPopup,
      closeWhyPopup,
      closeCertificatesPopup
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* RTL Support */
.rtl {
  direction: rtl;
  text-align: right;
}

/* RTL grid order fix is now in global style.css for better specificity */

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