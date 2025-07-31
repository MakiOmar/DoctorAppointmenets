import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../services/api'

export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])
  const loading = ref(false)
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
      const response = await api.get('/api/ai/get-user-cart', {
        params: { user_id: userId }
      })
      
      cartItems.value = response.data.cart_items || []
    } catch (err) {
      error.value = 'Failed to load cart'
      console.error('Error loading cart:', err)
    } finally {
      loading.value = false
    }
  }

  const addToCart = async (appointmentData) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/api/ai/add-appointment-to-cart', appointmentData)
      
      if (response.data.success) {
        // Reload cart to get updated data
        await loadCart(appointmentData.user_id)
        return { success: true }
      } else {
        error.value = response.data.error || 'Failed to add to cart'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Failed to add to cart'
      console.error('Error adding to cart:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const removeFromCart = async (slotId, userId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/api/ai/remove-from-cart', {
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
      console.error('Error removing from cart:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  const checkout = async (userId) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.post('/api/ai/book-appointments-from-cart', {
        user_id: userId
      })
      
      if (response.data.success) {
        // Clear cart after successful checkout
        cartItems.value = []
        return { success: true, appointmentIds: response.data.appointment_ids }
      } else {
        error.value = response.data.error || 'Failed to checkout'
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