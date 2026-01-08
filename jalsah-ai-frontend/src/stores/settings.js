import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useSettingsStore = defineStore('settings', () => {
  // State
  const bilingualEnabled = ref(true)
  const defaultLanguage = ref('ar')
  const siteTitle = ref('جلسة الذكية - دعم الصحة النفسية')
  const siteDescription = ref('دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.')
  const siteIconUrl = ref('')
  const ratingsEnabled = ref(true)
  const diagnosisSearchByName = ref(false)
  const diagnosisResultsLimit = ref(10)
  const showMoreButtonEnabled = ref(true)
  const appointmentChangeTerms = ref('')
  const isLoading = ref(false)
  const isInitialized = ref(false)
  const therapistRegistrationPasswordMode = ref('auto')
  const disableChatCopyPaste = ref(true)
  const userCountryCode = ref(null)
  const userCurrencyCode = ref('EGP') // Store currency code (e.g., 'GBP', 'EUR'), not symbol
  const userCurrency = ref('ج.م') // Keep for backward compatibility, but use currency_symbol when available

  // Getters
  const isBilingualEnabled = computed(() => bilingualEnabled.value)
  const getDefaultLanguage = computed(() => defaultLanguage.value)
  const getSiteTitle = computed(() => siteTitle.value)
  const getSiteDescription = computed(() => siteDescription.value)
  const isRatingsEnabled = computed(() => ratingsEnabled.value)
  const isDiagnosisSearchByName = computed(() => diagnosisSearchByName.value)
  const getDiagnosisResultsLimit = computed(() => diagnosisResultsLimit.value)
  const isShowMoreButtonEnabled = computed(() => showMoreButtonEnabled.value)
  const getAppointmentChangeTerms = computed(() => appointmentChangeTerms.value)
  const getTherapistRegistrationPasswordMode = computed(() => therapistRegistrationPasswordMode.value)
  const getSiteIconUrl = computed(() => siteIconUrl.value)
  const isChatCopyPasteDisabled = computed(() => disableChatCopyPaste.value)
  const getUserCountryCode = computed(() => userCountryCode.value)
  const getUserCurrencyCode = computed(() => userCurrencyCode.value)
  const getUserCurrency = computed(() => userCurrency.value)

  // Helper function to update favicon
  const updateFavicon = (iconUrl) => {
    if (!iconUrl) return
    
    // Remove existing favicon links
    const existingLinks = document.querySelectorAll('link[rel*="icon"]')
    existingLinks.forEach(link => link.remove())
    
    // Create new favicon link
    const link = document.createElement('link')
    link.rel = 'icon'
    link.type = 'image/x-icon'
    link.href = iconUrl
    document.head.appendChild(link)
  }

  // Helper function to get cookie value
  const getCookie = (name) => {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) {
      return parts.pop().split(';').shift()
    }
    return null
  }

  // Helper function to set cookie value (same format as backend: expires in 24 hours, path /)
  const setCookie = (name, value, days = 1) => {
    const expires = new Date()
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000))
    const expiresStr = expires.toUTCString()
    document.cookie = `${name}=${value}; expires=${expiresStr}; path=/`
  }

  // Actions
  const initializeSettings = () => {
    if (isInitialized.value) return
    
    // Load from localStorage first
    const savedSettings = localStorage.getItem('jalsah_settings')
    if (savedSettings) {
      try {
        const settings = JSON.parse(savedSettings)
        bilingualEnabled.value = settings.bilingual_enabled ?? true
        defaultLanguage.value = settings.default_language ?? 'ar'
        siteTitle.value = settings.site_title ?? 'جلسة الذكية - دعم الصحة النفسية'
        siteDescription.value = settings.site_description ?? 'دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.'
        siteIconUrl.value = settings.site_icon_url ?? ''
        if (siteIconUrl.value) {
          updateFavicon(siteIconUrl.value)
        }
        ratingsEnabled.value = settings.ratings_enabled ?? true
        diagnosisSearchByName.value = settings.diagnosis_search_by_name ?? false
        diagnosisResultsLimit.value = settings.diagnosis_results_limit ?? 10
        showMoreButtonEnabled.value = settings.show_more_button_enabled ?? true
        appointmentChangeTerms.value = settings.appointment_change_terms ?? ''
        therapistRegistrationPasswordMode.value = settings.therapist_registration_password_mode ?? 'auto'
        disableChatCopyPaste.value = settings.disable_chat_copy_paste ?? true
        userCountryCode.value = localStorage.getItem('user_country_code') || null
        userCurrencyCode.value = localStorage.getItem('user_currency_code') || getCookie('ced_selected_currency') || 'EGP'
        userCurrency.value = localStorage.getItem('user_currency') || 'ج.م' // Keep for backward compatibility

      } catch (e) {
        console.error('Failed to parse saved settings:', e)
        // Use defaults if parsing fails
        ratingsEnabled.value = true
        diagnosisResultsLimit.value = 10
        showMoreButtonEnabled.value = true
      }
    } else {
      // No saved settings, use defaults
      ratingsEnabled.value = true
      diagnosisResultsLimit.value = 10
      showMoreButtonEnabled.value = true
      disableChatCopyPaste.value = true
    }
    
    // Load user country from localStorage or cookie
    userCountryCode.value = localStorage.getItem('user_country_code') || null
    userCurrencyCode.value = localStorage.getItem('user_currency_code') || getCookie('ced_selected_currency') || 'EGP'
    userCurrency.value = localStorage.getItem('user_currency') || 'ج.م' // Keep for backward compatibility
    
    isInitialized.value = true
    
    // Try to load fresh settings from API in background
    loadSettingsFromAPI()
    
    // Validate country sync before detecting
    validateCountrySync().then((synced) => {
      // If sync was performed, detectUserCountry was already called
      // Otherwise, detect if not set
      if (!synced && !userCountryCode.value) {
        detectUserCountry()
      }
    })
  }

  const loadSettingsFromAPI = async () => {
    try {
      // Try multiple endpoints
      let response = null
      
      // Try custom API endpoint first (same pattern as working therapist requests)
      try {
        response = await api.get('/api/ai/settings')
      } catch (e) {
        // Try REST API as fallback
        try {
          response = await api.get('/wp-json/jalsah-ai/v1/ai-settings')
        } catch (e2) {
          // Try WordPress AJAX as last resort
          try {
            response = await api.get('/wp-admin/admin-ajax.php', {
              params: { action: 'get_ai_settings' }
            })
          } catch (e3) {
            return
          }
        }
      }
      
      // Update settings if we got a valid response
      if (response && response.data && response.data.success) {
        const settings = response.data.data
        
        bilingualEnabled.value = settings.bilingual_enabled ?? true
        defaultLanguage.value = settings.default_language ?? 'ar'
        siteTitle.value = settings.site_title ?? 'جلسة الذكية - دعم الصحة النفسية'
        siteDescription.value = settings.site_description ?? 'دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.'
        siteIconUrl.value = settings.site_icon_url ?? ''
        ratingsEnabled.value = settings.ratings_enabled ?? true
        diagnosisSearchByName.value = settings.diagnosis_search_by_name ?? false
        diagnosisResultsLimit.value = settings.diagnosis_results_limit ?? 10
        showMoreButtonEnabled.value = settings.show_more_button_enabled ?? true
        appointmentChangeTerms.value = settings.appointment_change_terms ?? ''
        therapistRegistrationPasswordMode.value = settings.therapist_registration_password_mode ?? 'auto'
        disableChatCopyPaste.value = settings.disable_chat_copy_paste ?? true
        
        // Update favicon if site icon URL is available
        if (siteIconUrl.value) {
          updateFavicon(siteIconUrl.value)
        }
        
        // Save to localStorage
        localStorage.setItem('jalsah_settings', JSON.stringify(settings))
      }
    } catch (error) {
      console.error('Failed to load settings from API:', error)
      // Use defaults if API fails
      ratingsEnabled.value = true
      diagnosisResultsLimit.value = 10
    }
  }

  const loadSettings = async () => {
    // Initialize immediately with defaults/localStorage
    initializeSettings()
    
    // Try to load from API
    await loadSettingsFromAPI()
  }

  const shouldShowLanguageSwitcher = computed(() => {
    return bilingualEnabled.value
  })

  // Detect user country from IP (backend handles IP detection automatically)
  const detectUserCountry = async () => {
    try {
      // Backend endpoint automatically detects IP from server variables
      // No need to fetch IP from external service (avoids CORS issues)
      const timestamp = Date.now()
      const params = new URLSearchParams({
        t: timestamp.toString()
      })
      
      const response = await api.get(`/wp-json/jalsah-ai/v1/user-country?${params.toString()}`)
      if (response.data && response.data.country_code) {
        const countryCode = response.data.country_code.toUpperCase()
        setUserCountry(countryCode)
      }
    } catch (error) {
      console.error('Failed to detect user country:', error)
      // Fallback to Egypt
      setUserCountry('EG')
    }
  }

  // Helper function to reset all country/currency related cookies and localStorage
  const resetCountryCurrencyData = () => {
    // Clear localStorage
    localStorage.removeItem('user_country_code')
    localStorage.removeItem('user_currency_code')
    localStorage.removeItem('user_currency')
    
    // Clear cookies (set to empty and expire immediately)
    const pastDate = new Date(0).toUTCString()
    document.cookie = `country_code=; expires=${pastDate}; path=/`
    document.cookie = `ced_selected_currency=; expires=${pastDate}; path=/`
    
    // Reset state
    userCountryCode.value = null
    userCurrencyCode.value = 'EGP'
    userCurrency.value = 'ج.م'
  }

  // Validate and sync country code between localStorage and cookie
  const validateCountrySync = async () => {
    const localStorageCountry = localStorage.getItem('user_country_code')
    const cookieCountry = getCookie('country_code')
    
    // Normalize values (handle null/undefined/empty)
    const lsCountry = localStorageCountry ? localStorageCountry.toUpperCase() : null
    const cookieCntry = cookieCountry ? cookieCountry.toUpperCase() : null
    
    // If both exist but don't match, reset and fetch again
    if (lsCountry && cookieCntry && lsCountry !== cookieCntry) {
      console.log('Country code mismatch detected. Resetting and fetching fresh data.')
      console.log(`localStorage: ${lsCountry}, cookie: ${cookieCntry}`)
      
      // Reset all related data
      resetCountryCurrencyData()
      
      // Fetch fresh country data from backend (will set everything fresh)
      await detectUserCountry()
      return true // Indicates a sync was performed
    }
    
    // If localStorage exists but cookie doesn't, sync from localStorage
    if (lsCountry && !cookieCntry) {
      console.log('Cookie missing, syncing from localStorage')
      setUserCountry(lsCountry)
      return true
    }
    
    // If cookie exists but localStorage doesn't, ONLY sync if user is authenticated
    // This prevents syncing from stale cookies before login
    if (cookieCntry && !lsCountry) {
      // Check if user is authenticated (cookies should be fresh if user just logged in)
      const isAuth = localStorage.getItem('jalsah_token') && localStorage.getItem('jalsah_user')
      
      // Only sync from cookie if user is authenticated (fresh cookies from login)
      // Otherwise, wait for login to set fresh cookies
      if (isAuth) {
        console.log('localStorage missing, syncing from cookie (user authenticated)')
        setUserCountry(cookieCntry)
        return true
      } else {
        // Don't sync from potentially stale cookies if user is not authenticated
        // Wait for login to set fresh cookies
        return false
      }
    }
    
    return false // No sync needed, values match or both are null
  }

  // Set user country and currency code (follows main plugin logic)
  const setUserCountry = (countryCode) => {
    userCountryCode.value = countryCode
    localStorage.setItem('user_country_code', countryCode)
    
    // Also set the country_code cookie to keep them in sync
    setCookie('country_code', countryCode, 1)
    
    // Check cookie first (set by main plugin backend)
    const cookieCurrency = getCookie('ced_selected_currency')
    if (cookieCurrency) {
      userCurrencyCode.value = cookieCurrency.toUpperCase()
      localStorage.setItem('user_currency_code', userCurrencyCode.value)
      // Update symbol for backward compatibility
      userCurrency.value = mapCurrencyCodeToSymbol(userCurrencyCode.value)
      localStorage.setItem('user_currency', userCurrency.value)
      return
    }
    
    // Map country code to currency code (same as backend COUNTRY_CURRENCIES)
    const countryCurrencyMap = {
      'EG': 'EGP', 'SA': 'SAR', 'AE': 'AED', 'KW': 'KWD',
      'QA': 'QAR', 'BH': 'BHD', 'OM': 'OMR', 'EU': 'EUR',
      'US': 'USD', 'GB': 'GBP', 'CA': 'CAD', 'AU': 'AUD'
    }
    
    // Europe country codes
    const europeCountries = [
      'AL', 'AD', 'AM', 'AT', 'AZ', 'BY', 'BE', 'BA', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE',
      'FI', 'FR', 'GE', 'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'KZ', 'XK', 'LV', 'LI', 'LT',
      'LU', 'MT', 'MD', 'MC', 'ME', 'NL', 'MK', 'NO', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS',
      'SK', 'SI', 'ES', 'SE', 'CH', 'TR', 'UA', 'GB', 'VA'
    ]
    
    let currencyCode = 'EGP' // Default
    
    if (countryCurrencyMap[countryCode]) {
      currencyCode = countryCurrencyMap[countryCode]
    } else if (europeCountries.includes(countryCode)) {
      currencyCode = 'EUR'
    } else {
      currencyCode = 'USD'
    }
    
    userCurrencyCode.value = currencyCode
    localStorage.setItem('user_currency_code', currencyCode)
    
    // Set currency cookie
    setCookie('ced_selected_currency', currencyCode, 1)
    
    // Map currency code to symbol for backward compatibility
    userCurrency.value = mapCurrencyCodeToSymbol(currencyCode)
    localStorage.setItem('user_currency', userCurrency.value)
  }

  // Map currency code to symbol (same as backend)
  const mapCurrencyCodeToSymbol = (currencyCode) => {
    const currencySymbolMap = {
      'EGP': 'ج.م', 'SAR': 'ر.س', 'AED': 'د.إ', 'KWD': 'د.ك',
      'QAR': 'ر.ق', 'BHD': 'د.ب', 'OMR': 'ر.ع', 'EUR': '€',
      'USD': 'USD', 'GBP': 'GBP', 'CAD': 'CAD', 'AUD': 'AUD'
    }
    return currencySymbolMap[currencyCode.toUpperCase()] || currencyCode
  }

  // Sync country and currency from cookies (used after backend sets cookies)
  const syncFromCookies = () => {
    const cookieCountry = getCookie('country_code')
    const cookieCurrency = getCookie('ced_selected_currency')
    
    if (cookieCountry) {
      // Update country from cookie (backend is source of truth)
      userCountryCode.value = cookieCountry.toUpperCase()
      localStorage.setItem('user_country_code', cookieCountry.toUpperCase())
      
      // Update currency from cookie if available
      if (cookieCurrency) {
        const currencyUpper = cookieCurrency.toUpperCase()
        userCurrencyCode.value = currencyUpper
        localStorage.setItem('user_currency_code', currencyUpper)
        userCurrency.value = mapCurrencyCodeToSymbol(currencyUpper)
        localStorage.setItem('user_currency', userCurrency.value)
      } else {
        // If no currency cookie, map from country
        const countryCurrencyMap = {
          'EG': 'EGP', 'SA': 'SAR', 'AE': 'AED', 'KW': 'KWD',
          'QA': 'QAR', 'BH': 'BHD', 'OM': 'OMR', 'EU': 'EUR',
          'US': 'USD', 'GB': 'GBP', 'CA': 'CAD', 'AU': 'AUD'
        }
        
        const europeCountries = [
          'AL', 'AD', 'AM', 'AT', 'AZ', 'BY', 'BE', 'BA', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE',
          'FI', 'FR', 'GE', 'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'KZ', 'XK', 'LV', 'LI', 'LT',
          'LU', 'MT', 'MD', 'MC', 'ME', 'NL', 'MK', 'NO', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS',
          'SK', 'SI', 'ES', 'SE', 'CH', 'TR', 'UA', 'GB', 'VA'
        ]
        
        let currencyCode = 'EGP'
        if (countryCurrencyMap[cookieCountry.toUpperCase()]) {
          currencyCode = countryCurrencyMap[cookieCountry.toUpperCase()]
        } else if (europeCountries.includes(cookieCountry.toUpperCase())) {
          currencyCode = 'EUR'
        } else {
          currencyCode = 'USD'
        }
        
        userCurrencyCode.value = currencyCode
        localStorage.setItem('user_currency_code', currencyCode)
        setCookie('ced_selected_currency', currencyCode, 1)
        userCurrency.value = mapCurrencyCodeToSymbol(currencyCode)
        localStorage.setItem('user_currency', userCurrency.value)
      }
      
      return true
    }
    
    return false
  }

  const setSettings = (settings) => {
        bilingualEnabled.value = settings.bilingual_enabled ?? true
        defaultLanguage.value = settings.default_language ?? 'ar'
        siteTitle.value = settings.site_title ?? 'جلسة الذكية - دعم الصحة النفسية'
        siteDescription.value = settings.site_description ?? 'دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.'
        siteIconUrl.value = settings.site_icon_url ?? ''
        ratingsEnabled.value = settings.ratings_enabled ?? true
        diagnosisSearchByName.value = settings.diagnosis_search_by_name ?? false
        diagnosisResultsLimit.value = settings.diagnosis_results_limit ?? 10
        showMoreButtonEnabled.value = settings.show_more_button_enabled ?? true
        appointmentChangeTerms.value = settings.appointment_change_terms ?? ''
        therapistRegistrationPasswordMode.value = settings.therapist_registration_password_mode ?? 'auto'
        disableChatCopyPaste.value = settings.disable_chat_copy_paste ?? true
        
        // Update favicon if site icon URL is available
        if (siteIconUrl.value) {
          updateFavicon(siteIconUrl.value)
        }
        
        // Save to localStorage
        localStorage.setItem('jalsah_settings', JSON.stringify(settings))
  }

  return {
    // State
    bilingualEnabled,
    defaultLanguage,
    siteTitle,
    siteDescription,
    siteIconUrl,
    ratingsEnabled,
    diagnosisSearchByName,
    diagnosisResultsLimit,
    showMoreButtonEnabled,
    appointmentChangeTerms,
    isLoading,
    isInitialized,
    therapistRegistrationPasswordMode,
    disableChatCopyPaste,
    userCountryCode,
    userCurrencyCode,
    userCurrency,
    
    // Getters
    isBilingualEnabled,
    getDefaultLanguage,
    getSiteTitle,
    getSiteDescription,
    getSiteIconUrl,
    isRatingsEnabled,
    isDiagnosisSearchByName,
    getDiagnosisResultsLimit,
    isShowMoreButtonEnabled,
    getAppointmentChangeTerms,
    shouldShowLanguageSwitcher,
    getTherapistRegistrationPasswordMode,
    isChatCopyPasteDisabled,
    getUserCountryCode,
    getUserCurrencyCode,
    getUserCurrency,
    
    // Actions
    loadSettings,
    initializeSettings,
    loadSettingsFromAPI,
    setSettings,
    detectUserCountry,
    setUserCountry,
    validateCountrySync,
    resetCountryCurrencyData,
    syncFromCookies,
    mapCurrencyCodeToSymbol,
    getCookie,
    setCookie
  }
}) 