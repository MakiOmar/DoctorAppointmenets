import { createI18n } from 'vue-i18n'
import ar from './locales/ar.js'
import en from './locales/en.js'

const messages = {
  ar,
  en
}

// Get the default locale from localStorage or use Arabic as default
const getDefaultLocale = () => {
  const savedLocale = localStorage.getItem('locale')
  const locale = savedLocale || 'ar'
  
  // Also store in jalsah_locale for API calls
  localStorage.setItem('jalsah_locale', locale)
  
  return locale
}

export default createI18n({
  legacy: false, // Use Composition API
  locale: getDefaultLocale(),
  fallbackLocale: 'ar',
  messages,
  globalInjection: true,
  silentTranslationWarn: true,
  missingWarn: false,
  fallbackWarn: false
}) 