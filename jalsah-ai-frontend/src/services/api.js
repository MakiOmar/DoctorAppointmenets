import axios from 'axios'
import { useToast } from 'vue-toastification'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 10000,
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
    
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('jalsah_token')
      window.location.href = '/login'
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