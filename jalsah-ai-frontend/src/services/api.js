import axios from 'axios'
import { useToast } from 'vue-toastification'

// Determine the base URL based on environment
const getBaseURL = () => {
  // In development, always use empty string for proxy
  if (import.meta.env.DEV) {
    return ''
  }
  
  // In production, use the configured base URL or current domain
  if (import.meta.env.VITE_API_BASE_URL) {
    return import.meta.env.VITE_API_BASE_URL
  }
  
  return window.location.origin
}



const api = axios.create({
  baseURL: getBaseURL(),
  timeout: 15000, // Increased timeout
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor to add auth token and locale
api.interceptors.request.use(
  (config) => {
    
    
    const token = localStorage.getItem('jalsah_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Add locale parameter to all requests
    const locale = localStorage.getItem('jalsah_locale') || 'en'
    if (config.params) {
      config.params.locale = locale
    } else {
      config.params = { locale }
    }

    
    return config
  },
  (error) => {
    console.error('❌ Request interceptor error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    const toast = useToast()
    
    console.error('❌ === API RESPONSE ERROR ===')
    console.error('🚨 Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      url: error.config?.url,
      method: error.config?.method,
      data: error.response?.data
    })
    
    // Handle 401 Unauthorized errors
    if (error.response?.status === 401) {
      // Check if this is a verification error (not a session expired error)
      const errorMessage = error.response?.data?.error || ''
      const isVerificationError = errorMessage.includes('verify') || 
                                 errorMessage.includes('verification') ||
                                 errorMessage.includes('تحقق') ||
                                 errorMessage.includes('التحقق')
      
      if (!isVerificationError) {
        console.log('401 Unauthorized - User session expired or invalid')
        // Clear all user data
        localStorage.removeItem('jalsah_token')
        localStorage.removeItem('jalsah_user')
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        localStorage.removeItem('lastDiagnosisId')
        sessionStorage.clear()
        
        // Remove authorization header
        delete api.defaults.headers.common['Authorization']
        
        if (window.location.pathname !== '/login') {
          window.location.href = '/login'
        }
        toast.error('Your session has expired. Please log in again.')
      }
      // For verification errors, don't show toast - let the auth store handle it
    }
    // Handle 404 errors on user-related endpoints (user deleted)
    else if (error.response?.status === 404) {
      const url = error.config?.url || ''
      console.log('404 error on URL:', url)
      // Check if it's a user-related endpoint
      if (url.includes('/api/ai/profile') || 
          url.includes('/api/ai/user') || 
          url.includes('/api/ai/auth')) {
        console.log('404 User Not Found - User may have been deleted')
        // Clear all user data
        localStorage.removeItem('jalsah_token')
        localStorage.removeItem('jalsah_user')
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        localStorage.removeItem('lastDiagnosisId')
        sessionStorage.clear()
        
        // Remove authorization header
        delete api.defaults.headers.common['Authorization']
        
        // Redirect to login page
        window.location.href = '/login'
        
        // Show user-friendly message
        toast.error('Your account is no longer available. Please contact support.')
        return Promise.reject(error)
      }
    }
    // Handle other errors
    else if (error.response?.status === 403) {
      toast.error('Access denied')
    } else if (error.response?.status >= 500) {
      toast.error('Server error. Please try again later.')
    } else if (error.response?.data?.error) {
      toast.error(error.response.data.error)
    } else {
      toast.error('An error occurred. Please try again.')
    }
    
    return Promise.reject(error)
  }
)

export default api 