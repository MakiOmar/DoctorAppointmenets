import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useSettingsStore = defineStore('settings', () => {
  // State
  const bilingualEnabled = ref(true)
  const defaultLanguage = ref('ar')
  const siteTitle = ref('جلسة الذكية - دعم الصحة النفسية')
  const siteDescription = ref('دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.')
  const isLoading = ref(false)
  const isInitialized = ref(false)

  // Getters
  const isBilingualEnabled = computed(() => bilingualEnabled.value)
  const getDefaultLanguage = computed(() => defaultLanguage.value)
  const getSiteTitle = computed(() => siteTitle.value)
  const getSiteDescription = computed(() => siteDescription.value)

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
        console.log('Loaded settings from localStorage:', settings)
      } catch (e) {
        console.error('Failed to parse saved settings:', e)
      }
    }
    
    isInitialized.value = true
    
    // Try to load fresh settings from API in background
    loadSettingsFromAPI()
  }

  const loadSettingsFromAPI = async () => {
    try {
      console.log('Loading settings from API...')
      
      // Try multiple endpoints
      let response = null
      
      // Try REST API first
      try {
        response = await api.get('/wp-json/jalsah-ai/v1/settings')
        console.log('REST API response:', response.data)
      } catch (e) {
        console.log('REST API failed, trying AJAX...')
        
        // Try WordPress AJAX
        try {
          response = await api.get('/wp-admin/admin-ajax.php', {
            params: { action: 'get_ai_settings' }
          })
          console.log('WordPress AJAX response:', response.data)
        } catch (e2) {
          console.log('WordPress AJAX failed:', e2.message)
          return
        }
      }
      
      // Update settings if we got a valid response
      if (response && response.data.success) {
        const settings = response.data.data
        bilingualEnabled.value = settings.bilingual_enabled
        defaultLanguage.value = settings.default_language
        siteTitle.value = settings.site_title
        siteDescription.value = settings.site_description
        
        // Save to localStorage
        localStorage.setItem('jalsah_settings', JSON.stringify(settings))
        console.log('Settings updated from API:', settings)
      }
    } catch (error) {
      console.error('Failed to load settings from API:', error)
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
    
    // Save to localStorage
    localStorage.setItem('jalsah_settings', JSON.stringify(settings))
  }

  return {
    // State
    bilingualEnabled,
    defaultLanguage,
    siteTitle,
    siteDescription,
    isLoading,
    isInitialized,
    
    // Getters
    isBilingualEnabled,
    getDefaultLanguage,
    getSiteTitle,
    getSiteDescription,
    shouldShowLanguageSwitcher,
    
    // Actions
    loadSettings,
    initializeSettings,
    loadSettingsFromAPI,
    setSettings
  }
}) 