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
    console.log('🌐 === API REQUEST INTERCEPTOR ===')
    console.log('📤 Request URL:', config.url)
    console.log('🔧 Request method:', config.method)
    console.log('🏠 API Base URL:', config.baseURL || '(empty - using proxy)')
    console.log('🎯 Full URL:', config.baseURL + config.url)
    console.log('🌍 Environment:', import.meta.env.MODE)
    console.log('🔧 Development mode:', import.meta.env.DEV)
    console.log('🎯 Proxy target:', import.meta.env.VITE_API_TARGET || 'http://localhost/shrinks')
    console.log('🔗 Actual request will go to:', (import.meta.env.VITE_API_TARGET || 'http://localhost/shrinks') + config.url)
    
    const token = localStorage.getItem('jalsah_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
      console.log('🔑 Authorization token added to request')
    } else {
      console.log('⚠️ No authorization token found')
    }
    
    // Add locale parameter to all requests
    const locale = localStorage.getItem('jalsah_locale') || 'en'
    if (config.params) {
      config.params.locale = locale
    } else {
      config.params = { locale }
    }
    console.log('🌍 Locale parameter added:', locale)
    
    console.log('📋 Request headers:', config.headers)
    console.log('📦 Request data:', config.data)
    
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
    console.log('✅ === API RESPONSE INTERCEPTOR ===')
    console.log('📥 Response status:', response.status)
    console.log('📄 Response URL:', response.config.url)
    console.log('🏠 Response base URL:', response.config.baseURL || '(empty - using proxy)')
    console.log('🔗 Full response URL:', response.config.baseURL + response.config.url)
    console.log('📊 Response data:', response.data)
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
    
    if (error.response?.status === 401) {
      // Token expired or invalid - only redirect if not on login page
      localStorage.removeItem('jalsah_token')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
      toast.error('Session expired. Please login again.')
    } else if (error.response?.status === 403) {
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