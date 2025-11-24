import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])
  const loading = ref(false)
  const checkoutLoading = ref(false)
  const error = ref(null)

  // Computed properties
  const totalPrice = computed(() => {
    return cartItems.value.reduce((total, item) => {
      const itemPrice = item.price ? parseFloat(item.price) : 200.00 // Use actual price or fallback
      return total + itemPrice
    }, 0)
  })

  const itemCount = computed(() => cartItems.value.length)

  // Actions
  const loadCart = async (userId) => {
    if (!userId) return
    
    loading.value = true
    error.value = null
    
    try {
      // Use the WordPress REST API endpoint
      const response = await api.get(`/wp-json/jalsah-ai/v1/get-user-cart`, {
        params: { user_id: userId }
      })
      
      // The response format is different for the custom API
      if (response.data.success) {
        cartItems.value = response.data.data || []
      } else {
        cartItems.value = []
      }
    } catch (err) {
      error.value = 'Failed to load cart'
      cartItems.value = []
    } finally {
      loading.value = false
    }
  }

  const addToCart = async (appointmentData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/wp-json/jalsah-ai/v1/add-appointment-to-cart', appointmentData)
      
      if (response.data.success) {
        // Reload cart to get updated data
        await loadCart(appointmentData.user_id)
        return { success: true, message: response.data.message }
      } else if (response.data.requires_confirmation) {
        // Return special response for different therapist confirmation
        return { 
          success: false, 
          requiresConfirmation: true, 
          message: response.data.message 
        }
      } else {
        error.value = response.data.error || 'Failed to add to cart'
        return { success: false, message: error.value, shouldRefresh: response.data.error?.includes('booked by another user') }
      }
    } catch (err) {
      console.error('🛒 Cart API Error:', err)
      const errorMessage = err.response?.data?.error || 'Failed to add to cart'
      error.value = errorMessage
      return { success: false, message: errorMessage, shouldRefresh: errorMessage.includes('booked by another user') }
    } finally {
      loading.value = false
    }
  }

  const addToCartWithConfirmation = async (appointmentData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/wp-json/jalsah-ai/v1/add-appointment-to-cart-with-confirmation', {
        ...appointmentData,
        confirm: 'true'
      })
      
      if (response.data.success) {
        // Reload cart to get updated data
        await loadCart(appointmentData.user_id)
        return { success: true, message: response.data.message }
      } else {
        error.value = response.data.error || 'Failed to add to cart'
        return { success: false, message: error.value, shouldRefresh: response.data.error?.includes('booked by another user') }
      }
    } catch (err) {
      const errorMessage = err.response?.data?.error || 'Failed to add to cart'
      error.value = errorMessage
      return { success: false, message: errorMessage, shouldRefresh: errorMessage.includes('booked by another user') }
    } finally {
      loading.value = false
    }
  }

  const removeFromCart = async (slotId, userId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/wp-json/jalsah-ai/v1/remove-from-cart', {
        slot_id: slotId,
        user_id: userId
      })
      
      if (response.data.success) {
        // Reload cart to get updated data
        await loadCart(userId)
        return { success: true }
      } else {
        error.value = response.data.error || 'Failed to remove from cart'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Failed to remove from cart'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const checkout = async (userId, couponData = null) => {
    checkoutLoading.value = true
    error.value = null
    
    try {
      // Create WooCommerce order from existing cart
      const response = await api.post('/wp-json/jalsah-ai/v1/create-woocommerce-order', {
        user_id: userId,
        cart_items: cartItems.value,
        coupon: couponData
      })
      
      if (response.data.success) {
        // Stop loading and redirect immediately
        checkoutLoading.value = false
        
        // Redirect directly to payment URL
        window.location.href = response.data.auto_login_url
        
        return { success: true, auto_login_url: response.data.auto_login_url }
      } else {
        error.value = response.data.error || 'Failed to create order'
        checkoutLoading.value = false
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Failed to checkout'
      console.error('Error during checkout:', err)
      checkoutLoading.value = false
      return { success: false, message: error.value }
    }
  }

  const clearCart = () => {
    cartItems.value = []
    error.value = null
  }

  return {
    // State
    cartItems,
    loading,
    checkoutLoading,
    error,
    
    // Computed
    totalPrice,
    itemCount,
    
    // Actions
    loadCart,
    addToCart,
    addToCartWithConfirmation,
    removeFromCart,
    checkout,
    clearCart
  }
}) 