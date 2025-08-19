import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useSettingsStore = defineStore('settings', () => {
  // State
  const bilingualEnabled = ref(true)
  const defaultLanguage = ref('ar')
  const siteTitle = ref('جلسة الذكية - دعم الصحة النفسية')
  const siteDescription = ref('دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.')
  const ratingsEnabled = ref(true)
  const diagnosisSearchByName = ref(false)
  const diagnosisResultsLimit = ref(10)
  const showMoreButtonEnabled = ref(true)
  const isLoading = ref(false)
  const isInitialized = ref(false)
  const therapistRegistrationPasswordMode = ref('auto')

  // Getters
  const isBilingualEnabled = computed(() => bilingualEnabled.value)
  const getDefaultLanguage = computed(() => defaultLanguage.value)
  const getSiteTitle = computed(() => siteTitle.value)
  const getSiteDescription = computed(() => siteDescription.value)
  const isRatingsEnabled = computed(() => ratingsEnabled.value)
  const isDiagnosisSearchByName = computed(() => diagnosisSearchByName.value)
  const getDiagnosisResultsLimit = computed(() => diagnosisResultsLimit.value)
  const isShowMoreButtonEnabled = computed(() => showMoreButtonEnabled.value)
  const getTherapistRegistrationPasswordMode = computed(() => therapistRegistrationPasswordMode.value)

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
        ratingsEnabled.value = settings.ratings_enabled ?? true
        diagnosisSearchByName.value = settings.diagnosis_search_by_name ?? false
        diagnosisResultsLimit.value = settings.diagnosis_results_limit ?? 10
        showMoreButtonEnabled.value = settings.show_more_button_enabled ?? true
        therapistRegistrationPasswordMode.value = settings.therapist_registration_password_mode ?? 'auto'

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
    }
    
    isInitialized.value = true
    
    // Try to load fresh settings from API in background
    loadSettingsFromAPI()
  }

  const loadSettingsFromAPI = async () => {
    try {
      // Try multiple endpoints
      let response = null
      
      // Try REST API first
      try {
        			response = await api.get('/wp-json/jalsah-ai/v1/ai-settings')
      } catch (e) {
        // Try WordPress AJAX
        try {
          response = await api.get('/wp-admin/admin-ajax.php', {
            params: { action: 'get_ai_settings' }
          })
        } catch (e2) {
          return
        }
      }
      
      // Update settings if we got a valid response
      if (response && response.data && response.data.success) {
        const settings = response.data.data
        
        bilingualEnabled.value = settings.bilingual_enabled ?? true
        defaultLanguage.value = settings.default_language ?? 'ar'
        siteTitle.value = settings.site_title ?? 'جلسة الذكية - دعم الصحة النفسية'
        siteDescription.value = settings.site_description ?? 'دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.'
        ratingsEnabled.value = settings.ratings_enabled ?? true
        diagnosisSearchByName.value = settings.diagnosis_search_by_name ?? false
        diagnosisResultsLimit.value = settings.diagnosis_results_limit ?? 10
        showMoreButtonEnabled.value = settings.show_more_button_enabled ?? true
        therapistRegistrationPasswordMode.value = settings.therapist_registration_password_mode ?? 'auto'
        
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
    ratingsEnabled.value = settings.ratings_enabled ?? true
    diagnosisSearchByName.value = settings.diagnosis_search_by_name ?? false
    diagnosisResultsLimit.value = settings.diagnosis_results_limit ?? 10
    showMoreButtonEnabled.value = settings.show_more_button_enabled ?? true
    therapistRegistrationPasswordMode.value = settings.therapist_registration_password_mode ?? 'auto'
    
    // Save to localStorage
    localStorage.setItem('jalsah_settings', JSON.stringify(settings))
  }

  return {
    // State
    bilingualEnabled,
    defaultLanguage,
    siteTitle,
    siteDescription,
    ratingsEnabled,
    diagnosisSearchByName,
    diagnosisResultsLimit,
    showMoreButtonEnabled,
    isLoading,
    isInitialized,
    therapistRegistrationPasswordMode,
    
    // Getters
    isBilingualEnabled,
    getDefaultLanguage,
    getSiteTitle,
    getSiteDescription,
    isRatingsEnabled,
    isDiagnosisSearchByName,
    getDiagnosisResultsLimit,
    isShowMoreButtonEnabled,
    shouldShowLanguageSwitcher,
    getTherapistRegistrationPasswordMode,
    
    // Actions
    loadSettings,
    initializeSettings,
    loadSettingsFromAPI,
    setSettings
  }
}) 