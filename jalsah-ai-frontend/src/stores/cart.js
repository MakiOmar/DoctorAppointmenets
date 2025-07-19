import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const loading = ref(false)
  const toast = useToast()

  const itemCount = computed(() => items.value.length)
  const total = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.price?.others || 0), 0)
  })

  const addToCart = async (slotId) => {
    loading.value = true
    try {
      const response = await api.post('/ai/cart/add', { slot_id: slotId })
      await loadCart()
      toast.success('Added to cart successfully!')
      return true
    } catch (error) {
      const message = error.response?.data?.error || 'Failed to add to cart'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const removeFromCart = (slotId) => {
    items.value = items.value.filter(item => item.slot_id !== slotId)
    toast.success('Removed from cart')
  }

  const loadCart = async () => {
    const userId = getCurrentUserId()
    if (!userId) {
      console.log('No user ID found, skipping cart load')
      return
    }
    
    try {
      const response = await api.get(`/ai/cart/${userId}`)
      items.value = response.data.data || []
    } catch (error) {
      if (error.response?.status === 401) {
        console.log('User not authenticated, clearing cart')
        items.value = []
      } else {
        console.error('Failed to load cart:', error)
      }
    }
  }

  const checkout = async () => {
    loading.value = true
    try {
      const response = await api.post('/ai/cart/checkout')
      const { checkout_url, order_id } = response.data.data
      
      // Clear cart after successful checkout
      items.value = []
      
      toast.success('Checkout successful!')
      
      // Redirect to WooCommerce checkout
      window.location.href = checkout_url
      
      return { checkout_url, order_id }
    } catch (error) {
      const message = error.response?.data?.error || 'Checkout failed'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const clearCart = () => {
    items.value = []
  }

  const getCurrentUserId = () => {
    // Get user ID from auth store or localStorage
    const userData = localStorage.getItem('jalsah_user')
    if (userData) {
      try {
        const user = JSON.parse(userData)
        return user.id
      } catch (error) {
        console.error('Failed to parse user data:', error)
        return null
      }
    }
    return null
  }

  // Load cart on store initialization - only if user is logged in
  const initializeCart = () => {
    const userId = getCurrentUserId()
    if (userId) {
      loadCart()
    }
  }
  
  // Initialize cart if user is already logged in
  initializeCart()

  return {
    items,
    loading,
    itemCount,
    total,
    addToCart,
    removeFromCart,
    loadCart,
    checkout,
    clearCart,
    initializeCart
  }
}) 