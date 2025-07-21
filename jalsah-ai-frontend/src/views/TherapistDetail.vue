<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    
    <div v-if="loading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">{{ $t('common.loading') }}</p>
      </div>
    </div>

    <div v-else-if="therapist" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Back Button -->
      <button 
        @click="$router.go(-1)"
        class="flex items-center text-primary-600 hover:text-primary-700 mb-6"
        :class="$i18n.locale === 'ar' ? 'flex-row-reverse' : 'flex-row'"
      >
        <svg class="w-5 h-5" :class="$i18n.locale === 'ar' ? 'ml-2' : 'mr-2'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="$i18n.locale === 'ar' ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'"></path>
        </svg>
        {{ $t('therapistDetail.backToTherapists') }}
      </button>

      <!-- Therapist Header -->
      <div class="card mb-8">
        <div class="md:flex" :class="$i18n.locale === 'ar' ? 'md:space-x-reverse md:space-x-8' : 'md:space-x-8'">
          <!-- Therapist Image -->
          <div class="md:w-1/3 mb-6 md:mb-0" :class="$i18n.locale === 'ar' ? 'md:order-2' : 'md:order-1'">
            <img 
              :src="therapist.photo || '/default-therapist.svg'" 
              :alt="therapist.name"
              class="w-full h-64 md:h-80 rounded-lg"
              :class="therapist.photo ? 'object-cover' : 'object-contain bg-gray-100 p-8'"
            />
          </div>

          <!-- Therapist Info -->
          <div class="md:w-2/3" :class="$i18n.locale === 'ar' ? 'md:order-1' : 'md:order-2'">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ therapist.name }}</h1>
            
            <!-- Rating -->
            <div class="flex items-center mb-4" :class="$i18n.locale === 'ar' ? 'space-x-reverse space-x-2' : 'space-x-2'">
              <div class="flex text-yellow-400">
                <svg v-for="i in 5" :key="i" class="w-5 h-5" :class="i <= getAverageRating() ? 'fill-current' : 'fill-gray-300'" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292c.3.921-.755 1.688-1.54 1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
              <span class="text-lg text-gray-600">{{ getAverageRating().toFixed(1) }} ({{ therapist.diagnoses?.length || 0 }} {{ $t('therapistDetail.reviews') }})</span>
            </div>

            <!-- Price -->
            <div class="text-2xl font-bold text-primary-600 mb-4">
              ${{ therapist.price?.others || 'Contact' }} {{ $t('therapistDetail.perSession') }}
            </div>

            <!-- Bio -->
            <p class="text-gray-600 mb-6 leading-relaxed">
              {{ therapist.bio || $t('therapistDetail.bioDefault') }}
            </p>

            <!-- Specializations -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $t('therapists.specializations') }}</h3>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="diagnosis in therapist.diagnoses" 
                  :key="diagnosis.id"
                  class="bg-primary-100 text-primary-800 px-3 py-1 rounded-full text-sm"
                >
                  {{ diagnosis.name }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4">
              <button 
                @click="bookAppointment"
                class="btn-primary text-lg px-8 py-3"
              >
                {{ $t('therapistDetail.bookSession') }}
              </button>
              <button 
                @click="addToCart"
                class="btn-outline text-lg px-8 py-3"
              >
                {{ $t('therapistDetail.addToCart') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Detailed Information -->
      <div class="grid md:grid-cols-2 gap-8">
        <!-- About -->
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('therapistDetail.about') }}</h2>
          <div class="space-y-4">
            <div>
              <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.experience') }}</h3>
              <p class="text-gray-600">{{ $t('therapistDetail.experienceText') }}</p>
            </div>
            <div>
              <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.approach') }}</h3>
              <p class="text-gray-600">{{ $t('therapistDetail.approachText') }}</p>
            </div>
            <div>
              <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.languages') }}</h3>
              <p class="text-gray-600">{{ $t('therapistDetail.languagesText') }}</p>
            </div>
          </div>
        </div>

        <!-- Availability -->
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('therapistDetail.availability') }}</h2>
          <div class="space-y-4">
            <div>
              <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.nextAvailable') }}</h3>
              <p class="text-gray-600">{{ therapist.earliest_slot || $t('therapists.contactForAvailability') }}</p>
            </div>
            <div>
              <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.sessionDuration') }}</h3>
              <p class="text-gray-600">{{ $t('therapistDetail.sessionDurationText') }}</p>
            </div>
            <div>
              <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.sessionType') }}</h3>
              <p class="text-gray-600">{{ $t('therapistDetail.sessionTypeText') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Reviews -->
      <div class="card mt-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $t('therapistDetail.reviews') }}</h2>
        
        <div v-if="therapist.diagnoses && therapist.diagnoses.length > 0" class="space-y-6">
          <div 
            v-for="diagnosis in therapist.diagnoses" 
            :key="diagnosis.id"
            class="border-b border-gray-200 pb-6 last:border-b-0"
          >
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-medium text-gray-900">{{ diagnosis.name }}</h3>
              <div class="flex items-center" :class="$i18n.locale === 'ar' ? 'space-x-reverse space-x-2' : 'space-x-2'">
                <div class="flex text-yellow-400">
                  <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= (diagnosis.rating || 0) ? 'fill-current' : 'fill-gray-300'" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292c.3.921-.755 1.688-1.54 1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
                <span class="text-sm text-gray-600">{{ diagnosis.rating || 0 }}/5</span>
              </div>
            </div>
            <p v-if="diagnosis.suitability_message" class="text-gray-600 text-sm">
              {{ diagnosis.suitability_message }}
            </p>
          </div>
        </div>
        
        <div v-else class="text-center py-8">
          <p class="text-gray-600">{{ $t('therapistDetail.noReviews') }}</p>
        </div>
      </div>
    </div>

    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('therapistDetail.therapistNotFound') }}</h3>
        <p class="text-gray-600">{{ $t('therapistDetail.therapistNotFoundMessage') }}</p>
        <button 
          @click="$router.push('/therapists')"
          class="btn-primary mt-4"
        >
          {{ $t('therapistDetail.browseTherapists') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart'
import api from '@/services/api'
export default {
  name: 'TherapistDetail',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const toast = useToast()
    const cartStore = useCartStore()
    
    const loading = ref(true)
    const therapist = ref(null)

    const getAverageRating = () => {
      if (!therapist.value?.diagnoses || therapist.value.diagnoses.length === 0) return 0
      const validRatings = therapist.value.diagnoses.filter(d => d.rating && !isNaN(d.rating) && d.rating > 0)
      if (validRatings.length === 0) return 0
      const total = validRatings.reduce((sum, d) => sum + (d.rating || 0), 0)
      return total / validRatings.length
    }

    const loadTherapist = async () => {
      loading.value = true
      try {
        const response = await api.get(`/api/ai/therapists/${route.params.id}`)
        therapist.value = response.data.data
      } catch (error) {
        toast.error('Failed to load therapist profile')
        console.error('Error loading therapist:', error)
      } finally {
        loading.value = false
      }
    }

    const bookAppointment = () => {
      router.push(`/booking/${route.params.id}`)
    }

    const addToCart = async () => {
      // This would typically add a default slot to cart
      // For now, we'll redirect to booking page
      router.push(`/booking/${route.params.id}`)
    }

    onMounted(() => {
      loadTherapist()
    })

    return {
      loading,
      therapist,
      getAverageRating,
      bookAppointment,
      addToCart
    }
  }
}
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
</style> 