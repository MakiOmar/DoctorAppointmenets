<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
    <!-- Therapist Image -->
    <div class="relative h-48 bg-gray-200">
      <img
        v-if="therapist.profile_image_url"
        :src="therapist.profile_image_url"
        :alt="therapist.name_en || therapist.name"
        class="w-full h-full object-cover"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Name and Rating -->
      <div class="flex items-start justify-between mb-3">
        <div>
          <h3 class="text-xl font-semibold text-gray-900 mb-1">
            {{ therapist.name_en || therapist.name }}
          </h3>
          <div class="flex items-center">
            <div class="flex items-center">
              <span class="text-yellow-400">★</span>
              <span class="ml-1 text-sm text-gray-600">{{ therapist.rating || 5.0 }}</span>
            </div>
            <span class="mx-2 text-gray-300">•</span>
            <span class="text-sm text-gray-600">{{ therapist.total_ratings || 0 }} {{ $t('reviews') }}</span>
          </div>
        </div>
      </div>

      <!-- Bio -->
      <p class="text-gray-600 text-sm mb-4 line-clamp-3">
        {{ therapist.bio_en || therapist.bio || $t('noBioAvailable') }}
      </p>

      <!-- Specializations -->
      <div v-if="therapist.diagnoses && therapist.diagnoses.length > 0" class="mb-3">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="diagnosis in therapist.diagnoses.slice(0, 3)"
            :key="diagnosis.id"
            class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
          >
            {{ diagnosis.name_en || diagnosis.name }}
          </span>
          <span v-if="therapist.diagnoses.length > 3" class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
            +{{ therapist.diagnoses.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Session Price -->
      <div class="mb-4">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-600">{{ $t('sessionPrice') }}</span>
          <span class="text-lg font-semibold text-gray-900">{{ formatPrice(200.00) }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex space-x-3">
        <router-link
          :to="`/therapist/${therapist.user_id}`"
          class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-center text-sm font-medium"
        >
          {{ $t('viewDetails') }}
        </router-link>
        <button
          @click="openBookingModal"
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
        >
          {{ $t('bookNow') }}
        </button>
      </div>
    </div>

    <!-- Booking Modal -->
    <BookingModal
      :is-open="showBookingModal"
      :therapist="therapist"
      :user-id="userId"
      @close="showBookingModal = false"
      @appointment-added="handleAppointmentAdded"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatPrice } from '../utils/currency'
import BookingModal from './BookingModal.vue'

const { t } = useI18n()

// Props
const props = defineProps({
  therapist: {
    type: Object,
    required: true
  },
  userId: {
    type: [String, Number],
    required: true
  }
})

// Reactive data
const showBookingModal = ref(false)

// Methods
const openBookingModal = () => {
  showBookingModal.value = true
}

const handleAppointmentAdded = (appointment) => {
  console.log('Appointment added:', appointment)
  // You can emit an event to parent component or show a toast notification
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