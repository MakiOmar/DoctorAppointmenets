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

      <!-- Search and Sorting -->
      <div class="card mb-8">
        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
          <!-- Search Filter -->
          <div class="w-full lg:w-1/4">
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
          
          <!-- Best/Order Sorting Button -->
          <div class="w-full lg:w-1/4">
            <label class="form-label mb-4">{{ $t('therapists.sorting.best') }}</label>
            <button
              @click="setSorting('best')"
              class="w-full px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
              :class="activeSort === 'best' 
                ? 'border-primary-600 bg-primary-50 text-primary-700' 
                : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400'"
            >
              {{ $t('therapists.sorting.best') }}
            </button>
          </div>
          
          <!-- Lowest Price Button -->
          <div class="w-full lg:w-1/4">
            <label class="form-label mb-4">{{ $t('therapists.sorting.priceLow') }}</label>
            <button
              @click="setSorting('price-low')"
              class="w-full px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
              :class="activeSort === 'price-low' 
                ? 'border-primary-600 bg-primary-50 text-primary-700' 
                : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400'"
            >
              {{ $t('therapists.sorting.priceLow') }}
            </button>
          </div>
          
          <!-- Nearest Slot Button -->
          <div class="w-full lg:w-1/4">
            <label class="form-label mb-4">{{ $t('therapists.sorting.nearest') }}</label>
            <button
              @click="setSorting('nearest')"
              class="w-full px-4 py-2 rounded-lg border text-sm font-medium transition-colors"
              :class="activeSort === 'nearest' 
                ? 'border-primary-600 bg-primary-50 text-primary-700' 
                : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400'"
            >
              {{ $t('therapists.sorting.nearest') }}
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
        <p class="text-gray-600">{{ $t('therapists.loading') }}</p>
      </div>

      <!-- Therapists List -->
      <div v-else-if="displayedTherapists.length > 0" class="space-y-6">
        <TherapistCard
          v-for="(therapist, index) in displayedTherapists" 
          :key="therapist.id"
          :therapist="therapist"
          :position="therapist.originalPosition"
          :show-order-badge="false"
          :settings-store="settingsStore"
          :open-therapist-id="openTherapistId"
          @click="viewTherapist"
          @book="bookAppointment"
          @show-details="handleShowDetails"
          @hide-details="handleHideDetails"
        />
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
    const openTherapistId = ref(null)
    
    // Sorting controls - single active sort
    const activeSort = ref('') // Active sorting: '', 'best', 'price-low', 'nearest'

    // Search query for therapist names
    const searchQuery = ref('')
    const searchTimeout = ref(null)


    // Computed property to get therapists with their original system positions
    const therapistsWithOriginalPositions = computed(() => {
      if (!therapists.value.length) return []
      
      
      // Return all therapists with proper position data
      return therapists.value.map((therapist, index) => ({
        ...therapist,
        originalPosition: therapist.frontend_order || index + 1,
        displayOrder: therapist.display_order || index + 1,
        frontendOrder: therapist.frontend_order || index + 1
      }))
    })

    // Computed property to sort therapists (search is now handled by API)
    const sortedTherapists = computed(() => {
      let sorted = [...therapistsWithOriginalPositions.value]

      // Apply active sorting
      switch (activeSort.value) {
        case 'best':
          // Sort by rating (best first) - higher rating first, then by total_ratings
          sorted.sort((a, b) => {
            const ratingA = a.rating || 0
            const ratingB = b.rating || 0
            const totalA = a.total_ratings || 0
            const totalB = b.total_ratings || 0
            
            // First sort by rating (descending)
            if (ratingA !== ratingB) {
              return ratingB - ratingA
            }
            // If ratings are equal, sort by total_ratings (descending)
            return totalB - totalA
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
          // Default sorting (no specific sorting applied)
          break
      }

      return sorted
    })

    // Computed property for displayed therapists (no pagination)
    const displayedTherapists = computed(() => {
      return sortedTherapists.value
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
      // Use earliest_slot_data if available (structured data with date and time)
      if (therapist.earliest_slot_data && therapist.earliest_slot_data.date && therapist.earliest_slot_data.time) {
        try {
          const dateTime = new Date(therapist.earliest_slot_data.date + ' ' + therapist.earliest_slot_data.time)
          return isNaN(dateTime.getTime()) ? 999999 : dateTime.getTime()
        } catch (error) {
          console.error('Error parsing earliest_slot_data:', error)
          return 999999
        }
      }
      
      // Fallback to earliest_slot if earliest_slot_data is not available
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


    // Sorting button handlers
    const setSorting = (sortType) => {
      // If clicking the same sort, deactivate it (reset to default)
      if (activeSort.value === sortType) {
        activeSort.value = ''
        return
      }
      
      // Apply new sorting
      activeSort.value = sortType
    }


    const loadTherapists = async () => {
      loading.value = true
      try {
        let response
        
        // If search query exists, use search API
        if (searchQuery.value.trim()) {
          const params = {
            q: searchQuery.value.trim()
          }
          
          response = await api.get('/api/ai/therapists/search', { params })
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

    const handleShowDetails = (therapistId) => {
      openTherapistId.value = therapistId
    }

    const handleHideDetails = () => {
      openTherapistId.value = null
    }


    // Watch for search query changes with debouncing
    watch(searchQuery, (newQuery) => {
      // Clear existing timeout
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      
      // Set new timeout for debounced search
      searchTimeout.value = setTimeout(() => {
        loadTherapists()
      }, 500) // 500ms delay
    })

    onMounted(() => {
      loadTherapists()
      loadDiagnoses()
    })

    return {
      loading,
      therapists,
      diagnoses,
      searchQuery,
      searchTimeout,
      activeSort,
      sortedTherapists,
      displayedTherapists,
      openTherapistId,
      settingsStore,
      setSorting,
      viewTherapist,
      bookAppointment,
      handleShowDetails,
      handleHideDetails
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