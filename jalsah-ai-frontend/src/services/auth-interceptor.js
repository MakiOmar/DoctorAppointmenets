import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

// Global Axios interceptor for handling 401 errors
export function setupAuthInterceptor(axios) {
  // Response interceptor to handle 401 errors
  axios.interceptors.response.use(
    (response) => {
      // Return successful responses as-is
      return response
    },
    (error) => {
      // Handle 401 Unauthorized errors
      if (error.response && error.response.status === 401) {
        console.log('401 Unauthorized - User session expired or invalid')
        
        // Get auth store and router
        const authStore = useAuthStore()
        const router = useRouter()
        
        // Clear user data
        authStore.logout()
        
        // Clear any additional local storage
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        localStorage.removeItem('lastDiagnosisId')
        sessionStorage.clear()
        
        // Redirect to login page
        router.push('/login')
        
        // Show user-friendly message
        if (window.$toast) {
          window.$toast.error('Your session has expired. Please log in again.')
        }
      }
      
      // Return the error for other cases
      return Promise.reject(error)
    }
  )
}

// Function to validate user session
export async function validateUserSession(api) {
  try {
    // Make a lightweight API call to check if user is still valid
    const response = await api.get('/api/ai/profile')
    return response.status === 200
  } catch (error) {
    if (error.response && error.response.status === 401) {
      return false
    }
    // For other errors, assume session is still valid
    return true
  }
}

// Function to setup periodic session validation
export function setupPeriodicValidation(api, intervalMinutes = 5) {
  const authStore = useAuthStore()
  
  // Only run if user is logged in
  if (!authStore.user || !authStore.token) {
    return
  }
  
  const intervalMs = intervalMinutes * 60 * 1000
  
  const validateSession = async () => {
    if (!authStore.user || !authStore.token) {
      return
    }
    
    const isValid = await validateUserSession(api)
    if (!isValid) {
      console.log('Periodic validation failed - logging out user')
      authStore.logout()
      localStorage.clear()
      sessionStorage.clear()
      
      if (window.$toast) {
        window.$toast.error('Your session has expired. Please log in again.')
      }
      
      // Redirect to login
      const router = useRouter()
      router.push('/login')
    }
  }
  
  // Run validation immediately
  validateSession()
  
  // Set up periodic validation
  return setInterval(validateSession, intervalMs)
}
