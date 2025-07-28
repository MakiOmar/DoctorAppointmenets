<template>
  <div 
    class="card hover:shadow-lg transition-shadow cursor-pointer"
    @click="$emit('click', therapist.id)"
  >
    <!-- Therapist Image -->
    <div class="relative mb-4">
      <img 
        :src="therapist.photo || '/default-therapist.svg'" 
        :alt="therapist.name"
        class="w-full h-48 rounded-lg"
        :class="therapist.photo ? 'object-cover' : 'object-contain bg-gray-100 p-4'"
      />
      <div class="absolute top-2 right-2 bg-primary-600 text-white px-2 py-1 rounded-full text-sm font-medium">
        {{ therapist.price?.others || $t('common.contact') }}
      </div>
    </div>

    <!-- Therapist Info -->
    <div class="space-y-3">
      <h3 class="text-xl font-semibold text-gray-900">{{ therapist.name }}</h3>
      
      <div class="flex items-center" :class="$i18n.locale === 'ar' ? 'space-x-reverse space-x-2' : 'space-x-2'">
        <StarRating :rating="getAverageRating(therapist)" />
        <span class="text-sm text-gray-600">
          {{ isNaN(getAverageRating(therapist)) ? '0.0' : getAverageRating(therapist).toFixed(1) }} ({{ therapist.diagnoses?.length || 0 }} {{$t('therapistDetail.reviews')}})
        </span>
      </div>

      <p class="text-gray-600 text-sm line-clamp-2">
        {{ therapist.bio || $t('therapists.bioDefault') }}
      </p>

      <!-- Suitability Message (only show if provided) -->
      <div v-if="suitabilityMessage" class="bg-primary-50 border border-primary-200 rounded-lg p-3">
        <p class="text-sm text-primary-800">
          {{ suitabilityMessage }}
        </p>
      </div>

      <!-- Next Available Slot -->
      <div class="flex items-center text-sm text-gray-600" :class="$i18n.locale === 'ar' ? 'space-x-reverse space-x-2' : 'space-x-2'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ formatEarliestSlot(therapist) }}</span>
      </div>

      <!-- Book Button -->
      <button
        @click.stop="$emit('book', therapist.id)"
        class="w-full btn-primary"
      >
        {{ $t('therapists.bookSession') }}
      </button>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
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
    const { t } = useI18n()

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

    const formatEarliestSlot = (therapist) => {
      if (!therapist.earliest_slot) {
        return t('therapists.noSlotsAvailable')
      }
      
      const slotDate = new Date(therapist.earliest_slot)
      const now = new Date()
      const diffTime = slotDate.getTime() - now.getTime()
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays === 0) {
        return t('therapists.availableToday', { 
          time: slotDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
        })
      } else if (diffDays === 1) {
        return t('therapists.availableTomorrow', { 
          time: slotDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
        })
      } else {
        return t('therapists.availableOn', { 
          date: slotDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }),
          time: slotDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
        })
      }
    }

    return {
      getAverageRating,
      suitabilityMessage,
      formatEarliestSlot
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