<template>
  <div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('shoppingCart') }}</h1>
        <p class="text-gray-600">{{ $t('cartDescription') }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="cartStore.loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        <p class="mt-4 text-gray-600">{{ $t('loadingCart') }}</p>
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
            <h3 class="text-sm font-medium text-red-800">{{ $t('errorLoadingCart') }}</h3>
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

      <!-- Cart Items -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items List -->
        <div class="lg:col-span-2">
          <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">{{ $t('appointments') }} ({{ cartStore.itemCount }})</h2>
            </div>
            <div class="divide-y divide-gray-200">
              <div v-for="item in cartStore.cartItems" :key="item.ID" class="p-6">
                <div class="flex items-start space-x-4 rtl:space-x-reverse">
                  <!-- Therapist Image -->
                  <div class="flex-shrink-0">
                    <img 
                      v-if="item.therapist_image_url"
                      :src="item.therapist_image_url" 
                      :alt="item.therapist_name || item.therapist_name_en"
                      class="w-16 h-16 rounded-lg object-cover"
                    />
                    <div v-else class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>

                  <!-- Appointment Details -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <div>
                        <h3 class="text-lg font-medium text-gray-900">
                          {{ item.therapist_name_en || item.therapist_name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                          {{ formatDate(item.date_time) }} at {{ formatTime(item.starts) }}
                        </p>
                        <p class="text-sm text-gray-500">
                          {{ $t('duration') }}: {{ item.period }} {{ $t('minutes') }}
                        </p>
                      </div>
                      <div class="text-right rtl:text-left">
                        <p class="text-lg font-medium text-gray-900">{{ formatPrice(200.00) }}</p>
                        <button
                          @click="removeItem(item.ID)"
                          class="text-sm text-red-600 hover:text-red-800 mt-1"
                        >
                          {{ $t('remove') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('orderSummary') }}</h2>
            
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
              @click="proceedToPayment"
              :disabled="cartStore.loading || cartStore.itemCount === 0"
              class="w-full mt-6 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="cartStore.loading">{{ $t('processing') }}...</span>
              <span v-else>{{ $t('proceedToPayment') }} {{ formatPrice(cartStore.totalPrice) }}</span>
            </button>
            
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
import { onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useSettingsStore } from '../stores/settings'
import { formatPrice } from '../utils/currency'

const { t, locale } = useI18n()
const cartStore = useCartStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

// Get the authenticated user's ID
const userId = computed(() => authStore.user?.id)

const formatDate = (dateTime) => {
  if (!dateTime) return ''
  
  const date = new Date(dateTime)
  const isArabic = locale?.value === 'ar'
  
  if (isArabic) {
    // Arabic month names
    const arabicMonths = [
      'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
      'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ]
    
    // Arabic day names
    const arabicDays = [
      'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
    ]
    
    const dayName = arabicDays[date.getDay()]
    const monthName = arabicMonths[date.getMonth()]
    const day = date.getDate()
    const year = date.getFullYear()
    
    return `${dayName}، ${day} ${monthName} ${year}`
  } else {
    // English formatting
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
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

const removeItem = async (slotId) => {
  if (!userId.value) return
  const result = await cartStore.removeFromCart(slotId, userId.value)
  if (!result.success) {
    console.error('Failed to remove item:', result.message)
  }
}

const proceedToPayment = async () => {
  if (!userId.value) return
  const result = await cartStore.checkout(userId.value)
  if (result.success) {
    // Redirect happens automatically in the store
            // Payment initiated successfully
  } else {
    console.error('Payment failed:', result.message)
  }
}

onMounted(() => {
  if (userId.value) {
    cartStore.loadCart(userId.value)
  }
  
  // Debug translation
          // Translation debug removed
})
</script> 