<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('checkout') }}</h1>
        <p class="text-gray-600">{{ $t('completeYourBooking') }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="cartStore.loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        <p class="mt-4 text-gray-600">{{ $t('loadingCheckout') }}</p>
      </div>

      <!-- Redirecting State -->
      <div v-else-if="cartStore.redirecting" class="text-center py-12">
        <div class="animate-pulse">
          <div class="mx-auto h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $t('orderCreated') }}</h3>
        <p class="mt-2 text-gray-600">{{ $t('redirectingToPayment') }}</p>
        <div class="mt-4">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500 mx-auto"></div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="cartStore.error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="mr-3 rtl:ml-3 rtl:mr-0">
            <h3 class="text-sm font-medium text-red-800">{{ $t('errorLoadingCheckout') }}</h3>
            <p class="mt-2 text-sm text-red-700">{{ cartStore.error }}</p>
          </div>
        </div>
      </div>

      <!-- Empty Cart -->
      <div v-else-if="cartStore.itemCount === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m6 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('emptyCart') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('emptyCartDescription') }}</p>
        <div class="mt-6">
          <router-link to="/therapists" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            {{ $t('browseTherapists') }}
          </router-link>
        </div>
      </div>

      <!-- Checkout Content -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Summary -->
        <div class="lg:col-span-2">
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('orderSummary') }}</h2>
            
            <!-- Appointments List -->
            <div class="space-y-4 mb-6">
              <div v-for="item in cartStore.cartItems" :key="item.ID" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                  <!-- Therapist Image -->
                  <div class="flex-shrink-0">
                    <img 
                      v-if="item.therapist_image_url"
                      :src="item.therapist_image_url" 
                      :alt="item.therapist_name || item.therapist_name_en"
                      class="w-12 h-12 rounded-lg object-cover"
                    />
                    <div v-else class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                      <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>

                  <!-- Appointment Details -->
                  <div>
                    <h3 class="font-medium text-gray-900">
                      {{ item.therapist_name_en || item.therapist_name }}
                    </h3>
                    <p class="text-sm text-gray-500">
                      {{ formatDate(item.date_time) }} at {{ formatTime(item.starts) }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ $t('duration') }}: {{ item.period }} {{ $t('minutes') }}
                    </p>
                  </div>
                </div>
                <div class="text-right rtl:text-left">
                  <p class="font-medium text-gray-900">{{ formatPrice(200.00) }}</p>
                </div>
              </div>
            </div>

            <!-- Payment Method -->
            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('paymentMethod') }}</h3>
              <div class="space-y-3">
                <div class="flex items-center">
                  <input
                    id="cash"
                    name="payment-method"
                    type="radio"
                    value="cash"
                    v-model="paymentMethod"
                    class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                  />
                  <label for="cash" class="mr-3 rtl:ml-3 rtl:mr-0 text-sm font-medium text-gray-700">
                    {{ $t('cashPayment') }}
                  </label>
                </div>
                <div class="flex items-center">
                  <input
                    id="card"
                    name="payment-method"
                    type="radio"
                    value="card"
                    v-model="paymentMethod"
                    class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                  />
                  <label for="card" class="mr-3 rtl:ml-3 rtl:mr-0 text-sm font-medium text-gray-700">
                    {{ $t('cardPayment') }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('paymentSummary') }}</h2>
            
            <div class="space-y-4">
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('subtotal') }}</span>
                <span class="text-gray-900">{{ formatPrice(cartStore.totalPrice) }}</span>
              </div>
              
              <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between">
                  <span class="text-lg font-medium text-gray-900">{{ $t('total') }}</span>
                  <span class="text-lg font-medium text-gray-900">{{ formatPrice(cartStore.totalPrice) }}</span>
                </div>
              </div>
            </div>

            <button
              @click="processPayment"
              :disabled="cartStore.loading || !paymentMethod"
              class="w-full mt-6 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="cartStore.loading">{{ $t('processing') }}...</span>
              <span v-else>{{ $t('completePayment') }}</span>
            </button>

            <div class="mt-4 text-center">
              <router-link to="/cart" class="text-sm text-blue-600 hover:text-blue-800">
                {{ $t('backToCart') }}
              </router-link>
            </div>
            
            <!-- Appointment Change Terms -->
            <div v-if="settingsStore.getAppointmentChangeTerms" class="mt-6 p-4 bg-gray-50 rounded-lg">
              <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('appointmentChangeTerms') }}</h3>
              <p class="text-sm text-gray-600 leading-relaxed">{{ settingsStore.getAppointmentChangeTerms }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useSettingsStore } from '../stores/settings'
import { useRouter } from 'vue-router'
import { formatPrice } from '../utils/currency'
import { formatGregorianDate } from '@/utils/dateFormatter'

const { t, locale } = useI18n()
const cartStore = useCartStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()
const router = useRouter()

// Get the authenticated user's ID
const userId = computed(() => authStore.user?.id)

// Payment method
const paymentMethod = ref('cash')

const formatDate = (dateTime) => {
  return formatGregorianDate(dateTime, locale?.value || 'en', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (time) => {
  if (!time) return ''
  // Force Gregorian calendar by using 'en-US' locale for time formatting
  return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const processPayment = async () => {
  if (!userId.value || !paymentMethod.value) return
  
  const result = await cartStore.checkout(userId.value)
  if (result.success) {
    // The cart store checkout function handles the redirect to payment URL
    // No need to redirect here as it will redirect to auto_login_url
            // Redirecting to payment
  } else {
    console.error('Payment failed:', result.message)
  }
}

onMounted(() => {
  if (userId.value) {
    cartStore.loadCart(userId.value)
  }
})
</script> 