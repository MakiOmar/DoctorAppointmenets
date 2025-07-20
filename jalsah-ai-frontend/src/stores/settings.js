import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useSettingsStore = defineStore('settings', () => {
  // State
  const bilingualEnabled = ref(true)
  const defaultLanguage = ref('ar')
  const siteTitle = ref('')
  const siteDescription = ref('')
  const isLoading = ref(false)

  // Getters
  const isBilingualEnabled = computed(() => bilingualEnabled.value)
  const getDefaultLanguage = computed(() => defaultLanguage.value)
  const getSiteTitle = computed(() => siteTitle.value)
  const getSiteDescription = computed(() => siteDescription.value)

  // Actions
  const loadSettings = async () => {
    try {
      isLoading.value = true
      const response = await api.get('/wp-admin/admin-ajax.php', {
        params: {
          action: 'get_ai_settings'
        }
      })
      
      if (response.data.success) {
        const settings = response.data.data
        bilingualEnabled.value = settings.bilingual_enabled
        defaultLanguage.value = settings.default_language
        siteTitle.value = settings.site_title
        siteDescription.value = settings.site_description
      }
    } catch (error) {
      console.error('Failed to load settings:', error)
      // Use defaults if API fails
      bilingualEnabled.value = true
      defaultLanguage.value = 'ar'
      siteTitle.value = 'جلسة الذكية - دعم الصحة النفسية'
      siteDescription.value = 'دعم الصحة النفسية والجلسات العلاجية المدعومة بالذكاء الاصطناعي.'
    } finally {
      isLoading.value = false
    }
  }

  const shouldShowLanguageSwitcher = computed(() => {
    return bilingualEnabled.value
  })

  return {
    // State
    bilingualEnabled,
    defaultLanguage,
    siteTitle,
    siteDescription,
    isLoading,
    
    // Getters
    isBilingualEnabled,
    getDefaultLanguage,
    getSiteTitle,
    getSiteDescription,
    shouldShowLanguageSwitcher,
    
    // Actions
    loadSettings
  }
}) 