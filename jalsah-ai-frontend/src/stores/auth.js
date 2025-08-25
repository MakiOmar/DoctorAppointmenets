import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import { useCartStore } from './cart'

// Helper function to get nonce from WordPress
const getNonce = async (action) => {
  try {
    // Try AI API nonce endpoint first (uses proxy)
    const response = await fetch(`/api/ai/nonce?action=${action}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    
    const data = await response.json()
    if (data.success && data.data.nonce) {

      return data.data.nonce
    }
    
    // Fallback to admin-ajax endpoint
    const ajaxResponse = await fetch(`/wp-admin/admin-ajax.php?action=get_ai_nonce&action=${action}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    
    const ajaxData = await ajaxResponse.json()
    if (ajaxData.success && ajaxData.data.nonce) {

      return ajaxData.data.nonce
    }
    
    throw new Error('Failed to get nonce from server')
  } catch (error) {
    console.warn('Could not get nonce from WordPress, using fallback:', error)
    // Fallback: generate a simple nonce-like string
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15)
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('jalsah_user') || 'null'))
  const token = ref(localStorage.getItem('jalsah_token'))
  const loading = ref(false)
  const toast = useToast()
  const { t, locale } = useI18n()

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const login = async (credentials) => {
    loading.value = true
    try {
      // Get nonce for security
      const nonce = await getNonce('ai_login_nonce')
      
      const requestData = {
        ...credentials,
        nonce: nonce,
        locale: locale.value
      }
      
      const response = await api.post('/api/ai/auth', requestData)
      
      // Check if response has the expected structure
      if (!response.data.success || !response.data.data) {
        console.error('❌ Invalid response format:', response.data)
        throw new Error('Invalid response format from server')
      }
      
      const { token: authToken, user: userData } = response.data.data
      
      if (!authToken || !userData) {
        console.error('❌ Missing token or user data')
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
      
      
      toast.success(t('toast.auth.loginSuccess'))
      return true
    } catch (error) {
      console.error('❌ === LOGIN PROCESS FAILED ===')
      console.error('🚨 Error details:', {
        message: error.message,
        status: error.response?.status,
        statusText: error.response?.statusText,
        url: error.config?.url,
        method: error.config?.method,
        data: error.response?.data
      })
      
      // Handle different types of errors
      if (error.response?.status === 302 || error.response?.status === 301) {
        // Redirect response - this shouldn't happen with API calls
        console.error('🔄 Received redirect response from API:', error.response)
        toast.error(t('toast.general.serverError'))
      } else if (error.response?.data?.error) {
        console.error('📝 Server error message:', error.response.data.error)
        // Check for specific error messages and translate them
        const errorMessage = error.response.data.error
        if (errorMessage.includes('User already exists and is verified')) {
          toast.error(t('toast.auth.userExistsVerified'))
        } else if (errorMessage.includes('Please verify your email address')) {
          toast.error(t('toast.auth.verificationRequired'))
        } else {
          toast.error(errorMessage)
        }
      } else if (error.message) {
        console.error('💬 Error message:', error.message)
        toast.error(error.message)
      } else {
        console.error('❓ Unknown error occurred')
        toast.error(t('toast.auth.loginFailed'))
      }
      
      return false
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    try {
      // Get nonce for security
      const nonce = await getNonce('ai_register_nonce')

      
      const requestData = {
        ...userData,
        nonce: nonce
      }
      
      const response = await api.post('/api/ai/auth/register', requestData)
      
      // Check if verification is required
      if (response.data.data.requires_verification) {
        // Store email for verification page
        localStorage.setItem('pending_verification_email', userData.email)
        // Store registration timestamp for countdown
        localStorage.setItem('registration_timestamp', Date.now().toString())
        
        toast.success(t('toast.auth.registerSuccess'))
        return { requiresVerification: true, email: userData.email }
      }
      
      // If no verification required, proceed with login
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
      
      toast.success(t('toast.auth.registerSuccess'))
      return { requiresVerification: false }
    } catch (error) {
      const message = error.response?.data?.error || t('toast.auth.registerFailed')
      // Check for specific error messages and translate them
      if (message.includes('User already exists and is verified')) {
        toast.error(t('toast.auth.userExistsVerified'))
      } else {
        toast.error(message)
      }
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
    

    
    toast.success(t('toast.auth.logoutSuccess'))
  }

  const verifyEmail = async (verificationData) => {
    loading.value = true
    try {
      // Get nonce for security
      const nonce = await getNonce('ai_verify_nonce')

      
      const requestData = {
        ...verificationData,
        nonce: nonce,
        locale: locale.value
      }
      
      const response = await api.post('/api/ai/auth/verify', requestData)
      const { token: authToken, user: newUser } = response.data.data
      
      token.value = authToken
      user.value = newUser
      localStorage.setItem('jalsah_token', authToken)
      localStorage.setItem('jalsah_user', JSON.stringify(newUser))
      
      // Set token in API headers for future requests
      api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      
      // Load cart after successful verification
      const cartStore = useCartStore()
      cartStore.loadCart(newUser.id)
      
      return true
    } catch (error) {
      const message = error.response?.data?.error || t('toast.auth.verificationFailed')
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const resendVerification = async (email) => {
    try {
      // Get nonce for security
      const nonce = await getNonce('ai_resend_verification_nonce')

      
      const requestData = {
        email: email,
        nonce: nonce,
        locale: locale.value
      }
      
      const response = await api.post('/api/ai/auth/resend-verification', requestData)
      return true
    } catch (error) {
      const message = error.response?.data?.error || t('toast.auth.verificationFailed')
      toast.error(message)
      return false
    }
  }

  const loadUser = async () => {

    
    if (!token.value) {

      return false
    }
    
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
      console.error('❌ Error loading cached user:', error)
      logout()
      return false
    }
  }

  // Initialize auth state
  
  
  if (token.value && user.value) {
    
    loadUser()
  } else {
    
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
    verifyEmail,
    resendVerification,
    loadUser
  }
}) 