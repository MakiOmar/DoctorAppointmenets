import axios from 'axios'
import { useToast } from 'vue-toastification'

// Retry configuration
const MAX_RETRIES = 3
const RETRY_DELAY = 1000 // 1 second base delay
const RETRY_STATUS_CODES = [408, 429, 500, 502, 503, 504] // Status codes to retry

// Helper function for exponential backoff delay
const getRetryDelay = (retryCount) => {
  return RETRY_DELAY * Math.pow(2, retryCount) // 1s, 2s, 4s
}

// Sleep helper
const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms))

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
  timeout: 30000, // Increased to 30 seconds
  // Ensure cookies (country_code, ced_selected_currency) are sent/received
  withCredentials: true,
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

// Response interceptor with retry logic and error handling
api.interceptors.response.use(
  (response) => {
    // Reset retry count on successful response
    if (response.config.__retryCount) {
      console.log(`✅ Request succeeded after ${response.config.__retryCount} retries`)
    }
    return response
  },
  async (error) => {
    const config = error.config
    const toast = useToast()
    
    // Initialize retry count if not exists
    if (!config.__retryCount) {
      config.__retryCount = 0
    }
    
    // Determine if we should retry this request
    const shouldRetry = 
      config.__retryCount < MAX_RETRIES &&
      (
        // Retry on network errors (no response)
        !error.response ||
        // Retry on specific status codes
        RETRY_STATUS_CODES.includes(error.response?.status) ||
        // Retry on timeout
        error.code === 'ECONNABORTED'
      )
    
    // Attempt retry if conditions are met
    if (shouldRetry) {
      config.__retryCount += 1
      const retryDelay = getRetryDelay(config.__retryCount - 1)
      
      console.log(`🔄 Retrying request (attempt ${config.__retryCount}/${MAX_RETRIES}):`, {
        url: config.url,
        method: config.method,
        delay: `${retryDelay}ms`,
        reason: error.response?.status || error.code || 'network error'
      })
      
      // Wait before retrying (exponential backoff)
      await sleep(retryDelay)
      
      // Retry the request
      return api(config)
    }
    
    // If we've exhausted retries or shouldn't retry, handle the error
    console.error('❌ === API RESPONSE ERROR ===')
    console.error('🚨 Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      url: config?.url,
      method: config?.method,
      data: error.response?.data,
      retriesAttempted: config.__retryCount,
      errorCode: error.code
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
      const url = config?.url || ''
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
    // Handle other errors (only show toast if we've exhausted retries)
    else if (error.response?.status === 403) {
      toast.error('Access denied')
    } else if (error.response?.status >= 500) {
      // Only show error if we tried multiple times
      if (config.__retryCount >= MAX_RETRIES) {
        toast.error('Server error. Please try again later.')
      }
    } else if (error.code === 'ECONNABORTED') {
      toast.error('Request timed out. Please check your connection.')
    } else if (!error.response) {
      // Network error
      if (config.__retryCount >= MAX_RETRIES) {
        toast.error('Network error. Please check your internet connection.')
      }
    } else if (error.response?.data?.error) {
      toast.error(error.response.data.error)
    } else {
      toast.error('An error occurred. Please try again.')
    }
    
    return Promise.reject(error)
  }
)

export default api