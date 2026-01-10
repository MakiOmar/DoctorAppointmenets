<template>
  <header class="sticky top-0 z-50 bg-primary-500 shadow-sm relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Left: Logo and Cart (visually left, first in normal order) -->
        <div class="flex items-center gap-4">
          <!-- Logo -->
          <router-link to="/" class="flex items-center">
            <img 
              v-if="headerLogoExists"
              src="/home/header-logo.png" 
              :alt="$t('logo.text')" 
              class="h-10 w-auto"
              @error="headerLogoExists = false"
            />
            <span v-else class="text-xl font-bold text-white">{{ $t('logo.text') }}</span>
          </router-link>

          <!-- Cart Icon with Badge -->
          <router-link 
            to="/cart" 
            class="relative p-2 text-white hover:opacity-80 transition-opacity"
          >
            <img 
              v-if="cartIconExists" 
              src="/home/Layer-26.png" 
              alt="Cart" 
              class="w-6 h-6"
              @error="cartIconExists = false"
            />
            <svg 
              v-else
              xmlns="http://www.w3.org/2000/svg" 
              viewBox="0 0 48 48" 
              class="w-6 h-6 text-white fill-current"
            >
              <title>troley</title>
              <g id="troley-2" data-name="troley">
                <path d="M15,39a3,3,0,1,0,3-3A3,3,0,0,0,15,39Zm4,0a1,1,0,1,1-1-1A1,1,0,0,1,19,39Z"/>
                <path d="M31,39a3,3,0,1,0,3-3A3,3,0,0,0,31,39Zm4,0a1,1,0,1,1-1-1A1,1,0,0,1,35,39Z"/>
                <circle cx="28.55" cy="20.55" r="1.45"/>
                <path d="M23.45,16.9A1.45,1.45,0,1,0,22,15.45,1.45,1.45,0,0,0,23.45,16.9Z"/>
                <path d="M23,22a1,1,0,0,0,.71-.29l6-6a1,1,0,0,0-1.42-1.42l-6,6a1,1,0,0,0,0,1.42A1,1,0,0,0,23,22Z"/>
                <path d="M7,10A1,1,0,0,0,8,9,1,1,0,0,1,9,8h2.26l5.4,17.27,1.38,5A1,1,0,0,0,19,31H32a1,1,0,0,1,0,2H20a1,1,0,0,0,0,2H32a3,3,0,0,0,0-6H19.76l-.83-3H32.47a6.92,6.92,0,0,0,3.58-1,7,7,0,0,0,3-3.46,6.45,6.45,0,0,0,.21-.62L42,11.27a1,1,0,0,0-.16-.87A1,1,0,0,0,41,10H14L13,6.7A1,1,0,0,0,12,6H9A3,3,0,0,0,6,9,1,1,0,0,0,7,10Zm32.67,2L38,18l-.68,2.37A5,5,0,0,1,32.47,24H18.36l-1.87-6-1.88-6Z"/>
              </g>
            </svg>
            <span 
              v-if="cartItemCount > 0"
              class="absolute -top-1 -right-1 bg-secondary-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center min-w-[20px] px-1"
            >
              {{ cartItemCount > 99 ? '99+' : cartItemCount }}
            </span>
          </router-link>
        </div>

        <!-- Right: Language Switcher, Notification, and Hamburger Menu (visually right) -->
        <div class="flex items-center gap-4">
          <!-- Language Switcher -->
          <LanguageSwitcher v-if="shouldShowLanguageSwitcher" />

          <!-- Notification Icon with Badge -->
          <SessionMessagesNotification v-if="isAuthenticated" />

          <!-- Hamburger Menu -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="hamburger-menu-button flex items-center gap-2 text-white hover:opacity-80 transition-opacity p-2"
          >
            <span class="text-sm font-medium">{{ $t('nav.menu') }}</span>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
        </div>
      </div>

    </div>

    <!-- Backdrop Overlay -->
    <Transition
      enter-active-class="transition-opacity ease-linear duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity ease-linear duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileMenuOpen"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
      ></div>
    </Transition>

    <!-- Side Menu -->
    <Transition
      enter-active-class="transition-transform ease-out duration-300"
      enter-from-class="transform -translate-x-full"
      enter-to-class="transform translate-x-0"
      leave-active-class="transition-transform ease-in duration-250"
      leave-from-class="transform translate-x-0"
      leave-to-class="transform -translate-x-full"
    >
      <div
        v-if="mobileMenuOpen"
        class="fixed top-0 left-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl z-50 overflow-y-auto"
        :dir="locale"
      >
        <!-- Side Menu Header with Close Button -->
        <div class="sticky top-0 bg-primary-500 px-6 py-4 flex items-center justify-between border-b border-primary-600 z-10">
          <h2 class="text-xl font-bold text-white">{{ $t('nav.menu') }}</h2>
          <button
            @click="mobileMenuOpen = false"
            class="p-2 text-white hover:bg-primary-600 rounded-md transition-colors"
            :aria-label="$t('common.close')"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Side Menu Content -->
        <div class="px-4 py-6">
          <!-- Authenticated User Menu -->
          <template v-if="isAuthenticated">
            <div class="space-y-1">
              <router-link
                to="/"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>{{ $t('nav.home') }}</span>
              </router-link>
              <router-link
                to="/therapists"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/therapists' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span>{{ $t('nav.therapists') }}</span>
              </router-link>
              <router-link
                to="/appointments"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/appointments' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>{{ $t('nav.appointments') }}</span>
              </router-link>
              <router-link
                to="/notifications"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/notifications' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span>{{ $t('nav.notifications') }}</span>
              </router-link>
              
              <!-- Smart Diagnosis Link -->
              <router-link
                v-if="!loadingDiagnosis"
                :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <span>{{ hasPreviousDiagnosis ? $t('nav.viewDiagnosisResults') : $t('nav.diagnosis') }}</span>
              </router-link>
              <div
                v-else
                class="flex items-center gap-3 px-4 py-3 text-gray-500"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
              >
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500 flex-shrink-0"></div>
                <span>{{ $t('common.loading') }}</span>
              </div>

              <!-- Divider -->
              <div class="border-t border-gray-200 my-4"></div>

              <!-- Profile and Logout -->
              <router-link
                to="/profile"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/profile' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>{{ $t('nav.profile') }}</span>
              </router-link>
              <button
                @click="logout"
                class="flex items-center gap-3 w-full text-right px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-red-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>{{ $t('nav.logout') }}</span>
              </button>
            </div>
          </template>

          <!-- Non-Authenticated User Menu -->
          <template v-else>
            <div class="space-y-1">
              <router-link
                to="/"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>{{ $t('nav.home') }}</span>
              </router-link>
              <router-link
                to="/therapists"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/therapists' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span>{{ $t('nav.therapists') }}</span>
              </router-link>
              <div class="border-t border-gray-200 my-4"></div>
              <router-link
                to="/login"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span>{{ $t('nav.login') }}</span>
              </router-link>
              <router-link
                to="/register"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>{{ $t('nav.register') }}</span>
              </router-link>
              <router-link
                to="/therapist-register"
                class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <span>{{ $t('nav.therapistRegister') }}</span>
              </router-link>
            </div>
          </template>
        </div>
      </div>
    </Transition>
  </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'
import { useI18n } from 'vue-i18n'
import LanguageSwitcher from './LanguageSwitcher.vue'
import SessionMessagesNotification from './SessionMessagesNotification.vue'
import api from '@/services/api'

export default {
  name: 'Header',
  components: {
    LanguageSwitcher,
    SessionMessagesNotification
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    const settingsStore = useSettingsStore()
    const { locale } = useI18n()
    
    const mobileMenuOpen = ref(false)
    const lastDiagnosisId = ref(null)
    const loadingDiagnosis = ref(false)
    const headerLogoExists = ref(false) // Start as false, check in onMounted
    const cartIconExists = ref(false) // Start as false, check in onMounted

    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const cartItemCount = computed(() => cartStore.itemCount)
    const shouldShowLanguageSwitcher = computed(() => settingsStore.shouldShowLanguageSwitcher)
    
    const hasPreviousDiagnosis = computed(() => {
      return lastDiagnosisId.value !== null
    })

    const logout = () => {
      mobileMenuOpen.value = false
      authStore.logout(true)
    }

    // Fetch last diagnosis ID from API
    const fetchLastDiagnosisId = async () => {
      if (!authStore.user || !authStore.token) {
        lastDiagnosisId.value = null
        return
      }
      
      try {
        loadingDiagnosis.value = true
        const response = await api.get('/api/ai/user-diagnosis-results', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
        
        if (response.data.success && response.data.data.current_diagnosis) {
          const diagnosis = response.data.data.current_diagnosis
          lastDiagnosisId.value = diagnosis.diagnosis_id
        } else {
          lastDiagnosisId.value = null
        }
      } catch (error) {
        console.error('Error fetching last diagnosis:', error)
        lastDiagnosisId.value = null
      } finally {
        loadingDiagnosis.value = false
      }
    }

    // Watch for user changes and refetch diagnosis
    watch(() => authStore.user, (newUser) => {
      if (newUser) {
        fetchLastDiagnosisId()
      } else {
        lastDiagnosisId.value = null
      }
    }, { immediate: true })

    // Prevent body scroll when menu is open
    watch(mobileMenuOpen, (isOpen) => {
      if (isOpen) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    })

    const handleClickOutside = (event) => {
      // Close menu if clicking outside the side menu
      // The backdrop handles closing, so this is mainly for ESC key or other edge cases
      const sideMenu = event.target.closest('[class*="fixed top-0 right-0"]')
      const menuButton = event.target.closest('.hamburger-menu-button')
      if (!sideMenu && !menuButton && mobileMenuOpen.value) {
        mobileMenuOpen.value = false
      }
    }

    const handleEscapeKey = (event) => {
      if (event.key === 'Escape' && mobileMenuOpen.value) {
        mobileMenuOpen.value = false
      }
    }

    // Check if images exist (fallback handling)
    const checkImageExists = (src) => {
      return new Promise((resolve) => {
        const img = new Image()
        img.onload = () => resolve(true)
        img.onerror = () => resolve(false)
        img.src = src
      })
    }

    onMounted(async () => {
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
      
      await settingsStore.loadSettings()
      
      // Check if images exist
      headerLogoExists.value = await checkImageExists('/home/header-logo.png')
      cartIconExists.value = await checkImageExists('/home/Layer-26.png')
      
      if (authStore.user) {
        fetchLastDiagnosisId()
      }
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('keydown', handleEscapeKey)
      document.body.style.overflow = '' // Reset overflow on unmount
    })

    return {
      locale,
      mobileMenuOpen,
      isAuthenticated,
      cartItemCount,
      shouldShowLanguageSwitcher,
      logout,
      lastDiagnosisId,
      hasPreviousDiagnosis,
      loadingDiagnosis,
      headerLogoExists,
      cartIconExists
    }
  }
}
</script>
