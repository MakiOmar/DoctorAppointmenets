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
  console.log('Setting up auth interceptor...')
  
  // Response interceptor to handle 401 errors
  axios.interceptors.response.use(
    (response) => {
      // Return successful responses as-is
      return response
    },
    (error) => {
      console.log('Auth interceptor triggered:', error.response?.status, error.config?.url)
      
      // Handle 401 Unauthorized errors
      if (error.response && error.response.status === 401) {
        console.log('401 Unauthorized - User session expired or invalid')
        handleUserLogout('Your session has expired. Please log in again.')
      }
      // Handle 404 errors on user-related endpoints (user deleted)
      else if (error.response && error.response.status === 404) {
        const url = error.config?.url || ''
        console.log('404 error on URL:', url)
        // Check if it's a user-related endpoint
        if (url.includes('/api/ai/profile') || 
            url.includes('/api/ai/user') || 
            url.includes('/api/ai/auth')) {
          console.log('404 User Not Found - User may have been deleted')
          handleUserLogout('Your account is no longer available. Please contact support.')
        } else {
          console.log('404 error not on user endpoint, ignoring')
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
    console.log('validateUserSession: Making API call to /api/ai/profile')
    // Make a lightweight API call to check if user is still valid
    const response = await api.get('/api/ai/profile')
    console.log('validateUserSession: API call successful, status:', response.status)
    return response.status === 200
  } catch (error) {
    console.log('validateUserSession: API call failed:', error.response?.status, error.message)
    if (error.response && error.response.status === 401) {
      console.log('validateUserSession: 401 error - session invalid')
      return false
    }
    if (error.response && error.response.status === 404) {
      console.log('validateUserSession: 404 error - user not found')
      return false
    }
    // For other errors, assume session is still valid
    console.log('validateUserSession: Other error, assuming session valid')
    return true
  }
}

// Function to setup periodic session validation
export function setupPeriodicValidation(api, intervalMinutes = 5) {
  console.log('Setting up periodic validation...')
  
  // Check if user is logged in by checking localStorage
  const token = localStorage.getItem('jalsah_token')
  const user = localStorage.getItem('jalsah_user')
  
  console.log('Periodic validation setup - Token:', !!token, 'User:', !!user)
  
  // Only run if user is logged in
  if (!token || !user) {
    console.log('Periodic validation: No user logged in, skipping setup')
    return
  }
  
  const intervalMs = intervalMinutes * 60 * 1000
  console.log(`Periodic validation: Setting up ${intervalMinutes} minute interval (${intervalMs}ms)`)
  
  const validateSession = async () => {
    console.log('Periodic validation: Function called at', new Date().toLocaleTimeString())
    
    // Check again if user is still logged in
    const currentToken = localStorage.getItem('jalsah_token')
    const currentUser = localStorage.getItem('jalsah_user')
    
    if (!currentToken || !currentUser) {
      console.log('Periodic validation: No token or user found, skipping')
      return
    }
    
    console.log('Periodic validation: Checking user session...')
    const isValid = await validateUserSession(api)
    if (!isValid) {
      console.log('Periodic validation failed - logging out user')
      handleUserLogout('Your session has expired. Please log in again.', api)
    } else {
      console.log('Periodic validation: User session is valid')
    }
  }
  
  // Run validation immediately
  console.log('Periodic validation: Running immediate check...')
  validateSession()
  
  // Set up periodic validation
  console.log('Periodic validation: Setting up interval...')
  const interval = setInterval(validateSession, intervalMs)
  
  // Add a test function to manually trigger validation
  window.triggerPeriodicCheck = () => {
    console.log('Manual trigger: Running periodic check...')
    validateSession()
  }
  
  // Add manual test function to window for debugging
  window.testUserDeletion = async () => {
    console.log('Manual test: Checking user session...')
    const isValid = await validateUserSession(api)
    console.log('Manual test result:', isValid)
    if (!isValid) {
      handleUserLogout('Manual test: User session invalid', api)
    }
  }
  
  return interval
}
