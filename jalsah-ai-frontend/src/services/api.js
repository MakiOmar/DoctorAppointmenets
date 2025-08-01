import axios from 'axios'
import { useToast } from 'vue-toastification'

// Determine the base URL based on environment
const getBaseURL = () => {
  if (import.meta.env.VITE_API_BASE_URL) {
    return import.meta.env.VITE_API_BASE_URL
  }
  
  // In development, use relative paths for proxy
  if (import.meta.env.DEV) {
    return ''
  }
  
  // In production, use the current domain
  return window.location.origin
}

// Debug logging for API configuration
console.log('API Configuration:', {
  VITE_API_BASE_URL: import.meta.env.VITE_API_BASE_URL,
  VITE_API_TARGET: import.meta.env.VITE_API_TARGET,
  DEV: import.meta.env.DEV,
  baseURL: getBaseURL()
})

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
    
    // Log the error for debugging
    console.error('API Error:', {
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