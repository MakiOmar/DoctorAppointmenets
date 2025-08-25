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
      console.log('🔐 === LOGIN PROCESS STARTED ===')
      console.log('📧 Login credentials:', { email: credentials.email })
      console.log('🌐 Current API base URL:', api.defaults.baseURL || '(empty - using proxy)')
      console.log('🎯 Target API endpoint:', '/api/ai/auth')
      console.log('🔗 Full request URL will be:', api.defaults.baseURL + '/api/ai/auth')
      console.log('🌍 Environment:', import.meta.env.MODE)
      console.log('🔧 Development mode:', import.meta.env.DEV)
      console.log('🎯 Proxy target:', import.meta.env.VITE_API_TARGET || 'http://localhost/shrinks')
      console.log('🔗 Actual request will go to:', (import.meta.env.VITE_API_TARGET || 'http://localhost/shrinks') + '/api/ai/auth')
      console.log('📋 API defaults:', {
        baseURL: api.defaults.baseURL,
        timeout: api.defaults.timeout,
        headers: api.defaults.headers
      })
      
      const response = await api.post('/api/ai/auth', credentials)
      
      console.log('✅ Login response received:')
      console.log('📊 Response status:', response.status)
      console.log('📄 Response data:', response.data)
      
      // Check if response has the expected structure
      console.log('🔍 Validating response structure...')
      if (!response.data.success || !response.data.data) {
        console.error('❌ Invalid response format:', response.data)
        throw new Error('Invalid response format from server')
      }
      
      const { token: authToken, user: userData } = response.data.data
      console.log('👤 User data extracted:', { 
        userId: userData?.id, 
        userEmail: userData?.email,
        userRole: userData?.role 
      })
      
      if (!authToken || !userData) {
        console.error('❌ Missing token or user data')
        throw new Error('Missing token or user data in response')
      }
      
      console.log('💾 Storing authentication data...')
      token.value = authToken
      user.value = userData
      localStorage.setItem('jalsah_token', authToken)
      localStorage.setItem('jalsah_user', JSON.stringify(userData))
      
      // Set token in API headers for future requests
      api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      console.log('🔑 Authorization header set for future requests')
      
      // Load cart after successful login
      const cartStore = useCartStore()
      cartStore.loadCart(userData.id)
      
      console.log('✅ === LOGIN PROCESS COMPLETED SUCCESSFULLY ===')
      toast.success('Login successful!')
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
        toast.error('Unexpected redirect response from server')
      } else if (error.response?.data?.error) {
        console.error('📝 Server error message:', error.response.data.error)
        toast.error(error.response.data.error)
      } else if (error.message) {
        console.error('💬 Error message:', error.message)
        toast.error(error.message)
      } else {
        console.error('❓ Unknown error occurred')
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
      
      // Check if verification is required
      if (response.data.data.requires_verification) {
        // Store email for verification page
        localStorage.setItem('pending_verification_email', userData.email)
        
        toast.success('Registration successful! Please check your email for verification code.')
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
      
      toast.success('Registration successful!')
      return { requiresVerification: false }
    } catch (error) {
      const message = error.response?.data?.error || 'Registration failed'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const logout = () => {
    console.log('🚪 === LOGOUT PROCESS ===')
    console.log('🧹 Clearing authentication data...')
    
    user.value = null
    token.value = null
    localStorage.removeItem('jalsah_token')
    localStorage.removeItem('jalsah_user')
    delete api.defaults.headers.common['Authorization']
    
    console.log('✅ Authentication data cleared')
    
    // Clear cart on logout
    const cartStore = useCartStore()
    cartStore.clearCart()
    
    console.log('🛒 Cart cleared')
    console.log('✅ === LOGOUT COMPLETED ===')
    
    toast.success('Logged out successfully')
  }

  const verifyEmail = async (verificationData) => {
    loading.value = true
    try {
      const response = await api.post('/api/ai/auth/verify', verificationData)
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
      const message = error.response?.data?.error || 'Verification failed'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const resendVerification = async (email) => {
    try {
      const response = await api.post('/api/ai/auth/resend-verification', { email })
      return true
    } catch (error) {
      const message = error.response?.data?.error || 'Failed to resend verification code'
      toast.error(message)
      return false
    }
  }

  const loadUser = async () => {
    console.log('🔄 === LOADING USER FROM CACHE ===')
    console.log('🔑 Token exists:', !!token.value)
    console.log('👤 User data exists:', !!user.value)
    
    if (!token.value) {
      console.log('❌ No token found, cannot load user')
      return false
    }
    
    try {
      // Set token in API headers
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      console.log('🔑 Authorization header set from cached token')
      
      // If we have user data in localStorage, use it
      if (user.value) {
        console.log('✅ Using cached user data:', {
          userId: user.value?.id,
          userEmail: user.value?.email,
          userRole: user.value?.role
        })
        
        // Load cart when user is loaded
        const cartStore = useCartStore()
        cartStore.loadCart(user.value.id)
        console.log('🛒 Cart loaded for cached user')
        return true
      }
      
      console.log('⚠️ Token exists but no user data, will validate token later')
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
  console.log('🚀 === AUTH STORE INITIALIZATION ===')
  console.log('🔑 Cached token exists:', !!token.value)
  console.log('👤 Cached user exists:', !!user.value)
  
  if (token.value && user.value) {
    console.log('✅ Loading user from cache on initialization')
    loadUser()
  } else {
    console.log('❌ No cached authentication found, user will need to login')
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