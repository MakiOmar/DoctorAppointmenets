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
                        <p class="text-lg font-medium text-gray-900">{{ formatPrice(item.price || 200.00, $i18n.locale, item.currency_symbol || getCurrencySymbol(item.currency || settingsStore.userCurrencyCode)) }}</p>
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
            
            <!-- Coupon Form -->
            <div v-if="!appliedCoupon" class="mb-4">
              <div class="flex space-x-2 rtl:space-x-reverse">
                <input
                  v-model="couponCode"
                  type="text"
                  :placeholder="$t('cart.enterCouponCode')"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <button
                  @click="applyCoupon"
                  :disabled="couponLoading || !couponCode.trim()"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                  <span v-if="couponLoading">{{ $t('common.loading') }}</span>
                  <span v-else>{{ $t('cart.applyCoupon') }}</span>
                </button>
              </div>
              <p v-if="couponError" class="mt-2 text-sm text-red-600">{{ couponError }}</p>
            </div>

            <!-- Applied Coupon -->
            <div v-if="appliedCoupon" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-green-800">{{ $t('cart.appliedCoupon') }}</p>
                  <p class="text-sm text-green-600">{{ appliedCoupon.code }}</p>
                </div>
                <button
                  @click="removeCoupon"
                  class="text-red-600 hover:text-red-800 text-sm"
                >
                  {{ $t('cart.removeCoupon') }}
                </button>
              </div>
            </div>

            <div class="space-y-4">
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('subtotal') }}</span>
                <span class="text-gray-900">{{ formatPrice(cartStore.totalPrice, $i18n.locale, getCartCurrency()) }}</span>
              </div>
              
              <!-- Coupon Discount -->
              <div v-if="appliedCoupon && appliedCoupon.discount > 0" class="flex justify-between text-green-600">
                <span>{{ $t('cart.discount') }}</span>
                <span>-{{ formatPrice(appliedCoupon.discount, $i18n.locale, getCartCurrency()) }}</span>
                <!-- DEBUG: Show original discount for troubleshooting -->
                <span v-if="false" class="text-xs text-gray-400 ml-2">(Original: {{ appliedCoupon.discountOriginal }})</span>
              </div>
              
              <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between">
                  <span class="text-lg font-medium text-gray-900">{{ $t('total') }}</span>
                  <span class="text-lg font-medium text-gray-900">{{ formatPrice(finalTotal, $i18n.locale, getCartCurrency()) }}</span>
                </div>
              </div>
            </div>

            <button
              @click="proceedToPayment"
              :disabled="cartStore.checkoutLoading || cartStore.itemCount === 0"
              class="w-full mt-6 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="cartStore.checkoutLoading">{{ $t('processing') }}...</span>
              <span v-else>{{ $t('proceedToPayment') }} {{ formatPrice(finalTotal, $i18n.locale, getCartCurrency()) }}</span>
            </button>

            <!-- Add More Bookings Button -->
            <button
              @click="addMoreBookings"
              class="w-full mt-3 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors border border-gray-300"
            >
              {{ $t('cart.addMoreBookings') }}
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
import { onMounted, computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { useSettingsStore } from '../stores/settings'
import { formatPrice, getCurrencySymbol } from '../utils/currency'
import api from '../services/api'

const { t, locale } = useI18n()
const cartStore = useCartStore()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

// Get the authenticated user's ID
const userId = computed(() => authStore.user?.id)

// Coupon state
const couponCode = ref('')
const appliedCoupon = ref(null)
const couponLoading = ref(false)
const couponError = ref('')

// Calculate final total with coupon discount (using original prices for calculations)
// Note: Currency exchange is display-only, all calculations use original EGP prices
const finalTotalOriginal = computed(() => {
  if (appliedCoupon.value && appliedCoupon.value.discountOriginal > 0) {
    return Math.max(0, cartStore.totalOriginalPrice - appliedCoupon.value.discountOriginal)
  }
  return cartStore.totalOriginalPrice
})

// Display version: convert final total for UI display
const finalTotal = computed(() => {
  // For display, we show the converted price
  // The actual calculation uses original prices
  if (appliedCoupon.value && appliedCoupon.value.discount > 0) {
    // Display converted discount
    return Math.max(0, cartStore.totalPrice - appliedCoupon.value.discount)
  }
  return cartStore.totalPrice
})

// Get currency symbol from cart items or settings store
const getCartCurrency = () => {
  // Try to get currency symbol from first cart item
  if (cartStore.cartItems.length > 0) {
    const firstItem = cartStore.cartItems[0]
    if (firstItem.currency_symbol) {
      return firstItem.currency_symbol
    }
    if (firstItem.currency) {
      return getCurrencySymbol(firstItem.currency)
    }
  }
  // Fallback to settings store currency code (map to symbol)
  return getCurrencySymbol(settingsStore.userCurrencyCode)
}

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
    couponError.value = result.message || t('cart.removeError')
    return
  }

  const summary = result.data || {}

  // Reload cart to get fresh data with updated items and totals
  await cartStore.loadCart(userId.value)

  // Handle coupon recalculation from backend
  if (summary?.coupon) {
    if (summary.coupon.removed) {
      appliedCoupon.value = null
      couponError.value = summary.coupon.message || ''
    } else {
      // Backend provides both converted (discount) and original (discount_original) amounts
      const discountDisplay = Number(summary.coupon.discount || 0) // Converted for display
      const discountOriginal = Number(summary.coupon.discount_original || summary.coupon.discount || 0) // Original EGP for calculations
      
      console.log('🔍 COUPON DEBUG - After remove item:', {
        summary: summary,
        discountDisplay: discountDisplay,
        discountOriginal: discountOriginal,
        cartTotal: cartStore.totalPrice,
        cartTotalOriginal: cartStore.totalOriginalPrice
      })
      
      appliedCoupon.value = {
        code: summary.coupon.code,
        discount: discountDisplay, // Converted discount for display
        discountOriginal: discountOriginal, // Original EGP discount for calculations
        finalPriceOriginal: Number(summary.coupon.final_price || 0), // Original EGP final price
        type: summary.coupon.source || 'AI'
      }
      couponError.value = ''
    }
  } else {
    // No coupon in response means it was removed or doesn't exist
    appliedCoupon.value = null
  }
}

const proceedToPayment = async () => {
  if (!userId.value) return
  // Send original discount amount for order creation (currency exchange is display-only)
  const couponPayload = appliedCoupon.value
    ? { 
        code: appliedCoupon.value.code, 
        discount: appliedCoupon.value.discountOriginal || appliedCoupon.value.discount // Use original for calculations
      }
    : null
  const result = await cartStore.checkout(userId.value, couponPayload)
  if (result.success) {
    // Redirect happens automatically in the store
            // Payment initiated successfully
  } else {
    console.error('Payment failed:', result.message)
  }
}

const addMoreBookings = () => {
  // Redirect to homepage to allow user to book more appointments
  window.location.href = '/'
}

// Coupon functions
const applyCoupon = async () => {
  if (!couponCode.value.trim()) return
  
  couponLoading.value = true
  couponError.value = ''
  
  // FRONTEND DEBUG: Log cart state before coupon application
  console.log('🔍 COUPON DEBUG - Before apply:', {
    totalPrice: cartStore.totalPrice,
    totalOriginalPrice: cartStore.totalOriginalPrice,
    cartItems: cartStore.cartItems.map(item => ({
      id: item.ID,
      price: item.price,
      original_price: item.original_price,
      currency: item.currency,
      currency_symbol: item.currency_symbol
    }))
  })
  
  try {
    // Get a nonce for the coupons action (works both logged-in and guests)
    let nonce = ''
    try {
      const nonceRes = await api.get('/wp-json/jalsah-ai/v1/nonce', {
        params: { action: 'snks_coupon_nonce' }
      })
      nonce = nonceRes?.data?.data?.nonce || ''
    } catch (e) {
      // Fallback to admin-ajax nonce endpoint if needed
      const fallback = await api.get(`/wp-admin/admin-ajax.php`, {
        params: { action: 'get_ai_nonce', action2: 'snks_coupon_nonce' }
      })
      nonce = fallback?.data?.data?.nonce || ''
    }

    if (!nonce) {
      throw new Error('Nonce generation failed')
    }

    // CRITICAL: Ensure cart is loaded before applying coupon
    if (cartStore.cartItems.length === 0) {
      console.warn('🔍 COUPON DEBUG - Cart is empty, loading cart first...')
      await cartStore.loadCart(userId.value)
    }
    
    // Build form-encoded body for WordPress admin-ajax (AI-specific apply)
    // IMPORTANT: Send original EGP price for calculations (currency exchange is display-only)
    const originalAmount = cartStore.totalOriginalPrice
    
    // CRITICAL DEBUG: Verify we're sending the correct original price
    console.log('🔍 COUPON DEBUG - Sending to backend:', {
      totalPrice: cartStore.totalPrice,
      totalOriginalPrice: cartStore.totalOriginalPrice,
      apiTotalPrice: cartStore.apiTotalPrice,
      apiTotalOriginal: cartStore.apiTotalOriginal,
      sendingAmount: originalAmount,
      cartItems: cartStore.cartItems.map(item => ({
        id: item.ID,
        price: item.price,
        original_price: item.original_price
      }))
    })
    
    // VALIDATION: Ensure we have valid original price
    if (originalAmount <= 0) {
      console.error('🔍 COUPON DEBUG - ERROR: totalOriginalPrice is 0 or invalid!', {
        totalPrice: cartStore.totalPrice,
        totalOriginalPrice: cartStore.totalOriginalPrice,
        apiTotalOriginal: cartStore.apiTotalOriginal,
        cartItemCount: cartStore.cartItems.length
      })
      couponError.value = 'Unable to calculate cart total. Please refresh the page.'
      couponLoading.value = false
      return
    }
    
    if (originalAmount === cartStore.totalPrice) {
      console.error('🔍 COUPON DEBUG - ERROR: totalOriginalPrice equals totalPrice (no currency conversion detected)!', {
        totalPrice: cartStore.totalPrice,
        totalOriginalPrice: cartStore.totalOriginalPrice,
        apiTotalOriginal: cartStore.apiTotalOriginal,
        warning: 'This suggests original_price is not being set correctly'
      })
      // Don't block, but log the warning - might be same currency
    }
    
    const body = new URLSearchParams()
    body.append('action', 'snks_apply_ai_coupon')
    body.append('code', couponCode.value.trim())
    body.append('amount', String(originalAmount)) // Use original price for calculations
    body.append('security', nonce)

    const response = await api.post('/wp-admin/admin-ajax.php', body, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })

    if (response.data?.success) {
      const payload = response.data?.data || {}
      const finalPriceOriginal = Number(payload.final_price ?? 0) // Original EGP price after discount
      const discountAmountOriginal = Number(
        payload.discount ?? Math.max(0, Number(cartStore.totalOriginalPrice) - finalPriceOriginal)
      )
      
      // FRONTEND DEBUG: Log coupon calculation values
      console.log('🔍 COUPON DEBUG - Frontend:', {
        totalOriginalPrice: cartStore.totalOriginalPrice,
        totalPrice: cartStore.totalPrice,
        discountAmountOriginal: discountAmountOriginal,
        finalPriceOriginal: finalPriceOriginal,
        payload: payload
      })
      
      // Convert discount for display using the same ratio as the total conversion
      // Currency exchange is display-only, so we convert the discount proportionally
      let discountAmountDisplay = discountAmountOriginal
      
      // CRITICAL: Ensure we have valid values for conversion
      if (cartStore.totalOriginalPrice > 0 && cartStore.totalPrice > 0 && cartStore.totalPrice !== cartStore.totalOriginalPrice) {
        // Calculate conversion ratio: converted_total / original_total
        const conversionRatio = cartStore.totalPrice / cartStore.totalOriginalPrice
        
        // Validate conversion ratio is reasonable (between 0.01 and 100)
        if (conversionRatio > 0.01 && conversionRatio < 100) {
          discountAmountDisplay = discountAmountOriginal * conversionRatio
          
          console.log('🔍 COUPON DEBUG - Conversion:', {
            conversionRatio: conversionRatio,
            discountAmountOriginal: discountAmountOriginal,
            discountAmountDisplay: discountAmountDisplay,
            calculation: `${discountAmountOriginal} * ${conversionRatio} = ${discountAmountDisplay}`
          })
        } else {
          console.error('🔍 COUPON DEBUG - Invalid conversion ratio:', conversionRatio, {
            totalPrice: cartStore.totalPrice,
            totalOriginalPrice: cartStore.totalOriginalPrice
          })
          // Fallback: don't convert if ratio is invalid
          discountAmountDisplay = discountAmountOriginal
        }
      } else {
        console.warn('🔍 COUPON DEBUG - No conversion needed:', {
          totalPrice: cartStore.totalPrice,
          totalOriginalPrice: cartStore.totalOriginalPrice,
          reason: cartStore.totalPrice === cartStore.totalOriginalPrice ? 'prices match' : 'invalid values'
        })
        // If prices match, it means no currency conversion is active, so discount should match
        discountAmountDisplay = discountAmountOriginal
      }
      
      appliedCoupon.value = {
        code: couponCode.value.trim(),
        discount: discountAmountDisplay, // Converted discount for display
        discountOriginal: discountAmountOriginal, // Original EGP discount for calculations
        finalPriceOriginal: finalPriceOriginal, // Original EGP final price
        type: payload.coupon_type || 'General'
      }
      
      console.log('🔍 COUPON DEBUG - Applied coupon:', appliedCoupon.value)
      couponCode.value = ''
    } else {
      const payload = response.data?.data || {}
      couponError.value = payload?.message || response.data?.message || t('cart.couponError')
    }
  } catch (error) {
    console.error('Coupon application error:', error)
    couponError.value = error.response?.data?.message || error.message || t('cart.couponError')
  } finally {
    couponLoading.value = false
  }
}

const removeCoupon = () => {
  appliedCoupon.value = null
  couponError.value = ''
}

onMounted(() => {
  if (userId.value) {
    cartStore.loadCart(userId.value)
  }
  
  // Debug translation
          // Translation debug removed
})
</script> 