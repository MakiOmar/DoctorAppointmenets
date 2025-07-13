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
      const response = await api.post('/api/ai/cart/add', { slot_id: slotId })
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
    try {
      const response = await api.get(`/api/ai/cart/${getCurrentUserId()}`)
      items.value = response.data.data || []
    } catch (error) {
      console.error('Failed to load cart:', error)
    }
  }

  const checkout = async () => {
    loading.value = true
    try {
      const response = await api.post('/api/ai/cart/checkout')
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
    const token = localStorage.getItem('jalsah_token')
    if (token) {
      try {
        const payload = JSON.parse(atob(token.split('.')[1]))
        return payload.user_id
      } catch (error) {
        console.error('Failed to decode token:', error)
        return null
      }
    }
    return null
  }

  // Load cart on store initialization
  if (getCurrentUserId()) {
    loadCart()
  }

  return {
    items,
    loading,
    itemCount,
    total,
    addToCart,
    removeFromCart,
    loadCart,
    checkout,
    clearCart
  }
}) 