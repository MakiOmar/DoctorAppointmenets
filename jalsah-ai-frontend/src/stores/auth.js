import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import { useCartStore } from './cart'
import { useSettingsStore } from './settings'

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
      
      const { token: authToken, user: userData, country_code, currency_code } = response.data.data
      
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
      
      // After login, backend sets cookies (country_code, ced_selected_currency) server-side
      // We need to read the actual cookie values and sync localStorage (backend is source of truth)
      
      // Clear old localStorage values first to ensure clean sync (prevents stale values)
      localStorage.removeItem('user_country_code')
      localStorage.removeItem('user_currency_code')
      localStorage.removeItem('user_currency')
      
      const settingsStore = useSettingsStore()
      
      // Try to sync from cookies immediately (backend sets them in response headers)
      let synced = settingsStore.syncFromCookies()
      
      // If cookies weren't available immediately, try again after a brief moment
      if (!synced) {
        // Use setTimeout as fallback in case cookies take a moment to be available
        setTimeout(() => {
          const retrySynced = settingsStore.syncFromCookies()
          
          if (!retrySynced) {
            // If cookies still don't exist, check response values or detect
            if (country_code && currency_code) {
              // Use response values if cookies aren't set
              if (settingsStore.setCookie) {
                settingsStore.setCookie('country_code', country_code, 1)
                settingsStore.setCookie('ced_selected_currency', currency_code, 1)
                settingsStore.setUserCountry(country_code)
              }
            } else {
              // Last resort: detect country
              settingsStore.detectUserCountry().catch(console.error)
            }
          }
        }, 50)
      }
      
      // Also validate sync to ensure everything is correct
      await settingsStore.validateCountrySync()
      
      // Store the country code at login time for VPN detection
      // Use the synced country code (from cookies/localStorage) as it's the most accurate
      // This ensures if VPN is already enabled, we store the VPN country, not a cached value
      const loginCountryCode = settingsStore.userCountryCode || 
                               localStorage.getItem('user_country_code') || 
                               country_code || 
                               'EG'
      localStorage.setItem('login_country_code', loginCountryCode.toUpperCase())
      
      console.log(`[Login] Stored login_country_code: ${loginCountryCode.toUpperCase()} for VPN detection`)
      
      // Load cart after successful login
      const cartStore = useCartStore()
      cartStore.loadCart(userData.id)
      
      // Setup periodic validation after successful login
      const { setupPeriodicValidation } = await import('@/services/auth-interceptor')
      setupPeriodicValidation(api, 1) // 1 minute
      
      // Setup VPN detection (check country changes)
      setupVPNDetection()
      
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
      
      // Detect and store country code and currency after registration
      const settingsStore = useSettingsStore()
      await settingsStore.detectUserCountry()
      
      // Setup periodic validation after successful registration
      const { setupPeriodicValidation } = await import('@/services/auth-interceptor')
      setupPeriodicValidation(api, 1) // 1 minute
      
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

  // VPN detection: Check if country has changed after login
  let vpnCheckInterval = null
  
  const checkForCountryChange = async () => {
    if (!isAuthenticated.value) {
      // Stop checking if user is not authenticated
      if (vpnCheckInterval) {
        clearInterval(vpnCheckInterval)
        vpnCheckInterval = null
      }
      return
    }
    
    try {
      const loginCountryCode = localStorage.getItem('login_country_code')
      if (!loginCountryCode) {
        // No stored login country, skip check
        return
      }
      
      const settingsStore = useSettingsStore()
      
      // Get current country from backend (fresh detection)
      const timestamp = Date.now()
      const params = new URLSearchParams({
        t: timestamp.toString()
      })
      
      const response = await api.get(`/wp-json/jalsah-ai/v1/user-country?${params.toString()}`)
      
      if (response.data && response.data.country_code) {
        const currentCountryCode = response.data.country_code.toUpperCase()
        const storedLoginCountry = loginCountryCode.toUpperCase()
        
        // Get current country from cookies/localStorage (what user is actually using)
        const currentStoredCountry = (settingsStore.userCountryCode || 
                                      localStorage.getItem('user_country_code') || 
                                      '').toUpperCase()
        
        console.log(`[VPN Detection] Current detected: ${currentCountryCode}, Stored in cookies/localStorage: ${currentStoredCountry}, Login country: ${storedLoginCountry}`)
        
        // If current detected country matches what's stored in cookies/localStorage,
        // this means the country is stable (VPN was already enabled at login or no VPN)
        // In this case, update login_country_code to match, but don't trigger VPN detection
        if (currentCountryCode === currentStoredCountry && currentStoredCountry) {
          // Country is stable - matches what's stored in cookies/localStorage
          // This means VPN was already enabled when user logged in, or no VPN is being used
          if (currentStoredCountry !== storedLoginCountry) {
            console.log(`[VPN Detection] Updating login_country_code from ${storedLoginCountry} to ${currentStoredCountry} (country is stable - VPN was already enabled at login)`)
            localStorage.setItem('login_country_code', currentStoredCountry)
          }
          
          // Sync to ensure everything is in sync
          if (settingsStore.userCountryCode !== currentCountryCode) {
            settingsStore.setUserCountry(currentCountryCode)
          }
          await settingsStore.validateCountrySync()
          return // No VPN detected, country is stable
        }
        
        // If current country is different from login country AND different from stored country,
        // this means VPN was enabled AFTER login (VPN detected - logout required)
        // Only trigger VPN detection if:
        // 1. Current country != login country (country changed)
        // 2. Current country != stored country (stored values haven't been updated yet)
        // This means the country just changed (VPN was just enabled)
        if (currentCountryCode !== storedLoginCountry && 
            currentCountryCode !== currentStoredCountry &&
            currentStoredCountry) { // Only if we have a stored country to compare
          console.warn(`[VPN Detection] Country changed from ${storedLoginCountry} to ${currentCountryCode}. Logging out user.`)
          
          // Stop checking immediately
          if (vpnCheckInterval) {
            clearInterval(vpnCheckInterval)
            vpnCheckInterval = null
          }
          
          // Show warning message
          toast.warning(t('toast.auth.vpnDetected') || 'VPN detected. Please log in again for security.')
          
          // Clear login country code
          localStorage.removeItem('login_country_code')
          
          // Clear user data immediately
          user.value = null
          token.value = null
          localStorage.removeItem('jalsah_token')
          localStorage.removeItem('jalsah_user')
          localStorage.removeItem('user')
          localStorage.removeItem('token')
          localStorage.removeItem('lastDiagnosisId')
          localStorage.removeItem('locale')
          localStorage.removeItem('jalsah_locale')
          
          // Clear country and currency data from localStorage and cookies
          // This ensures fresh detection on next login
          try {
            const settingsStore = useSettingsStore()
            if (settingsStore && typeof settingsStore.resetCountryCurrencyData === 'function') {
              settingsStore.resetCountryCurrencyData()
            } else {
              // Fallback: manually clear if resetCountryCurrencyData is not available
              localStorage.removeItem('user_country_code')
              localStorage.removeItem('user_currency_code')
              localStorage.removeItem('user_currency')
              const pastDate = new Date(0).toUTCString()
              const isSecure = typeof window !== 'undefined' && window.location.protocol === 'https:'
              if (isSecure) {
                document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
                document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
              } else {
                document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
                document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
              }
            }
          } catch (error) {
            // Fallback: manually clear if settings store is not available
            console.warn('Could not access settings store, manually clearing country/currency data:', error)
            localStorage.removeItem('user_country_code')
            localStorage.removeItem('user_currency_code')
            localStorage.removeItem('user_currency')
            const pastDate = new Date(0).toUTCString()
            const isSecure = typeof window !== 'undefined' && window.location.protocol === 'https:'
            if (isSecure) {
              document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
              document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
            } else {
              document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
              document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
            }
          }
          
          sessionStorage.clear()
          
          // Remove authorization header
          delete api.defaults.headers.common['Authorization']
          
          // Clear cart
          const cartStore = useCartStore()
          cartStore.clearCart()
          
          // Force redirect to login page after a short delay to show the message
          setTimeout(() => {
            if (typeof window !== 'undefined') {
              window.location.href = '/login'
            }
          }, 2000) // 2 seconds to show the warning message
          
          return
        }
        
        // Country matches login country - no VPN detected
        // But sync cookies and localStorage to ensure they're in sync
        // Update current country in settings store and sync
        if (settingsStore.userCountryCode !== currentCountryCode) {
          settingsStore.setUserCountry(currentCountryCode)
        }
        
        // Validate sync to ensure cookies and localStorage match
        await settingsStore.validateCountrySync()
        
        // If current country matches stored country but login country is different,
        // update login country (this handles the case where VPN was enabled before login but login_country wasn't set correctly)
        // Note: currentStoredCountry is already declared above, so we reuse it
        if (currentCountryCode === currentStoredCountry && 
            currentStoredCountry && 
            currentStoredCountry !== storedLoginCountry) {
          console.log(`[VPN Detection] Updating login_country_code from ${storedLoginCountry} to ${currentStoredCountry} (country is stable)`)
          localStorage.setItem('login_country_code', currentStoredCountry)
        }
      }
    } catch (error) {
      // Silently fail - don't interrupt user experience if country check fails
      console.error('Error checking country for VPN detection:', error)
    }
  }
  
  // Setup VPN detection (check every 2 minutes)
  const setupVPNDetection = () => {
    // Clear any existing interval
    if (vpnCheckInterval) {
      clearInterval(vpnCheckInterval)
    }
    
    // Check immediately
    checkForCountryChange()
    
    // Then check every 2 minutes
    vpnCheckInterval = setInterval(() => {
      checkForCountryChange()
    }, 2 * 60 * 1000) // 2 minutes
  }
  
  // Stop VPN detection
  const stopVPNDetection = () => {
    if (vpnCheckInterval) {
      clearInterval(vpnCheckInterval)
      vpnCheckInterval = null
    }
  }

  const logout = (redirectToLogin = true) => {
    // Stop VPN detection
    stopVPNDetection()
    
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
    localStorage.removeItem('login_country_code') // Clear login country code
    
    // Clear country and currency data from localStorage
    // This ensures fresh detection on next login
    localStorage.removeItem('user_country_code')
    localStorage.removeItem('user_currency_code')
    localStorage.removeItem('user_currency')
    
    // Clear country and currency cookies and reset settings store
    // This ensures fresh detection on next login
    try {
      const settingsStore = useSettingsStore()
      if (settingsStore && typeof settingsStore.resetCountryCurrencyData === 'function') {
        settingsStore.resetCountryCurrencyData()
      } else {
        // Fallback: manually clear cookies if resetCountryCurrencyData is not available
        const pastDate = new Date(0).toUTCString()
        const isSecure = typeof window !== 'undefined' && window.location.protocol === 'https:'
        if (isSecure) {
          document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
          document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
        } else {
          document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
          document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
        }
      }
    } catch (error) {
      // If settings store is not available, manually clear cookies
      console.warn('Could not access settings store, manually clearing cookies:', error)
      const pastDate = new Date(0).toUTCString()
      const isSecure = typeof window !== 'undefined' && window.location.protocol === 'https:'
      if (isSecure) {
        document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
        document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=None; Secure; Partitioned`
      } else {
        document.cookie = `country_code=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
        document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/; SameSite=Lax; Partitioned`
      }
    }
    
    // Clear sessionStorage
    sessionStorage.clear()
    
    // Clear switch-user context so bar does not show after logout
    localStorage.removeItem('jalsah_admin_token')
    localStorage.removeItem('jalsah_admin_user')
    if (typeof switchUserMode !== 'undefined') {
      switchUserMode.value = false
    }
    
    // Remove authorization header
    delete api.defaults.headers.common['Authorization']
    
    // Clear cart on logout
    const cartStore = useCartStore()
    cartStore.clearCart()
    
    // Show success message (only if not VPN-triggered logout)
    if (redirectToLogin) {
      toast.success(t('toast.auth.logoutSuccess'))
      
      // Redirect to login page after a brief delay to show the message
      setTimeout(() => {
        if (typeof window !== 'undefined') {
          window.location.href = '/login'
        }
      }, 500) // Short delay to show success message
    }
  }

  /**
   * Set session from switch-user: store token and user (e.g. after admin switches to patient).
   * Does not redirect; caller should navigate.
   */
  const setSession = (authToken, userData) => {
    if (!authToken || !userData) return
    token.value = authToken
    user.value = userData
    localStorage.setItem('jalsah_token', authToken)
    localStorage.setItem('jalsah_user', JSON.stringify(userData))
    api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
  }

  /** True when current session is a patient view after admin switched user (revert available). */
  const switchUserMode = ref(
    typeof localStorage !== 'undefined' && !!localStorage.getItem('jalsah_admin_token')
  )

  /**
   * Save current (admin) token and user before switching to patient. Call before setSession(patientToken, patientUser).
   */
  const saveAdminContextBeforeSwitch = () => {
    if (!token.value || !user.value) return
    localStorage.setItem('jalsah_admin_token', token.value)
    localStorage.setItem('jalsah_admin_user', JSON.stringify(user.value))
    switchUserMode.value = true
  }

  /**
   * Revert from patient view back to admin session. Clears switch-user context.
   */
  const revertToAdmin = () => {
    const adminToken = localStorage.getItem('jalsah_admin_token')
    const adminUser = localStorage.getItem('jalsah_admin_user')
    if (!adminToken || !adminUser) {
      switchUserMode.value = false
      return
    }
    try {
      const userData = JSON.parse(adminUser)
      setSession(adminToken, userData)
    } finally {
      localStorage.removeItem('jalsah_admin_token')
      localStorage.removeItem('jalsah_admin_user')
      switchUserMode.value = false
    }
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
      
      // Send country_code so backend can set country_code/ced_selected_currency cookies on auto-login
      try {
        const settingsStore = useSettingsStore()
        requestData.country_code = settingsStore.userCountryCode || localStorage.getItem('user_country_code') || 'EG'
      } catch (e) {
        requestData.country_code = localStorage.getItem('user_country_code') || 'EG'
      }
      
      const response = await api.post('/api/ai/auth/verify', requestData)
      const { token: authToken, user: newUser, country_code, currency_code } = response.data.data
      
      token.value = authToken
      user.value = newUser
      localStorage.setItem('jalsah_token', authToken)
      localStorage.setItem('jalsah_user', JSON.stringify(newUser))
      
      // Set token in API headers for future requests
      api.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      
      // After verification, backend sets cookies (country_code, ced_selected_currency) server-side
      // We need to read the actual cookie values and sync localStorage (backend is source of truth)
      const settingsStore = useSettingsStore()
      
      // Try to sync from cookies immediately (backend sets them in response headers)
      const synced = settingsStore.syncFromCookies()
      
      // If cookies weren't available immediately, try again after a brief moment
      if (!synced) {
        // Use setTimeout as fallback in case cookies take a moment to be available
        setTimeout(() => {
          const retrySynced = settingsStore.syncFromCookies()
          
          if (!retrySynced) {
            // If cookies still don't exist, check response values or detect
            if (country_code && currency_code) {
              // Use response values if cookies aren't set
              if (settingsStore.setCookie) {
                settingsStore.setCookie('country_code', country_code, 1)
                settingsStore.setCookie('ced_selected_currency', currency_code, 1)
                settingsStore.setUserCountry(country_code)
              }
            } else {
              // Last resort: detect country
              settingsStore.detectUserCountry().catch(console.error)
            }
          }
        }, 50)
      }
      
      // Also validate sync to ensure everything is correct
      await settingsStore.validateCountrySync()
      
      // Store the country code at verification time for VPN detection
      const loginCountryCode = country_code || settingsStore.userCountryCode || localStorage.getItem('user_country_code') || 'EG'
      localStorage.setItem('login_country_code', loginCountryCode.toUpperCase())
      
      // Setup VPN detection (check country changes)
      setupVPNDetection()
      
      // Load cart after successful verification
      const cartStore = useCartStore()
      cartStore.loadCart(newUser.id)
      
      // Setup periodic validation after successful verification
      const { setupPeriodicValidation } = await import('@/services/auth-interceptor')
      setupPeriodicValidation(api, 1) // 1 minute
      
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
        
        // Check and set country/currency if not already set
        const settingsStore = useSettingsStore()
        if (!settingsStore.userCountryCode) {
          await settingsStore.detectUserCountry()
        }
        
        // Setup VPN detection if user is already logged in
        // Only if login_country_code exists (user logged in after this feature was added)
        const loginCountryCode = localStorage.getItem('login_country_code')
        if (loginCountryCode) {
          setupVPNDetection()
        } else {
          // If no login country code stored, store current country and start detection
          const currentCountry = settingsStore.userCountryCode || localStorage.getItem('user_country_code') || 'EG'
          localStorage.setItem('login_country_code', currentCountry.toUpperCase())
          setupVPNDetection()
        }
        
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
    switchUserMode,
    login,
    register,
    logout,
    setSession,
    saveAdminContextBeforeSwitch,
    revertToAdmin,
    verifyEmail,
    resendVerification,
    loadUser,
    checkForCountryChange,
    setupVPNDetection,
    stopVPNDetection,
    checkUserExists,
    forgotPassword,
    verifyForgotPassword,
    resetPassword
  }
}) 