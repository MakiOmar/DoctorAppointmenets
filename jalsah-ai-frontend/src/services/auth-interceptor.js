// Helper function to handle user logout
function handleUserLogout(message, apiInstance) {
  // Clear user data from localStorage
  localStorage.removeItem('jalsah_token')
  localStorage.removeItem('jalsah_user')
  localStorage.removeItem('user')
  localStorage.removeItem('token')
  localStorage.removeItem('lastDiagnosisId')
  sessionStorage.clear()
  
  // Remove authorization header if apiInstance is provided
  if (apiInstance) {
    delete apiInstance.defaults.headers.common['Authorization']
  }
  
  // Redirect to login page
  window.location.href = '/login'
  
  // Show user-friendly message
  if (window.$toast) {
    window.$toast.error(message)
  }
}

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
        handleUserLogout('Your session has expired. Please log in again.')
      }
      // Handle 404 errors on user-related endpoints (user deleted)
      else if (error.response && error.response.status === 404) {
        const url = error.config?.url || ''
        // Check if it's a user-related endpoint
        if (url.includes('/api/ai/profile') || 
            url.includes('/api/ai/user') || 
            url.includes('/api/ai/auth')) {
          handleUserLogout('Your account is no longer available. Please contact support.')
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
    if (error.response && error.response.status === 404) {
      return false
    }
    // For other errors, assume session is still valid
    return true
  }
}

// Function to setup periodic session validation
export function setupPeriodicValidation(api, intervalMinutes = 5) {
  // Check if user is logged in by checking localStorage
  const token = localStorage.getItem('jalsah_token')
  const user = localStorage.getItem('jalsah_user')
  
  // Only run if user is logged in
  if (!token || !user) {
    return
  }
  
  const intervalMs = intervalMinutes * 60 * 1000
  
  const validateSession = async () => {
    // Check again if user is still logged in
    const currentToken = localStorage.getItem('jalsah_token')
    const currentUser = localStorage.getItem('jalsah_user')
    
    if (!currentToken || !currentUser) {
      return
    }
    
    const isValid = await validateUserSession(api)
    if (!isValid) {
      handleUserLogout('Your session has expired. Please log in again.', api)
    }
  }
  
  // Run validation immediately
  validateSession()
  
  // Set up periodic validation
  const interval = setInterval(validateSession, intervalMs)
  
  return interval
}
