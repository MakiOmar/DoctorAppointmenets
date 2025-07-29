<template>
  <div 
    class="card hover:shadow-lg transition-shadow cursor-pointer"
    @click="$emit('click', therapist.id)"
  >
    <div class="flex items-start gap-6" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
      <!-- Therapist Image -->
      <div class="relative flex-shrink-0">
        <img 
          :src="therapist.photo || '/default-therapist.svg'" 
          :alt="therapist.name"
          class="w-32 h-32 rounded-lg"
          :class="therapist.photo ? 'object-cover' : 'object-contain bg-gray-100 p-4'"
        />
        <div class="absolute top-2 right-2 bg-primary-600 text-white px-2 py-1 rounded-full text-sm font-medium">
          {{ therapist.price?.others || $t('common.contact') }}
        </div>
      </div>

      <!-- Therapist Info -->
      <div class="flex-1 flex flex-col justify-between min-h-32">
        <!-- Top Section: Name, Rating, Bio -->
        <div class="space-y-4">
          <div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ therapist.name }}</h3>
            
            <div class="flex items-center gap-2">
              <StarRating :rating="getAverageRating(therapist)" />
              <span class="text-sm text-gray-600">
                {{ isNaN(getAverageRating(therapist)) ? '0.0' : getAverageRating(therapist).toFixed(1) }} ({{ therapist.diagnoses?.length || 0 }} {{$t('therapistDetail.reviews')}})
              </span>
            </div>
          </div>

          <p class="text-gray-600 text-sm line-clamp-2 leading-relaxed">
            {{ therapist.bio || $t('therapists.bioDefault') }}
          </p>

          <!-- Specializations/Diagnoses -->
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="diagnosis in therapist.diagnoses?.slice(0, 3)" 
              :key="diagnosis.id"
              class="bg-primary-100 text-primary-800 text-xs px-3 py-1 rounded-full"
            >
              {{ diagnosis.name }}
            </span>
            <span v-if="therapist.diagnoses?.length > 3" class="text-xs text-gray-500 px-2 py-1">
              {{ $t('therapists.more', { count: therapist.diagnoses.length - 3 }) }}
            </span>
          </div>

          <!-- Suitability Message (only show if provided) -->
          <div v-if="suitabilityMessage" class="bg-primary-50 border border-primary-200 rounded-lg p-3">
            <p class="text-sm text-primary-800">
              {{ suitabilityMessage }}
            </p>
          </div>
        </div>

        <!-- Bottom Section: Availability and Book Button -->
        <div class="flex items-center justify-between mt-6" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
          <!-- Next Available Slot -->
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ formatEarliestSlot(therapist) }}</span>
          </div>

          <!-- Book Button -->
          <button
            @click.stop="showTherapistDetails"
            class="btn-primary px-6 py-2"
          >
            {{ showDetails ? $t('common.hide') : $t('therapists.viewDetails') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Therapist Details Inside Card -->
    <div v-if="showDetails" class="mt-6 border-t border-gray-200 pt-6">
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
      <p class="text-gray-600 mt-2">{{ $t('therapistDetails.loading') }}</p>
    </div>
    
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-600">{{ $t('therapistDetails.error') }}</p>
      <button @click="loadTherapistDetails" class="btn-secondary mt-2">
        {{ $t('common.retry') }}
      </button>
    </div>
    
    <div v-else-if="details" class="space-y-6">
      <!-- Personal Information -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.personalInfo') }}</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.name') }}</label>
            <p class="text-gray-900">{{ details.name }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.nameEn') }}</label>
            <p class="text-gray-900">{{ details.name_en }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.specialty') }}</label>
            <p class="text-gray-900">{{ details.specialty }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.jalsahAiName') }}</label>
            <p class="text-gray-900">{{ details.jalsah_ai_name }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.email') }}</label>
            <p class="text-gray-900">{{ details.email }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.phone') }}</label>
            <p class="text-gray-900">{{ details.phone }}</p>
          </div>
          <div v-if="details.whatsapp">
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.whatsapp') }}</label>
            <p class="text-gray-900">{{ details.whatsapp }}</p>
          </div>
        </div>
      </div>

      <!-- Application Information -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.applicationInfo') }}</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.applicationDate') }}</label>
            <p class="text-gray-900">{{ details.application_date }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.approvalDate') }}</label>
            <p class="text-gray-900">{{ details.approval_date }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.certificatesCount') }}</label>
            <p class="text-gray-900">{{ (details.certificates || []).length }} {{ $t('therapistDetails.certificates') }}</p>
          </div>
        </div>
      </div>

      <!-- Certificates Section -->
      <div v-if="details.certificates && details.certificates.length > 0" class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ $t('therapistDetails.certificates') }}</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div 
            v-for="(cert, index) in details.certificates" 
            :key="cert.id"
            class="relative group cursor-pointer"
            @click="openLightbox(index)"
          >
            <div class="aspect-square bg-white rounded-lg border border-gray-200 overflow-hidden">
              <img 
                v-if="cert.type === 'image'"
                :src="cert.url" 
                :alt="cert.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
            </div>
            <div class="mt-2 text-center">
              <p class="text-sm text-gray-900 truncate">{{ cert.name }}</p>
              <p class="text-xs text-gray-500">{{ cert.size }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <div v-if="showLightbox" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75" @click="closeLightbox">
      <div class="relative max-w-4xl max-h-full p-4" @click.stop>
        <button @click="closeLightbox" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
          &times;
        </button>
        <div v-if="currentCertificate" class="text-center">
          <img 
            v-if="currentCertificate.type === 'image'"
            :src="currentCertificate.url" 
            :alt="currentCertificate.name"
            class="max-w-full max-h-[80vh] object-contain"
          />
          <div v-else class="bg-white rounded-lg p-8">
            <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ currentCertificate.name }}</h3>
            <p class="text-gray-600 mb-4">{{ currentCertificate.size }}</p>
            <a :href="currentCertificate.url" download class="btn-primary">
              {{ $t('therapistDetails.downloadFile') }}
            </a>
          </div>
        </div>
        <div class="flex justify-center mt-4 space-x-2">
          <button @click="previousCertificate" class="btn-secondary px-4 py-2" :disabled="currentCertificateIndex === 0">
            {{ $t('common.previous') }}
          </button>
          <span class="text-white px-4 py-2">{{ currentCertificateIndex + 1 }} / {{ (details.certificates || []).length }}</span>
          <button @click="nextCertificate" class="btn-secondary px-4 py-2" :disabled="currentCertificateIndex === (details.certificates || []).length - 1">
            {{ $t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import StarRating from './StarRating.vue'

export default {
  name: 'TherapistCard',
  components: {
    StarRating
  },
  props: {
    therapist: {
      type: Object,
      required: true
    },
    diagnosisId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['click', 'book'],
  setup(props) {
    const { t, locale } = useI18n()
    
    const showDetails = ref(false)
    const loading = ref(false)
    const error = ref(null)
    const details = ref(null)
    const showLightbox = ref(false)
    const currentCertificateIndex = ref(0)

    const getAverageRating = (therapist) => {
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

    const suitabilityMessage = computed(() => {
      if (!props.diagnosisId || !props.therapist.diagnoses) return null
      
      const diagnosis = props.therapist.diagnoses.find(d => d.id.toString() === props.diagnosisId.toString())
      return diagnosis?.suitability_message || null
    })

    const showTherapistDetails = () => {
      showDetails.value = !showDetails.value
      if (showDetails.value && !details.value) {
        loadTherapistDetails()
      }
    }

    const loadTherapistDetails = async () => {
      loading.value = true
      error.value = null
      
      try {
        const response = await fetch(`/api/ai/therapists/${props.therapist.id}/details`)
        const data = await response.json()
        
        if (data.success) {
          details.value = data.data
        } else {
          error.value = data.message || t('therapistDetails.loadError')
        }
      } catch (err) {
        console.error('Error loading therapist details:', err)
        error.value = t('therapistDetails.error')
      } finally {
        loading.value = false
      }
    }

    const openLightbox = (index) => {
      currentCertificateIndex.value = index
      showLightbox.value = true
    }

    const closeLightbox = () => {
      showLightbox.value = false
    }

    const nextCertificate = () => {
      if (details.value?.certificates && currentCertificateIndex.value < details.value.certificates.length - 1) {
        currentCertificateIndex.value++
      }
    }

    const previousCertificate = () => {
      if (currentCertificateIndex.value > 0) {
        currentCertificateIndex.value--
      }
    }

    const currentCertificate = computed(() => {
      if (!details.value?.certificates || !Array.isArray(details.value.certificates)) return null
      return details.value.certificates[currentCertificateIndex.value] || null
    })

    const formatEarliestSlot = (therapist) => {
      if (!therapist.earliest_slot) {
        return t('therapists.noSlotsAvailable')
      }
      
      // Validate the date string
      const slotDate = new Date(therapist.earliest_slot)
      
      // Check if the date is valid
      if (isNaN(slotDate.getTime())) {
        return t('therapists.noSlotsAvailable')
      }
      
      const now = new Date()
      const diffTime = slotDate.getTime() - now.getTime()
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      const currentLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US'
      
      try {
        if (diffDays === 0) {
          return t('therapists.availableToday', { 
            time: slotDate.toLocaleTimeString(currentLocale, { hour: '2-digit', minute: '2-digit', hour12: true })
          })
        } else if (diffDays === 1) {
          return t('therapists.availableTomorrow', { 
            time: slotDate.toLocaleTimeString(currentLocale, { hour: '2-digit', minute: '2-digit', hour12: true })
          })
        } else {
          return t('therapists.availableOn', { 
            date: slotDate.toLocaleDateString(currentLocale, { weekday: 'short', month: 'short', day: 'numeric' }),
            time: slotDate.toLocaleTimeString(currentLocale, { hour: '2-digit', minute: '2-digit', hour12: true })
          })
        }
      } catch (error) {
        // If there's any error in date formatting, return the fallback message
        console.warn('Error formatting date:', error)
        return t('therapists.noSlotsAvailable')
      }
    }

    return {
      getAverageRating,
      suitabilityMessage,
      formatEarliestSlot,
      locale,
      showDetails,
      showTherapistDetails,
      loading,
      error,
      details,
      showLightbox,
      currentCertificateIndex,
      currentCertificate,
      openLightbox,
      closeLightbox,
      nextCertificate,
      previousCertificate,
      loadTherapistDetails
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

/* RTL specific spacing adjustments */
.rtl .flex.items-center {
  flex-direction: row;
}

.rtl .flex.flex-wrap {
  flex-direction: row;
}

.rtl .gap-1 > * {
  margin-left: 0.25rem;
  margin-right: 0;
}

.rtl .gap-1 > *:first-child {
  margin-left: 0;
}

/* RTL button positioning */
.rtl .absolute.top-2.right-2 {
  right: auto;
  left: 0.5rem;
}

/* RTL layout adjustments */
.rtl .flex.flex-row-reverse {
  flex-direction: row-reverse;
}

.rtl .flex.flex-row-reverse .gap-6 > * {
  margin-left: 1.5rem;
  margin-right: 0;
}

.rtl .flex.flex-row-reverse .gap-6 > *:first-child {
  margin-left: 0;
}

/* RTL button positioning */
.rtl .absolute.top-2.right-2 {
  right: auto;
  left: 0.5rem;
}

/* Responsive adjustments for horizontal layout */
@media (max-width: 768px) {
  .card .flex {
    flex-direction: column !important;
    gap: 1rem;
  }
  
  .card .flex > * {
    margin: 0;
  }
  
  .card .relative.flex-shrink-0 {
    align-self: center;
  }
  
  .card .w-32.h-32 {
    width: 8rem;
    height: 8rem;
  }
  
  .card .flex.items-center.justify-between {
    flex-direction: column !important;
    gap: 1rem;
    align-items: stretch;
  }
  
  .card .btn-primary {
    width: 100%;
  }
}

/* RTL responsive adjustments */
@media (max-width: 768px) {
  .rtl .grid.md\:grid-cols-2.lg\:grid-cols-3 {
    grid-template-columns: 1fr;
  }
  
  .rtl .card .flex {
    flex-direction: column !important;
  }
  
  .rtl .card .flex.items-center.justify-between {
    flex-direction: column !important;
  }
}
</style> 