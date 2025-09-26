import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import { useCartStore } from './cart'

// Helper function to get nonce from WordPress
const getNonce = async (action) => {
  try {
    // Try AI API nonce endpoint first (uses configured API base URL)
    const response = await api.get(`/api/ai/nonce?action=${action}`)
    
    if (response.data.success && response.data.data.nonce) {
      console.log('✅ Got nonce from AI API endpoint')
      return response.data.data.nonce
    }
    
    // Fallback to admin-ajax endpoint
    const ajaxResponse = await api.get(`/wp-admin/admin-ajax.php?action=get_ai_nonce&action=${action}`)
    
    if (ajaxResponse.data.success && ajaxResponse.data.data.nonce) {
      console.log('✅ Got nonce from admin-ajax endpoint')
      return ajaxResponse.data.data.nonce
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
        } else if (errorMessage.includes('Please verify your') || errorMessage.includes('verification') || errorMessage.includes('تحقق') || errorMessage.includes('التحقق')) {
          console.log('✅ Auth store detected verification error:', errorMessage)
          // Return verification error for login form to handle
          return { success: false, needsVerification: true, message: errorMessage }
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

  const register = async (userData, otpMethod = 'email') => {
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
        // Store contact method from backend response (not userData.email)
        const contactMethod = response.data.data.contact_method || userData.email
        localStorage.setItem('pending_verification_contact', contactMethod)
        // Store registration timestamp for countdown
        localStorage.setItem('registration_timestamp', Date.now().toString())
        
        // Dynamic success message based on OTP method
        const successMessage = otpMethod === 'whatsapp' 
          ? t('toast.auth.whatsappSentTo', { contact: contactMethod })
          : t('toast.auth.registerSuccess')
        toast.success(successMessage)
        return { requiresVerification: true, contact: contactMethod }
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
    // Clear user data
    user.value = null
    token.value = null
    
    // Clear all localStorage items
    localStorage.removeItem('jalsah_token')
    localStorage.removeItem('jalsah_user')
    localStorage.removeItem('user')
    localStorage.removeItem('token')
    localStorage.removeItem('lastDiagnosisId')
    localStorage.removeItem('locale')
    localStorage.removeItem('jalsah_locale')
    
    // Clear sessionStorage
    sessionStorage.clear()
    
    // Remove authorization header
    delete api.defaults.headers.common['Authorization']
    
    // Clear cart on logout
    const cartStore = useCartStore()
    cartStore.clearCart()
    
    // Show success message
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

  const resendVerification = async (contact) => {
    try {
      // Log contact information for debugging
      console.log('Contact info:', {
        contactType: typeof contact,
        contactLength: contact ? contact.length : 0
      });
       
      // Validate contact parameter
      if (!contact) {
        throw new Error('No contact information provided');
      }
       
      // Get nonce for security
      const nonce = await getNonce('ai_resend_verification_nonce');
      
      // Determine if contact is email or WhatsApp
      const requestData = {
        nonce: nonce,
        locale: locale.value
      };
       
      if (contact.includes('@')) {
        requestData.email = contact;
        console.log('📧 Using email for resend:', contact);
      } else {
        requestData.whatsapp = contact;
        console.log('📱 Using WhatsApp for resend:', contact);
      }
       
      console.log('📤 Sending resend request:', requestData);
       
      const response = await api.post('/api/ai/auth/resend-verification', requestData);
      return true;
    } catch (error) {
      console.error('❌ Resend verification error:', error);
      const message = error.response?.data?.error || t('toast.auth.verificationFailed');
      toast.error(message);
      return false;
    }
  };

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

  // Check if user exists by WhatsApp number
  const checkUserExists = async (whatsappNumber) => {
    try {
      const nonce = await getNonce('ai_check_user_nonce')
      
      const response = await api.post('/api/ai/auth/check-user', {
        whatsapp: whatsappNumber,
        nonce: nonce
      })
      
      return response.data
    } catch (error) {
      console.error('Error checking user existence:', error)
      throw error
    }
  }

  // Forgot password - send reset code to WhatsApp
  const forgotPassword = async (whatsappNumber) => {
    try {
      const nonce = await getNonce('ai_forgot_password_nonce')
      
      const response = await api.post('/api/ai/auth/forgot-password', {
        whatsapp: whatsappNumber,
        nonce: nonce
      })
      
      return response.data
    } catch (error) {
      console.error('Error sending forgot password code:', error)
      throw error
    }
  }

  // Verify forgot password code
  const verifyForgotPassword = async (whatsappNumber, code) => {
    try {
      const nonce = await getNonce('ai_verify_forgot_password_nonce')
      
      const response = await api.post('/api/ai/auth/verify-forgot-password', {
        whatsapp: whatsappNumber,
        code: code,
        nonce: nonce
      })
      
      return response.data
    } catch (error) {
      console.error('Error verifying forgot password code:', error)
      throw error
    }
  }

  // Reset password with new password
  const resetPassword = async (resetToken, newPassword) => {
    try {
      const nonce = await getNonce('ai_reset_password_nonce')
      
      console.log('🔄 Reset password request:', {
        reset_token: resetToken,
        new_password: newPassword,
        nonce: nonce
      })
      
      const response = await api.post('/api/ai/auth/reset-password', {
        reset_token: resetToken,
        new_password: newPassword,
        nonce: nonce
      })
      
      return response.data
    } catch (error) {
      console.error('Error resetting password:', error)
      console.error('Error response:', error.response?.data)
      throw error
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
    verifyEmail,
    resendVerification,
    loadUser,
    checkUserExists,
    forgotPassword,
    verifyForgotPassword,
    resetPassword
  }
}) 