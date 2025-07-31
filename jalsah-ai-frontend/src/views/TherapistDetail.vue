<template>
  <div :dir="locale === 'ar' ? 'rtl' : 'ltr'" :class="locale === 'ar' ? 'rtl' : 'ltr'">
    
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
        :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'"
      >
        <svg class="w-5 h-5" :class="locale === 'ar' ? 'ml-2' : 'mr-2'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="locale === 'ar' ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'"></path>
        </svg>
        {{ $t('therapistDetail.backToTherapists') }}
      </button>

      <!-- Therapist Header -->
      <div class="card mb-8">
        <div class="md:flex" :class="locale === 'ar' ? 'md:space-x-reverse md:space-x-8' : 'md:space-x-8'">
          <!-- Therapist Image -->
          <div class="md:w-1/3 mb-6 md:mb-0" :class="locale === 'ar' ? 'md:order-2' : 'md:order-1'">
            <img 
              :src="therapist.photo || '/default-therapist.svg'" 
              :alt="therapist.name"
              class="w-full h-64 md:h-80 rounded-lg"
              :class="therapist.photo ? 'object-cover' : 'object-contain bg-gray-100 p-8'"
            />
          </div>

          <!-- Therapist Info -->
          <div class="md:w-2/3" :class="locale === 'ar' ? 'md:order-1' : 'md:order-2'">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ therapist.name }}</h1>
            
            <!-- Rating -->
            <div class="flex items-center mb-4" :class="locale === 'ar' ? 'space-x-reverse space-x-2' : 'space-x-2'">
              <StarRating :rating="therapist.rating || 0" size="w-5 h-5" />
              <span class="text-lg text-gray-600">
                {{ (therapist.rating || 0).toFixed(1) }} ({{ therapist.total_ratings || 0 }} {{ $t('therapistDetail.reviews') }})
              </span>
            </div>

            <!-- Price -->
            <div class="text-2xl font-bold text-primary-600 mb-4">
              {{ formatPrice(therapist.price?.others, locale) || $t('common.contact') }} {{ $t('therapistDetail.perSession') }}
            </div>


          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: About & Bio -->
        <div class="lg:col-span-2 space-y-8">
          <!-- About Section -->
          <div class="card">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('therapistDetail.about') }}</h2>
            <div class="space-y-4">
              <div>
                <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.bio') }}</h3>
                <p class="text-gray-600 leading-relaxed">
                  {{ therapist.bio || $t('therapistDetail.bioDefault') }}
                </p>
              </div>
              <div v-if="therapist.certifications">
                <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.certifications') }}</h3>
                <p class="text-gray-600">{{ therapist.certifications }}</p>
              </div>
            </div>
          </div>

          <!-- Specializations & Expertise -->
          <div class="card">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $t('therapists.specializations') }}</h2>
            
            <div v-if="therapist.diagnoses && therapist.diagnoses.length > 0" class="space-y-6">
              <div 
                v-for="diagnosis in therapist.diagnoses" 
                :key="diagnosis.id"
                class="border-b border-gray-200 pb-6 last:border-b-0"
              >
                <div class="flex items-center justify-between mb-3">
                  <h3 class="font-medium text-gray-900 text-lg">{{ diagnosis.name }}</h3>
                  <div class="flex items-center" :class="$i18n.locale === 'ar' ? 'space-x-reverse space-x-2' : 'space-x-2'">
                    <StarRating :rating="diagnosis.rating || 0" size="w-4 h-4" />
                    <span class="text-sm text-gray-600">{{ diagnosis.rating || 0 }}/5</span>
                  </div>
                </div>
                <p v-if="diagnosis.suitability_message" class="text-gray-600">
                  {{ diagnosis.suitability_message }}
                </p>
              </div>
            </div>
            
            <div v-else class="text-center py-8">
              <p class="text-gray-600">{{ $t('therapistDetail.noSpecializations') }}</p>
            </div>
          </div>
        </div>

                 <!-- Right Column: Sidebar -->
         <div class="space-y-8">
           <!-- Availability -->
           <div class="card">
             <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('therapistDetail.availability') }}</h2>
             <div class="space-y-4">
               <div>
                 <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.nextAvailable') }}</h3>
                 <p class="text-gray-600">{{ formatEarliestSlot(therapist.earliest_slot) }}</p>
               </div>
               <div>
                 <h3 class="font-medium text-gray-900 mb-2">{{ $t('therapistDetail.sessionDuration') }}</h3>
                 <p class="text-gray-600">{{ therapist.earliest_slot ? '45 ' + $t('therapistDetail.minutes') : $t('therapistDetail.contactForDetails') }}</p>
               </div>
             </div>
           </div>
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
import { useI18n } from 'vue-i18n'

import api from '@/services/api'
import { formatPrice } from '@/utils/currency'
import StarRating from '@/components/StarRating.vue'
export default {
  name: 'TherapistDetail',
  components: {
    StarRating
  },
  setup() {
    // Initialize component setup
    const route = useRoute()
    const router = useRouter()
    const toast = useToast()
    const { t, locale } = useI18n()

    
    const loading = ref(true)
    const therapist = ref(null)



    const formatEarliestSlot = (slotTime) => {
      if (!slotTime) {
        return t('therapists.noSlotsAvailable')
      }
      
      // Parse the slot time
      let slotDate
      try {
        if (slotTime.includes('T') || slotTime.includes(' ')) {
          // Full datetime string
          slotDate = new Date(slotTime)
        } else {
          // Time only string (e.g., "09:00")
          const [hours, minutes] = slotTime.split(':')
          if (!hours || !minutes || isNaN(parseInt(hours)) || isNaN(parseInt(minutes))) {
            return t('therapists.noSlotsAvailable')
          }
          const now = new Date()
          slotDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(hours), parseInt(minutes))
          
          // If the time has passed today, assume it's tomorrow
          if (slotDate < now) {
            slotDate.setDate(slotDate.getDate() + 1)
          }
        }
        
        // Check if the date is valid
        if (isNaN(slotDate.getTime())) {
          return t('therapists.noSlotsAvailable')
        }
        
        const now = new Date()
        const diffTime = slotDate.getTime() - now.getTime()
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        
        const currentLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US'
        
        // Format based on when the slot is
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
        // If there's any error in date parsing or formatting, return the fallback message
        console.warn('Error formatting date:', error)
        return t('therapists.noSlotsAvailable')
      }
    }

    const loadTherapist = async () => {
      loading.value = true
      try {
        // Check if route and params are available
        if (!route || !route.params || !route.params.id) {
          throw new Error('Invalid route parameters')
        }
        
        const response = await api.get(`/api/ai/therapists/${route.params.id}`)
        therapist.value = response.data.data
      } catch (error) {
        console.error('Error loading therapist:', error)
        
        if (error.response?.status === 404) {
          toast.error('Therapist not found or not available')
          // Redirect back to therapists list
          router.push('/therapists')
        } else {
          toast.error('Failed to load therapist profile')
        }
      } finally {
        loading.value = false
      }
    }



    onMounted(() => {
      // Load therapist data on component mount
      loadTherapist()
    })

    return {
      loading,
      therapist,
      route,
      formatPrice,
      formatEarliestSlot,
      locale
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

.rtl .form-label {
  text-align: right;
}
</style> 