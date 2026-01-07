<template>
  <div>

    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Back Button -->
      <button 
        @click="$router.go(-1)"
        class="flex items-center text-primary-600 hover:text-primary-700 mb-6"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back
      </button>

      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">Loading booking information...</p>
      </div>

      <div v-else-if="therapist" class="grid lg:grid-cols-3 gap-8">
        <!-- Booking Form -->
        <div class="lg:col-span-2">
          <div class="card">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Book Your Session</h1>
            
            <form @submit.prevent="submitBooking" class="space-y-6">
              <!-- Session Type -->
              <div>
                <label class="form-label">Session Type</label>
                <select v-model="booking.sessionType" class="input-field" required>
                  <option value="">Select session type</option>
                  <option value="45">45-Minute Session</option>
                  <option value="60">60-Minute Session</option>
                  <option value="90">90-Minute Session</option>
                </select>
              </div>

              <!-- Date Selection -->
              <div>
                <label class="form-label">Preferred Date</label>
                <input 
                  v-model="booking.date" 
                  type="date" 
                  class="input-field"
                  :min="minDate"
                  required
                />
              </div>

              <!-- Time Selection -->
              <div>
                <label class="form-label">Preferred Time</label>
                <select v-model="booking.time" class="input-field" required>
                  <option value="">Select time</option>
                  <option value="09:00">9:00 AM</option>
                  <option value="10:00">10:00 AM</option>
                  <option value="11:00">11:00 AM</option>
                  <option value="12:00">12:00 PM</option>
                  <option value="13:00">1:00 PM</option>
                  <option value="14:00">2:00 PM</option>
                  <option value="15:00">3:00 PM</option>
                  <option value="16:00">4:00 PM</option>
                  <option value="17:00">5:00 PM</option>
                  <option value="18:00">6:00 PM</option>
                </select>
              </div>

              <!-- Session Notes -->
              <div>
                <label class="form-label">Session Notes (Optional)</label>
                <textarea 
                  v-model="booking.notes" 
                  rows="4" 
                  class="input-field"
                  :placeholder="$t('booking.notesPlaceholder')"
                ></textarea>
              </div>

              <!-- Emergency Contact -->
              <div>
                <label class="form-label">{{ $t('booking.emergencyContact') }}</label>
                <div class="grid md:grid-cols-2 gap-4">
                  <input 
                    v-model="booking.emergencyName" 
                    type="text" 
                    class="input-field"
                                          :placeholder="$t('booking.contactName')"
                  />
                  <input 
                    v-model="booking.emergencyPhone" 
                    type="tel" 
                    class="input-field"
                                          :placeholder="$t('booking.contactPhone')"
                    @input="onEmergencyPhoneInput"
                  />
                </div>
              </div>

              <!-- Terms and Conditions -->
              <div class="flex items-start space-x-3">
                <input 
                  v-model="booking.agreeTerms" 
                  type="checkbox" 
                  class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  required
                />
                <label class="text-sm text-gray-700">
                  I agree to the 
                  <a href="#" class="text-primary-600 hover:text-primary-700">terms and conditions</a> 
                  and understand that this is a professional therapy session.
                </label>
              </div>

              <!-- Submit Button -->
              <button 
                type="submit" 
                :disabled="submitting"
                class="w-full btn-primary text-lg py-3"
              >
                <span v-if="submitting" class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Processing...
                </span>
                <span v-else>Book Session</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Booking Summary -->
        <div class="lg:col-span-1">
          <div class="card sticky top-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Booking Summary</h2>
            
            <!-- Therapist Info -->
            <div class="flex items-center space-x-4 mb-6">
                              <img 
                  :src="therapist.photo || '/default-therapist.svg'" 
                  :alt="therapist.name"
                  class="w-16 h-16 rounded-full"
                  :class="therapist.photo ? 'object-cover' : 'object-contain bg-gray-100 p-1'"
                />
              <div>
                <h3 class="font-semibold text-gray-900">{{ therapist.name }}</h3>
                <p class="text-sm text-gray-600">Licensed Therapist</p>
              </div>
            </div>

            <!-- Session Details -->
            <div class="space-y-4 mb-6">
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('booking.sessionType') }}:</span>
                <span class="font-medium">{{ booking.sessionType || $t('booking.notSelected') }} {{ $t('booking.minutes') }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('booking.date') }}:</span>
                <span class="font-medium">{{ booking.date || $t('booking.notSelected') }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('booking.time') }}:</span>
                <span class="font-medium">{{ booking.time || $t('booking.notSelected') }}</span>
              </div>
            </div>

            <!-- Price Breakdown -->
            <div class="border-t border-gray-200 pt-4">
              <div class="flex justify-between mb-2">
                <span class="text-gray-600">Session Fee:</span>
                <span class="font-medium">{{ formatPrice(getSessionPrice(), $i18n.locale, getBookingCurrency()) }}</span>
              </div>
              <div class="flex justify-between mb-2">
                <span class="text-gray-600">Platform Fee:</span>
                <span class="font-medium">{{ formatPrice(5, $i18n.locale, getBookingCurrency()) }}</span>
              </div>
              <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-2">
                <span>Total:</span>
                <span>{{ formatPrice(getTotalPrice(), $i18n.locale, getBookingCurrency()) }}</span>
              </div>
            </div>

            <!-- Important Notes -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
              <h4 class="font-medium text-blue-900 mb-2">Important Information</h4>
              <ul class="text-sm text-blue-800 space-y-1">
                <li>• Sessions are conducted via secure video call</li>
                <li>• Please join 5 minutes before your scheduled time</li>
                <li>• Cancellation policy: 24 hours notice required</li>
                <li>• Payment is processed securely</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('booking.therapistNotFound') }}</h3>
        <p class="text-gray-600">{{ $t('booking.therapistNotFoundMessage') }}</p>
        <button 
          @click="$router.push('/therapists')"
          class="btn-primary mt-4"
        >
          {{ $t('booking.browseTherapists') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useSettingsStore } from '@/stores/settings'
import api from '@/services/api'
import { formatPrice, getCurrencySymbol } from '@/utils/currency'
export default {
  name: 'Booking',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const toast = useToast()
    const settingsStore = useSettingsStore()
    
    const loading = ref(true)
    const submitting = ref(false)
    const therapist = ref(null)
    
    const booking = ref({
      sessionType: '',
      date: '',
      time: '',
      notes: '',
      emergencyName: '',
      emergencyPhone: '',
      agreeTerms: false
    })

    const minDate = computed(() => {
      const today = new Date()
      return today.toISOString().split('T')[0]
    })

    const getSessionPrice = () => {
      if (!booking.value.sessionType) return 0
      // Use price from pricing info (country-based) or fallback
      const basePrice = therapist.value?.price?.price || therapist.value?.price?.others || 100
      const prices = {
        '45': basePrice,
        '60': basePrice * 1.33,
        '90': basePrice * 2
      }
      return Math.round(prices[booking.value.sessionType])
    }

    // Get currency symbol for booking (from therapist pricing or settings)
    const getBookingCurrency = () => {
      if (therapist.value?.price?.currency_symbol) {
        return therapist.value.price.currency_symbol
      }
      if (therapist.value?.price?.currency) {
        return getCurrencySymbol(therapist.value.price.currency)
      }
      // Fallback to settings store
      const settingsStore = useSettingsStore()
      return getCurrencySymbol(settingsStore.userCurrencyCode)
    }

    const getTotalPrice = () => {
      return getSessionPrice() + 5
    }

    const loadTherapist = async () => {
      loading.value = true
      try {
        const response = await api.get(`/api/ai/therapists/${route.params.id}`)
        therapist.value = response.data.data
      } catch (error) {
        toast.error($t('booking.loadError'))
        console.error('Error loading therapist:', error)
      } finally {
        loading.value = false
      }
    }

    const submitBooking = async () => {
      submitting.value = true
      
      try {
        const bookingData = {
          therapist_id: route.params.id,
          session_type: booking.value.sessionType,
          date: booking.value.date,
          time: booking.value.time,
          notes: booking.value.notes,
          emergency_contact: {
            name: booking.value.emergencyName,
            phone: booking.value.emergencyPhone
          }
        }

        const response = await api.post('/api/ai/bookings', bookingData)
        
        toast.success($t('booking.submitSuccess'))
        
        // Redirect to payment or confirmation page
        router.push(`/booking/confirmation/${response.data.data.id}`)
        
      } catch (error) {
        toast.error($t('booking.submitError'))
        console.error('Error submitting booking:', error)
      } finally {
        submitting.value = false
      }
    }

    // Function to filter only numbers for emergency phone input
    const onEmergencyPhoneInput = (event) => {
      // Remove all non-numeric characters
      const numericValue = event.target.value.replace(/[^0-9]/g, '')
      
      // Update the booking value
      booking.value.emergencyPhone = numericValue
    }

    onMounted(() => {
      loadTherapist()
    })

    return {
      loading,
      submitting,
      therapist,
      booking,
      minDate,
      getSessionPrice,
      getTotalPrice,
      submitBooking,
      onEmergencyPhoneInput,
      formatPrice
    }
  }
}
</script> 