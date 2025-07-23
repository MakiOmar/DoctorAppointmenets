<template>
  <div>

    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Your Cart</h1>

      <div v-if="cartItems.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
        <p class="text-gray-600 mb-6">Start by browsing our therapists and adding sessions to your cart.</p>
        <button 
          @click="$router.push('/therapists')"
          class="btn-primary"
        >
          Browse Therapists
        </button>
      </div>

      <div v-else class="grid lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
          <div class="card">
            <div class="space-y-6">
              <div 
                v-for="item in cartItems" 
                :key="item.id"
                class="flex items-start space-x-4 p-4 border border-gray-200 rounded-lg"
              >
                <!-- Therapist Image -->
                                  <img 
                    :src="item.therapist?.photo || '/default-therapist.svg'" 
                    :alt="item.therapist?.name"
                    class="w-20 h-20 rounded-lg"
                    :class="item.therapist?.photo ? 'object-cover' : 'object-contain bg-gray-100 p-2'"
                  />

                <!-- Item Details -->
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">{{ item.therapist?.name }}</h3>
                  <p class="text-sm text-gray-600 mb-2">{{ item.sessionType }}-minute session</p>
                  <p class="text-sm text-gray-600">Date: {{ item.date }} at {{ item.time }}</p>
                  
                  <!-- Session Notes -->
                  <div v-if="item.notes" class="mt-2">
                    <p class="text-sm text-gray-600">
                      <span class="font-medium">Notes:</span> {{ item.notes }}
                    </p>
                  </div>
                </div>

                <!-- Price and Actions -->
                <div class="text-right">
                  <div class="text-lg font-semibold text-gray-900 mb-2">
                    {{ formatPrice(item.price, $i18n.locale) }}
                  </div>
                  <button 
                    @click="removeFromCart(item.id)"
                    class="text-red-600 hover:text-red-700 text-sm font-medium"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
          <div class="card sticky top-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
            
            <!-- Items Summary -->
            <div class="space-y-3 mb-6">
              <div class="flex justify-between">
                <span class="text-gray-600">Sessions ({{ cartItems.length }})</span>
                <span class="font-medium">{{ formatPrice(subtotal, $i18n.locale) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Platform Fee</span>
                <span class="font-medium">{{ formatPrice(platformFee, $i18n.locale) }}</span>
              </div>
              <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-3">
                <span>Total</span>
                <span>{{ formatPrice(total, $i18n.locale) }}</span>
              </div>
            </div>

            <!-- Promo Code -->
            <div class="mb-6">
              <label class="form-label">Promo Code (Optional)</label>
              <div class="flex space-x-2">
                <input 
                  v-model="promoCode" 
                  type="text" 
                  class="input-field flex-1"
                  placeholder="Enter code"
                />
                <button 
                  @click="applyPromoCode"
                  :disabled="!promoCode"
                  class="btn-outline px-4"
                >
                  Apply
                </button>
              </div>
              <div v-if="appliedPromo" class="mt-2 text-sm text-green-600">
                Promo code applied: {{ appliedPromo.code }} (-{{ formatPrice(appliedPromo.discount, $i18n.locale) }})
              </div>
            </div>

            <!-- Checkout Button -->
            <button 
              @click="proceedToCheckout"
              :disabled="checkoutLoading"
              class="w-full btn-primary text-lg py-3"
            >
              <span v-if="checkoutLoading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </span>
              <span v-else>Proceed to Checkout</span>
            </button>

            <!-- Continue Shopping -->
            <button 
              @click="$router.push('/therapists')"
              class="w-full btn-outline mt-3"
            >
              Continue Shopping
            </button>

            <!-- Important Notes -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
              <h4 class="font-medium text-blue-900 mb-2">Important Information</h4>
              <ul class="text-sm text-blue-800 space-y-1">
                <li>• All sessions are conducted online</li>
                <li>• 24-hour cancellation policy applies</li>
                <li>• Secure payment processing</li>
                <li>• Sessions are non-refundable</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { formatPrice } from '@/utils/currency'
export default {
  name: 'Cart',
  setup() {
    const router = useRouter()
    const toast = useToast()
    const cartStore = useCartStore()
    const authStore = useAuthStore()
    
    const checkoutLoading = ref(false)
    const promoCode = ref('')
    const appliedPromo = ref(null)

    const cartItems = computed(() => cartStore.items)
    
    const subtotal = computed(() => {
      return cartItems.value.reduce((sum, item) => sum + item.price, 0)
    })
    
    const platformFee = computed(() => {
      return cartItems.value.length * 5 // $5 per session
    })
    
    const total = computed(() => {
      let finalTotal = subtotal.value + platformFee.value
      if (appliedPromo.value) {
        finalTotal -= appliedPromo.value.discount
      }
      return Math.max(0, finalTotal)
    })

    const removeFromCart = (itemId) => {
      cartStore.removeFromCart(itemId)
      toast.success('Item removed from cart')
    }

    const applyPromoCode = async () => {
      if (!promoCode.value) return
      
      try {
        // This would typically call an API to validate the promo code
        // For now, we'll simulate a successful promo code
        if (promoCode.value.toLowerCase() === 'welcome10') {
          appliedPromo.value = {
            code: promoCode.value,
            discount: Math.round(subtotal.value * 0.1) // 10% off
          }
          toast.success('Promo code applied successfully!')
        } else {
          toast.error('Invalid promo code')
        }
      } catch (error) {
        toast.error('Failed to apply promo code')
      }
    }

    const proceedToCheckout = async () => {
      checkoutLoading.value = true
      
      try {
        // This would typically redirect to a payment gateway
        // For now, we'll simulate the checkout process
        await new Promise(resolve => setTimeout(resolve, 2000))
        
        toast.success('Redirecting to payment...')
        
        // Redirect to checkout page
        router.push('/checkout')
        
      } catch (error) {
        toast.error('Failed to proceed to checkout')
      } finally {
        checkoutLoading.value = false
      }
    }

    // Only show cart if authenticated
    if (!authStore.isAuthenticated) {
      router.push('/login')
      return {}
    }

    return {
      cartItems,
      subtotal,
      platformFee,
      total,
      checkoutLoading,
      promoCode,
      appliedPromo,
      removeFromCart,
      applyPromoCode,
      proceedToCheckout,
      formatPrice
    }
  }
}
</script> 