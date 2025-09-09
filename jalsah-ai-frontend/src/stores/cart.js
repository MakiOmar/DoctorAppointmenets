import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])
  const loading = ref(false)
  const redirecting = ref(false)
  const error = ref(null)

  // Computed properties
  const totalPrice = computed(() => {
    return cartItems.value.reduce((total, item) => total + 200.00, 0) // Default price
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
      } else {
        error.value = response.data.error || 'Failed to add to cart'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Failed to add to cart'
      return { success: false, message: error.value }
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

  const checkout = async (userId) => {
    loading.value = true
    redirecting.value = false
    error.value = null
    
    try {
      // Create WooCommerce order from existing cart
      const response = await api.post('/wp-json/jalsah-ai/v1/create-woocommerce-order', {
        user_id: userId,
        cart_items: cartItems.value
      })
      
      if (response.data.success) {
        // Set redirecting state to true before redirecting
        loading.value = false
        redirecting.value = true
        
        // Small delay to show redirect message
        setTimeout(() => {
          // Redirect to auto-login URL for main website
          window.location.href = response.data.auto_login_url
        }, 1500)
        
        return { success: true, auto_login_url: response.data.auto_login_url }
      } else {
        error.value = response.data.error || 'Failed to create order'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Failed to checkout'
      console.error('Error during checkout:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
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
    redirecting,
    error,
    
    // Computed
    totalPrice,
    itemCount,
    
    // Actions
    loadCart,
    addToCart,
    removeFromCart,
    checkout,
    clearCart
  }
}) 