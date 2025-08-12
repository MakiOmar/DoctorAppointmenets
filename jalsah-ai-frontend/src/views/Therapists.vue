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

      <!-- Filters -->
      <div class="card mb-8">
        <div class="grid md:grid-cols-4 gap-4">
          <div>
            <label class="form-label">{{ $t('therapists.filters.specialization') }}</label>
            <select v-model="filters.specialization" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option value="">{{ $t('therapists.filters.allSpecializations') }}</option>
              <option v-for="diagnosis in diagnosesWithTherapists" :key="diagnosis.id" :value="diagnosis.id">
                {{ diagnosis.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="form-label">{{ $t('therapists.filters.priceRange') }}</label>
            <select v-model="filters.priceRange" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option value="">{{ $t('therapists.filters.anyPrice') }}</option>
              <option value="lowest">{{ $t('therapists.filters.lowestPrice') }}</option>
              <option value="highest">{{ $t('therapists.filters.highestPrice') }}</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">{{ $t('therapists.filters.nearestAppointment') }}</label>
            <select v-model="filters.nearestAppointment" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option value="">{{ $t('therapists.filters.anyTime') }}</option>
              <option value="closest">{{ $t('therapists.filters.closest') }}</option>
              <option value="farthest">{{ $t('therapists.filters.farthest') }}</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">{{ $t('therapists.filters.sortBy') }}</label>
            <select v-model="filters.sortBy" class="input-field" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
              <option v-if="settingsStore && settingsStore.isRatingsEnabled" value="rating">{{ $t('therapists.filters.highestRated') }}</option>
              <option value="price_low">{{ $t('therapists.filters.lowestPrice') }}</option>
              <option value="price_high">{{ $t('therapists.filters.highestPrice') }}</option>
              <option value="nearest_appointment">{{ $t('therapists.filters.nearestAppointment') }}</option>
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
      <div v-else-if="filteredTherapists.length > 0" class="space-y-6">
        <TherapistCard
          v-for="therapist in filteredTherapists" 
          :key="therapist.id"
          :therapist="therapist"
          :settings-store="settingsStore"
          @click="viewTherapist"
          @book="bookAppointment"
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
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
    const toast = useToast()
    const { t } = useI18n()
    const settingsStore = useSettingsStore()
    
    const loading = ref(true)
    const therapists = ref([])
    const diagnoses = ref([])
    
    const filters = reactive({
      specialization: '',
      priceRange: '',
      nearestAppointment: '',
      sortBy: settingsStore && settingsStore.isRatingsEnabled ? 'rating' : 'nearest_appointment'
    })

    const filteredTherapists = computed(() => {
      let filtered = [...therapists.value]

      // Filter by specialization (diagnosis)
      if (filters.specialization) {
        filtered = filtered.filter(therapist =>
          therapist.diagnoses?.some(d => d.id.toString() === filters.specialization)
        )
      }

      // Sort by price range
      if (filters.priceRange) {
        if (filters.priceRange === 'lowest') {
          filtered.sort((a, b) => (a.price?.others || 0) - (b.price?.others || 0))
        } else if (filters.priceRange === 'highest') {
          filtered.sort((a, b) => (b.price?.others || 0) - (a.price?.others || 0))
        }
      }

      // Sort by nearest appointment if selected
      if (filters.nearestAppointment) {
        if (filters.nearestAppointment === 'closest') {
          // Sort by soonest slot (ascending), therapists with no slot at the end
          filtered.sort((a, b) => getEarliestSlotTime(a) - getEarliestSlotTime(b))
        } else if (filters.nearestAppointment === 'farthest') {
          // Sort by latest slot (descending), therapists with no slot at the end
          filtered.sort((a, b) => getEarliestSlotTime(b) - getEarliestSlotTime(a))
        }
      }

      // Sort therapists by main sortBy dropdown
      filtered.sort((a, b) => {
        switch (filters.sortBy) {
          case 'rating':
            return getAverageRating(b) - getAverageRating(a)
          case 'price_low':
            return (a.price?.others || 0) - (b.price?.others || 0)
          case 'price_high':
            return (b.price?.others || 0) - (a.price?.others || 0)
          case 'nearest_appointment':
            return getEarliestSlotTime(a) - getEarliestSlotTime(b)
          default:
            return 0
        }
      })

      return filtered
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

    // Watch for changes in ratings enabled setting
    watch(() => settingsStore && settingsStore.isRatingsEnabled, (newValue) => {
      if (!newValue && filters.sortBy === 'rating') {
        // If ratings are disabled and current sort is by rating, change to nearest appointment
        filters.sortBy = 'nearest_appointment'
      }
    })

    const loadTherapists = async () => {
      loading.value = true
      try {
        // Add cache busting parameter
        const response = await api.get('/api/ai/therapists', {
          params: {
            _t: Date.now() // Cache busting
          }
        })
        therapists.value = response.data.data || []
        
        // Debug logging
        // Therapists loaded successfully
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

    onMounted(() => {
      loadTherapists()
      loadDiagnoses()
    })

    return {
      loading,
      therapists,
      diagnoses,
      filters,
      filteredTherapists,
      diagnosesWithTherapists,
      settingsStore,
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