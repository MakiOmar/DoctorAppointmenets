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
    
    isInitialized.value = true
    
    // Try to load fresh settings from API in background
    loadSettingsFromAPI()
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
    
    // Actions
    loadSettings,
    initializeSettings,
    loadSettingsFromAPI,
    setSettings
  }
}) 