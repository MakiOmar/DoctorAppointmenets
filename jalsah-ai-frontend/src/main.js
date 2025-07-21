import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import router from './router'
import i18n from './i18n'
import App from './App.vue'
import './style.css'
import { useSettingsStore } from './stores/settings'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

// Initialize settings and update i18n locale
const pinia = app._context.provides.pinia
const settingsStore = useSettingsStore(pinia)

// Initialize settings immediately
settingsStore.initializeSettings()

// Update i18n locale based on settings
const savedLocale = localStorage.getItem('locale')
const defaultLocale = settingsStore.getDefaultLanguage

// Always use the default language from settings if no saved locale
if (!savedLocale || savedLocale !== defaultLocale) {
  // Set default language from settings
  i18n.global.locale.value = defaultLocale
  localStorage.setItem('locale', defaultLocale)
  localStorage.setItem('jalsah_locale', defaultLocale)
  console.log('Setting default language to:', defaultLocale)
} else {
  // Use saved locale
  i18n.global.locale.value = savedLocale
  console.log('Using saved locale:', savedLocale)
}

// Update document direction
const currentLocale = i18n.global.locale.value
document.documentElement.dir = currentLocale === 'ar' ? 'rtl' : 'ltr'
document.documentElement.lang = currentLocale
console.log('Document direction set to:', document.documentElement.dir)
console.log('Document language set to:', document.documentElement.lang)

app.use(Toast, {
  position: 'top-right',
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: false,
  closeButton: 'button',
  icon: true,
  rtl: i18n.global.locale.value === 'ar'
})

app.mount('#app') 