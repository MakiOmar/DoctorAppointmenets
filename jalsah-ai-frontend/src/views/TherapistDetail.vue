<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
      <p class="mt-4 text-gray-600">{{ $t('loadingTherapist') }}</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h3 class="text-lg text-red-800 mb-2">{{ $t('errorLoadingTherapist') }}</h3>
        <p class="text-red-700">{{ error }}</p>
        <button @click="loadTherapist" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          {{ $t('retry') }}
        </button>
      </div>
    </div>

    <!-- Therapist Details -->
    <div v-else-if="therapist" class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex flex-col md:flex-row gap-6">
          <!-- Therapist Image -->
          <div class="flex-shrink-0">
            <div class="w-48 h-48 bg-gray-200 rounded-lg overflow-hidden">
              <img
                v-if="therapist.profile_image_url"
                :src="therapist.profile_image_url"
                :alt="therapist.name_en || therapist.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-24 h-24 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Therapist Info -->
          <div class="flex-1">
            <h1 class="text-3xl text-gray-900 mb-2">
              {{ therapist.name_en || therapist.name }}
            </h1>
            
            <!-- Rating -->
            <div v-if="settingsStore && settingsStore.isRatingsEnabled" class="flex items-center mb-4">
              <div class="flex items-center">
                <span class="text-yellow-400 text-xl">★</span>
                <span class="ml-2 text-lg text-gray-900">{{ therapist.rating || 5.0 }}</span>
              </div>
              <span class="mx-3 text-gray-300">•</span>
              <span class="text-gray-600">{{ therapist.total_ratings || 0 }} {{ $t('reviews') }}</span>
            </div>

            <!-- Bio -->
            <p class="text-gray-600 mb-6 leading-relaxed">
              {{ therapist.bio_en || therapist.bio || $t('noBioAvailable') }}
            </p>

            <!-- Specializations -->
            <div v-if="therapist.diagnoses && therapist.diagnoses.length > 0" class="mb-6">
              <h3 class="text-lg text-gray-900 mb-3">{{ $t('specializations') }}</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="diagnosis in therapist.diagnoses"
                  :key="diagnosis.id"
                  class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full"
                >
                  {{ diagnosis.name_en || diagnosis.name }}
                </span>
              </div>
            </div>

            <!-- Session Price -->
            <div class="mb-6">
              <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
                <div>
                  <span class="text-lg text-gray-900">{{ $t('sessionPrice') }}</span>
                  <p class="text-sm text-gray-600">{{ $t('perSession') }}</p>
                </div>
                <span class="text-2xl text-gray-900">{{ formatPrice(therapist?.price?.price || therapist?.price?.others || 200.00, $i18n.locale, therapist?.price?.currency_symbol || getCurrencySymbol(therapist?.price?.currency || settingsStore.userCurrencyCode)) }}</span>
              </div>
            </div>

            <!-- Book Now Button -->
            <button
              @click="openBookingModal"
              class="w-full md:w-auto px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-lg"
            >
              {{ $t('bookNow') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Certificates Section -->
      <div v-if="therapist.certificates && therapist.certificates.length > 0" class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl text-gray-900 mb-6">{{ $t('certificates') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="certificate in therapist.certificates"
            :key="certificate.id"
            class="bg-gray-50 rounded-lg p-4"
          >
            <div class="aspect-square bg-white rounded-lg border border-gray-200 overflow-hidden mb-3">
              <img
                v-if="certificate.type === 'image'"
                :src="certificate.url"
                :alt="certificate.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
            </div>
            <h3 class="font-medium text-gray-900 mb-1">{{ certificate.name }}</h3>
            <p class="text-sm text-gray-600">{{ certificate.size }}</p>
          </div>
        </div>
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
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from '../stores/settings'
import api from '../services/api'
import { formatPrice, getCurrencySymbol } from '../utils/currency'
import BookingModal from '../components/BookingModal.vue'

const { t } = useI18n()
const route = useRoute()
const settingsStore = useSettingsStore()

// Reactive data
const therapist = ref(null)
const loading = ref(true)
const error = ref(null)
const showBookingModal = ref(false)

// Demo user ID for testing
const userId = 85

// Methods
const loadTherapist = async () => {
  if (!route || !route.params || !route.params.id) {
    error.value = 'Invalid therapist ID'
    loading.value = false
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await api.get(`/api/ai/therapists/${route.params.id}`)
    therapist.value = response.data
  } catch (err) {
    console.error('Error loading therapist:', err)
    if (err.response?.status === 404) {
      error.value = 'Therapist not found'
    } else {
      error.value = 'Failed to load therapist details'
    }
  } finally {
    loading.value = false
  }
}

const openBookingModal = () => {
  showBookingModal.value = true
}

const handleAppointmentAdded = (appointment) => {
          // Appointment added successfully
  // You can show a success message or redirect to cart
}

// Load therapist data on component mount
onMounted(() => {
  loadTherapist()
})
</script>

<style scoped>
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

.rtl .form-label {
  text-align: right;
}
</style> 