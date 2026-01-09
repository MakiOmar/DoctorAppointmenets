import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])
  const loading = ref(false)
  const checkoutLoading = ref(false)
  const error = ref(null)
  // Store totals from API response (more accurate than computing from items)
  const apiTotalPrice = ref(0) // Converted price from API
  const apiTotalOriginal = ref(0) // Original EGP price from API

  // Computed properties
  // Prefer API total_price if available (more accurate), otherwise compute from items
  const totalPrice = computed(() => {
    // Use API total if available and valid
    if (apiTotalPrice.value > 0) {
      return apiTotalPrice.value
    }
    // Fallback: compute from items
    return cartItems.value.reduce((total, item) => {
      const itemPrice = item.price ? parseFloat(item.price) : 200.00 // Use actual price or fallback
      return total + itemPrice
    }, 0)
  })

  // Total in original EGP (for calculations - currency exchange is display only)
  // Prefer API total_original if available (more accurate), otherwise compute from items
  const totalOriginalPrice = computed(() => {
    // Use API total if available and valid
    if (apiTotalOriginal.value > 0) {
      return apiTotalOriginal.value
    }
    // Fallback: compute from items
    const computedTotal = cartItems.value.reduce((total, item) => {
      // CRITICAL: Prefer original_price if available, but log warning if missing
      if (!item.original_price && item.price) {
        console.warn('🔍 CART DEBUG - Item missing original_price, using price as fallback:', {
          itemId: item.ID,
          price: item.price,
          original_price: item.original_price
        })
      }
      const itemPrice = item.original_price ? parseFloat(item.original_price) : 
                       (item.price ? parseFloat(item.price) : 200.00)
      return total + itemPrice
    }, 0)
    
    // Log if we had to compute (API value was 0)
    if (computedTotal > 0 && apiTotalOriginal.value === 0) {
      console.warn('🔍 CART DEBUG - Computed totalOriginalPrice from items (API value was 0):', {
        computed: computedTotal,
        apiTotalOriginal: apiTotalOriginal.value,
        itemCount: cartItems.value.length
      })
    }
    
    return computedTotal
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
        
        // CRITICAL DEBUG: Log raw response to see what we're getting
        console.log('🔍 CART DEBUG - Raw API response:', {
          success: response.data.success,
          total_price: response.data.total_price,
          total_original: response.data.total_original,
          item_count: response.data.item_count,
          data_length: response.data.data?.length,
          first_item: response.data.data?.[0] ? {
            id: response.data.data[0].ID,
            price: response.data.data[0].price,
            original_price: response.data.data[0].original_price
          } : null
        })
        
        // Store totals from API (more accurate than computing)
        // IMPORTANT: total_original is the original EGP price, total_price is converted for display
        apiTotalPrice.value = Number(response.data.total_price || 0)
        apiTotalOriginal.value = Number(response.data.total_original || 0)
        
        // CRITICAL: If total_original is not provided, try to compute from items
        if (apiTotalOriginal.value <= 0 && cartItems.value.length > 0) {
          // Fallback: sum original_price from items
          const computedOriginal = cartItems.value.reduce((sum, item) => {
            const origPrice = item.original_price ? parseFloat(item.original_price) : 0
            if (!item.original_price && item.price) {
              console.warn('🔍 CART DEBUG - Item missing original_price in API response:', {
                itemId: item.ID,
                price: item.price,
                original_price: item.original_price
              })
            }
            return sum + origPrice
          }, 0)
          
          if (computedOriginal > 0) {
            apiTotalOriginal.value = computedOriginal
            console.log('🔍 CART DEBUG - Computed total_original from items:', computedOriginal)
          }
        }
        
        // If still 0, use total_price as last resort (but log warning)
        if (apiTotalOriginal.value <= 0) {
          console.error('🔍 CART DEBUG - ERROR: total_original is 0 after all attempts!', {
            total_price: apiTotalPrice.value,
            total_original: apiTotalOriginal.value,
            response_total_original: response.data.total_original,
            items: cartItems.value.map(item => ({
              id: item.ID,
              price: item.price,
              original_price: item.original_price
            }))
          })
          // Don't use total_price as fallback - this will cause coupon calculation errors
          // Instead, keep it as 0 and let the computed property handle it
        }
        
        console.log('🔍 CART DEBUG - Loaded cart:', {
          itemCount: cartItems.value.length,
          apiTotalPrice: apiTotalPrice.value,
          apiTotalOriginal: apiTotalOriginal.value,
          computedTotalPrice: totalPrice.value,
          computedTotalOriginal: totalOriginalPrice.value,
          responseData: {
            total_price: response.data.total_price,
            total_original: response.data.total_original
          }
        })
      } else {
        cartItems.value = []
        apiTotalPrice.value = 0
        apiTotalOriginal.value = 0
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
      
      if (response.data?.success) {
        // Optimistically remove the item locally
        cartItems.value = cartItems.value.filter(item => item.ID !== slotId)
        
        // Update totals from API response (more accurate than computing)
        if (response.data.cart_total !== undefined) {
          apiTotalPrice.value = Number(response.data.cart_total || 0)
        }
        if (response.data.cart_total_original !== undefined) {
          apiTotalOriginal.value = Number(response.data.cart_total_original || 0)
        }
        
        // If totals are 0, reset them
        if (response.data.item_count === 0) {
          apiTotalPrice.value = 0
          apiTotalOriginal.value = 0
        }
        
        console.log('🔍 CART DEBUG - After remove:', {
          itemCount: response.data.item_count,
          apiTotalPrice: apiTotalPrice.value,
          apiTotalOriginal: apiTotalOriginal.value,
          responseData: response.data
        })
        
        return { success: true, data: response.data }
      } else {
        error.value = response.data?.error || 'Failed to remove from cart'
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
    apiTotalPrice,
    apiTotalOriginal,
    
    // Computed
    totalPrice,
    totalOriginalPrice,
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