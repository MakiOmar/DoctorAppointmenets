<template>
  <div class="relative">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md"
      :class="{ 'text-primary-600': isOpen }"
    >
      <span>{{ currentLanguageName }}</span>
      <svg
        class="w-4 h-4 transition-transform"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
    >
      <button
        v-for="lang in languages"
        :key="lang.code"
        @click="switchLanguage(lang.code)"
        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
        :class="{ 'bg-primary-50 text-primary-700': currentLocale === lang.code }"
      >
        {{ lang.name }}
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

export default {
  name: 'LanguageSwitcher',
  setup() {
    const { locale } = useI18n()
    const isOpen = ref(false)

    const languages = [
      { code: 'ar', name: 'العربية' },
      { code: 'en', name: 'English' }
    ]

    const currentLocale = computed(() => locale.value)

    const currentLanguageName = computed(() => {
      const lang = languages.find(l => l.code === currentLocale.value)
      return lang ? lang.name : 'العربية'
    })

    const switchLanguage = (langCode) => {
      locale.value = langCode
      localStorage.setItem('locale', langCode)
      localStorage.setItem('jalsah_locale', langCode) // Store for API calls
      isOpen.value = false
      
      // Update document direction for RTL support
      document.documentElement.dir = langCode === 'ar' ? 'rtl' : 'ltr'
      document.documentElement.lang = langCode
    }

    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        isOpen.value = false
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
      
      // Set initial document direction
      const savedLocale = localStorage.getItem('locale') || 'ar'
      document.documentElement.dir = savedLocale === 'ar' ? 'rtl' : 'ltr'
      document.documentElement.lang = savedLocale
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      isOpen,
      languages,
      currentLocale,
      currentLanguageName,
      switchLanguage
    }
  }
}
</script> 