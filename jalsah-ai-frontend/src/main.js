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

// Load settings and update locale
settingsStore.loadSettings().then(() => {
  // Update i18n locale based on settings
  const savedLocale = localStorage.getItem('locale')
  const defaultLocale = settingsStore.getDefaultLanguage
  
  if (!savedLocale) {
    // Set default language from settings
    i18n.global.locale.value = defaultLocale
    localStorage.setItem('locale', defaultLocale)
    localStorage.setItem('jalsah_locale', defaultLocale)
  }
  
  // Update document direction
  const currentLocale = i18n.global.locale.value
  document.documentElement.dir = currentLocale === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.lang = currentLocale
})

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