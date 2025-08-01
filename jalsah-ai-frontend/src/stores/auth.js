import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import { useCartStore } from './cart'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('jalsah_user') || 'null'))
  const token = ref(localStorage.getItem('jalsah_token'))
  const loading = ref(false)
  const toast = useToast()

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const login = async (credentials) => {
    loading.value = true
    try {
      console.log('Attempting login with credentials:', { email: credentials.email })
      
      const response = await api.post('/api/ai/auth', credentials)
      
      console.log('Login response:', response.data)
      
      // Check if response has the expected structure
      if (!response.data.success || !response.data.data) {
        throw new Error('Invalid response format from server')
      }
      
      const { token: authToken, user: userData } = response.data.data
      
      if (!authToken || !userData) {
        throw new Error('Missing token or user data in response')
      }
      
      token.value = authToken
      user.value = userData
      localStorage.setItem('jalsah_token', authToken)
      localStorage.setItem('jalsah_user', JSON.stringify(userData))
      
      // Set token in API headers for future requests
      api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      
      // Load cart after successful login
      const cartStore = useCartStore()
      cartStore.loadCart(userData.id)
      
      toast.success('Login successful!')
      return true
    } catch (error) {
      console.error('Login error:', error)
      
      // Handle different types of errors
      if (error.response?.status === 302 || error.response?.status === 301) {
        // Redirect response - this shouldn't happen with API calls
        console.error('Received redirect response from API:', error.response)
        toast.error('Unexpected redirect response from server')
      } else if (error.response?.data?.error) {
        toast.error(error.response.data.error)
      } else if (error.message) {
        toast.error(error.message)
      } else {
        toast.error('Login failed')
      }
      
      return false
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    try {
      const response = await api.post('/api/ai/auth/register', userData)
      const { token: authToken, user: newUser } = response.data.data
      
      token.value = authToken
      user.value = newUser
      localStorage.setItem('jalsah_token', authToken)
      localStorage.setItem('jalsah_user', JSON.stringify(newUser))
      
      // Set token in API headers for future requests
      api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      
      // Load cart after successful registration
      const cartStore = useCartStore()
      cartStore.loadCart(newUser.id)
      
      toast.success('Registration successful!')
      return true
    } catch (error) {
      const message = error.response?.data?.error || 'Registration failed'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('jalsah_token')
    localStorage.removeItem('jalsah_user')
    delete api.defaults.headers.common['Authorization']
    
    // Clear cart on logout
    const cartStore = useCartStore()
    cartStore.clearCart()
    
    toast.success('Logged out successfully')
  }

  const loadUser = async () => {
    if (!token.value) return false
    
    try {
      // Set token in API headers
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      
      // If we have user data in localStorage, use it
      if (user.value) {
        // Load cart when user is loaded
        const cartStore = useCartStore()
        cartStore.loadCart(user.value.id)
        return true
      }
      
      // You might want to add an endpoint to get current user data
      // For now, we'll just check if the token is valid
      return true
    } catch (error) {
      logout()
      return false
    }
  }

  // Initialize auth state
  if (token.value && user.value) {
    loadUser()
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
    loadUser
  }
}) 