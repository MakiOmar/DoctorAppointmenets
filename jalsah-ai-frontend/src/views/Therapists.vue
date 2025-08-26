<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          {{ $t('therapists.title') }}
        </h1>
        <p class="text-lg text-gray-600">
          {{ $t('therapists.subtitle') }}
        </p>
      </div>

      <!-- Diagnosis Filter -->
      <div class="card mb-8">
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="form-label">{{ $t('therapists.filters.specialization') }}</label>
            <select 
              :value="selectedDiagnosis" 
              @change="onDiagnosisChange" 
              class="input-field w-full" 
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
            >
              <option value="">{{ $t('therapists.filters.allSpecializations') }}</option>
              <option v-for="diagnosis in diagnosesWithTherapists" :key="diagnosis.id" :value="diagnosis.id">
                {{ diagnosis.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="form-label">{{ $t('therapists.filters.search') }}</label>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="$t('therapists.filters.searchPlaceholder')"
                class="input-field w-full pr-10"
                :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              />
              <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none" :class="$i18n.locale === 'ar' ? 'left-0 pl-3' : 'right-0 pr-3'">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sorting Controls (only show if diagnosis is selected) -->
      <div v-if="selectedDiagnosis" class="card mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapists.sorting.title') }}</h3>
        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="form-label">{{ $t('therapists.sorting.order') }}</label>
            <select v-model="orderSort" @change="updateSorting" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option value="">{{ $t('therapists.sorting.defaultOrder') }}</option>
              <option value="asc">{{ $t('therapists.sorting.lowestFirst') }}</option>
              <option value="desc">{{ $t('therapists.sorting.highestFirst') }}</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">{{ $t('therapists.sorting.price') }}</label>
            <select v-model="priceSort" @change="updateSorting" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option value="">{{ $t('therapists.sorting.anyPrice') }}</option>
              <option value="lowest">{{ $t('therapists.sorting.lowestPrice') }}</option>
              <option value="highest">{{ $t('therapists.sorting.highestPrice') }}</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">{{ $t('therapists.sorting.appointment') }}</label>
            <select v-model="appointmentSort" @change="updateSorting" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option value="">{{ $t('therapists.sorting.anyTime') }}</option>
              <option value="nearest">{{ $t('therapists.sorting.nearest') }}</option>
              <option value="farthest">{{ $t('therapists.sorting.farthest') }}</option>
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
        <p class="text-gray-600">{{ $t('therapists.loading') }}</p>
      </div>

      <!-- Therapists List -->
      <div v-else-if="displayedTherapists.length > 0" class="space-y-6">
        <TherapistCard
          v-for="(therapist, index) in displayedTherapists" 
          :key="therapist.id"
          :therapist="therapist"
          :diagnosis-id="selectedDiagnosis"
          :position="therapist.originalPosition"
          :show-order-badge="!!selectedDiagnosis"
          :settings-store="settingsStore"
          @click="viewTherapist"
          @book="bookAppointment"
        />
        
        <!-- Show More Button -->
        <div v-if="hasMoreTherapists && !showAllTherapists" class="text-center pt-6">
          <button
            @click="showMoreTherapists"
            class="btn-secondary"
          >
            {{ $t('therapists.showMore') }} ({{ sortedTherapists.length - displayedTherapists.length }} {{ $t('therapists.moreTherapists') }})
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('therapists.noResults') }}</h3>
        <p class="text-gray-600">{{ $t('therapists.noResultsMessage') }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from '@/stores/settings'
import api from '@/services/api'
import StarRating from '@/components/StarRating.vue'
import TherapistCard from '@/components/TherapistCard.vue'

export default {
  name: 'Therapists',
  components: {
    StarRating,
    TherapistCard
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const toast = useToast()
    const { t } = useI18n()
    const settingsStore = useSettingsStore()
    
    const loading = ref(true)
    const therapists = ref([])
    const diagnoses = ref([])
    const showAllTherapists = ref(false)
    
    // Sorting controls (similar to diagnosis results page)
    const orderSort = ref('') // Order sorting: '', 'asc', 'desc'
    const priceSort = ref('') // Price sorting: '', 'lowest', 'highest'
    const appointmentSort = ref('') // Appointment sorting: '', 'nearest', 'farthest'

    // Search query for therapist names
    const searchQuery = ref('')

    // Get selected diagnosis from URL query parameter
    const selectedDiagnosis = computed(() => {
      return route.query.diagnosis || ''
    })

    // Computed property to get therapists with their original system positions
    const therapistsWithOriginalPositions = computed(() => {
      if (!therapists.value.length) return []
      
      const diagnosisId = selectedDiagnosis.value
      
      if (!diagnosisId) {
        // If no diagnosis selected, return all therapists without position data
        return therapists.value.map(therapist => ({
          ...therapist,
          originalPosition: 0,
          displayOrder: 0,
          frontendOrder: 0
        }))
      }
      
      return therapists.value.map((therapist) => {
        // Get the frontend_order from the diagnosis data
        const diagnosis = therapist.diagnoses?.find(d => d.id.toString() === diagnosisId.toString())
        const frontendOrder = parseInt(diagnosis?.frontend_order || '0')
        
        return {
          ...therapist,
          originalPosition: frontendOrder || 1, // Use frontend_order from API, fallback to 1
          displayOrder: parseInt(diagnosis?.display_order || '0'), // Keep display_order for sorting
          frontendOrder: frontendOrder || 1 // Add frontendOrder property for sorting
        }
      })
    })

    // Computed property to filter and sort therapists
    const sortedTherapists = computed(() => {
      let filtered = [...therapistsWithOriginalPositions.value]

      // Apply search filter
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim()
        filtered = filtered.filter(therapist => {
          const name = therapist.name?.toLowerCase() || ''
          const nameEn = therapist.name_en?.toLowerCase() || ''
          const nameAr = therapist.name_ar?.toLowerCase() || ''
          
          return name.includes(query) || nameEn.includes(query) || nameAr.includes(query)
        })
      }

      // Apply order sorting (frontend_order)
      if (orderSort.value) {
        filtered.sort((a, b) => {
          if (orderSort.value === 'asc') {
            return a.frontendOrder - b.frontendOrder
          } else if (orderSort.value === 'desc') {
            return b.frontendOrder - a.frontendOrder
          }
          return 0
        })
      }

      // Apply price sorting
      if (priceSort.value) {
        filtered.sort((a, b) => {
          const priceA = a.price?.others || 0
          const priceB = b.price?.others || 0
          
          if (priceSort.value === 'lowest') {
            return priceA - priceB
          } else if (priceSort.value === 'highest') {
            return priceB - priceA
          }
          return 0
        })
      }

      // Apply appointment sorting
      if (appointmentSort.value) {
        filtered.sort((a, b) => {
          const timeA = getEarliestSlotTime(a)
          const timeB = getEarliestSlotTime(b)
          
          if (appointmentSort.value === 'nearest') {
            return timeA - timeB
          } else if (appointmentSort.value === 'farthest') {
            return timeB - timeA
          }
          return 0
        })
      }

      return filtered
    })

    // Computed property for displayed therapists (with show more functionality)
    const displayedTherapists = computed(() => {
      if (showAllTherapists.value) {
        return sortedTherapists.value
      }
      
      // If no diagnosis selected, show all therapists
      if (!selectedDiagnosis.value) {
        return sortedTherapists.value
      }
      
      // If diagnosis selected, apply limit from settings
      if (settingsStore && !settingsStore.isShowMoreButtonEnabled && settingsStore.getDiagnosisResultsLimit > 0) {
        return sortedTherapists.value.slice(0, settingsStore.getDiagnosisResultsLimit)
      }
      
      return sortedTherapists.value
    })

    // Computed property to check if there are more therapists to show
    const hasMoreTherapists = computed(() => {
      if (!selectedDiagnosis.value) return false
      return sortedTherapists.value.length > displayedTherapists.value.length
    })

    const diagnosesWithTherapists = computed(() => {
      // Build a set of diagnosis IDs that are assigned to at least one therapist
      const assignedIds = new Set()
      therapists.value.forEach(therapist => {
        therapist.diagnoses?.forEach(d => assignedIds.add(d.id))
      })
      // Return only diagnoses that are assigned
      return diagnoses.value.filter(d => assignedIds.has(d.id))
    })

    const getAverageRating = (therapist) => {
      if (!settingsStore.isRatingsEnabled) {
        return 0
      }
      if (!therapist.diagnoses || therapist.diagnoses.length === 0) {
        return 0
      }
      const validRatings = therapist.diagnoses.filter(d => d.rating && !isNaN(d.rating) && d.rating > 0)
      if (validRatings.length === 0) {
        return 0
      }
      const total = validRatings.reduce((sum, d) => sum + Math.min(d.rating || 0, 5), 0)
      const average = total / validRatings.length
      return Math.min(average, 5) // Cap at 5.0
    }

    const getEarliestSlotTime = (therapist) => {
      // If no earliest slot, return a very high number to push to end
      if (!therapist.earliest_slot) {
        return 999999
      }
      
      // Parse the earliest slot time (format: "2024-01-15 09:00" or "09:00")
      let slotTime = therapist.earliest_slot
      
      // If it's just a time (like "09:00"), assume it's today
      if (slotTime.includes(':') && !slotTime.includes('-')) {
        const today = new Date()
        const [hours, minutes] = slotTime.split(':')
        const slotDate = new Date(today.getFullYear(), today.getMonth(), today.getDate(), parseInt(hours), parseInt(minutes))
        
        // If the time has passed today, assume it's tomorrow
        if (slotDate < new Date()) {
          slotDate.setDate(slotDate.getDate() + 1)
        }
        
        return slotDate.getTime()
      }
      
      // If it's a full datetime string, parse it
      const slotDate = new Date(slotTime)
      return isNaN(slotDate.getTime()) ? 999999 : slotDate.getTime()
    }

    // Handle diagnosis filter change
    const onDiagnosisChange = (event) => {
      const diagnosisId = event.target.value
      
      // Update URL with query parameter
      if (diagnosisId) {
        router.push({
          path: '/therapists',
          query: { diagnosis: diagnosisId }
        })
      } else {
        router.push('/therapists')
      }
    }

    // Update sorting (reset show all when sorting changes)
    const updateSorting = () => {
      showAllTherapists.value = false
    }

    // Show more therapists
    const showMoreTherapists = () => {
      showAllTherapists.value = true
    }

    const loadTherapists = async () => {
      loading.value = true
      try {
        let response
        
        // If diagnosis is selected, load therapists by diagnosis
        if (selectedDiagnosis.value) {
          response = await api.get(`/api/ai/therapists/by-diagnosis/${selectedDiagnosis.value}`)
        } else {
          // Load all therapists
          response = await api.get('/api/ai/therapists', {
            params: {
              _t: Date.now() // Cache busting
            }
          })
        }
        
        therapists.value = response.data.data || []
        
      } catch (error) {
        toast.error('Failed to load therapists')
        console.error('Error loading therapists:', error)
      } finally {
        loading.value = false
      }
    }

    const loadDiagnoses = async () => {
      try {
        const response = await api.get('/api/ai/diagnoses')
        diagnoses.value = response.data.data || []
      } catch (error) {
        console.error('Error loading diagnoses:', error)
      }
    }

    const viewTherapist = (therapistId) => {
      router.push(`/therapist/${therapistId}`)
    }

    const bookAppointment = (therapistId) => {
      router.push(`/booking/${therapistId}`)
    }

    // Watch for route changes to reload therapists when diagnosis changes
    watch(() => route.query.diagnosis, () => {
      loadTherapists()
    })

    onMounted(() => {
      loadTherapists()
      loadDiagnoses()
    })

    return {
      loading,
      therapists,
      diagnoses,
      selectedDiagnosis,
      searchQuery,
      orderSort,
      priceSort,
      appointmentSort,
      sortedTherapists,
      displayedTherapists,
      hasMoreTherapists,
      showAllTherapists,
      diagnosesWithTherapists,
      settingsStore,
      onDiagnosisChange,
      updateSorting,
      showMoreTherapists,
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

/* RTL form improvements */
.rtl .form-label {
  text-align: right;
}

.rtl .input-field {
  text-align: right;
}

.rtl select.input-field {
  background-position: left 0.5rem center;
  padding-left: 2.5rem;
  padding-right: 0.75rem;
}

/* RTL list improvements */
.rtl .space-y-6 > * {
  direction: rtl;
}

/* RTL responsive adjustments */
@media (max-width: 768px) {
  .rtl .grid.md\:grid-cols-4 {
    grid-template-columns: 1fr;
  }
}
</style> 