<template>
  <header class="sticky top-0 z-50 bg-primary-500 shadow-sm relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Left: Hamburger Menu -->
        <div class="flex items-center">
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="hamburger-menu-button flex items-center space-x-2 text-white hover:opacity-80 transition-opacity p-2"
            :class="locale === 'ar' ? 'space-x-reverse' : ''"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <span class="text-sm font-medium">{{ $t('nav.menu') }}</span>
          </button>
        </div>

        <!-- Right: Icons and Logo -->
        <div class="flex items-center space-x-4" :class="locale === 'ar' ? 'space-x-reverse' : ''">
          <!-- Language Switcher -->
          <LanguageSwitcher v-if="shouldShowLanguageSwitcher" />

          <!-- Notification Icon with Badge -->
          <SessionMessagesNotification v-if="isAuthenticated" />

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

          <!-- Logo -->
          <router-link to="/" class="flex items-center">
            <img 
              v-if="headerLogoExists"
              src="/home/header-logo.png" 
              :alt="$t('logo.text')" 
              class="h-10 w-auto"
            />
            <span v-else class="text-xl font-bold text-white">{{ $t('logo.text') }}</span>
          </router-link>
        </div>
      </div>

      <!-- Hamburger Menu Dropdown -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 transform -translate-y-2"
        enter-to-class="opacity-100 transform translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 transform translate-y-0"
        leave-to-class="opacity-0 transform -translate-y-2"
      >
        <div
          v-if="mobileMenuOpen"
          class="hamburger-menu absolute top-16 left-0 right-0 bg-white shadow-xl border-t border-gray-200 max-h-[calc(100vh-4rem)] overflow-y-auto z-50"
        >
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <!-- Authenticated User Menu -->
            <template v-if="isAuthenticated">
              <div class="space-y-1">
                <router-link
                  to="/"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.home') }}
                </router-link>
                <router-link
                  to="/therapists"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/therapists' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.therapists') }}
                </router-link>
                <router-link
                  to="/appointments"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/appointments' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.appointments') }}
                </router-link>
                <router-link
                  to="/notifications"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/notifications' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.notifications') }}
                </router-link>
                
                <!-- Smart Diagnosis Link -->
                <router-link
                  v-if="!loadingDiagnosis"
                  :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  @click="mobileMenuOpen = false"
                >
                  {{ hasPreviousDiagnosis ? $t('nav.viewDiagnosisResults') : $t('nav.diagnosis') }}
                </router-link>
                <div
                  v-else
                  class="block px-4 py-3 text-gray-500"
                >
                  <div class="flex items-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500 mr-2"></div>
                    {{ $t('common.loading') }}
                  </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-200 my-2"></div>

                <!-- Profile and Logout -->
                <router-link
                  to="/profile"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/profile' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.profile') }}
                </router-link>
                <button
                  @click="logout"
                  class="block w-full text-left px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-red-500 rounded-md transition-colors"
                >
                  {{ $t('nav.logout') }}
                </button>
              </div>
            </template>

            <!-- Non-Authenticated User Menu -->
            <template v-else>
              <div class="space-y-1">
                <router-link
                  to="/"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.home') }}
                </router-link>
                <router-link
                  to="/therapists"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  :class="{ 'bg-gray-50 text-primary-500': $route.path === '/therapists' }"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.therapists') }}
                </router-link>
                <div class="border-t border-gray-200 my-2"></div>
                <router-link
                  to="/login"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.login') }}
                </router-link>
                <router-link
                  to="/register"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.register') }}
                </router-link>
                <router-link
                  to="/therapist-register"
                  class="block px-4 py-3 text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                  @click="mobileMenuOpen = false"
                >
                  {{ $t('nav.therapistRegister') }}
                </router-link>
              </div>
            </template>
          </div>
        </div>
      </Transition>

    </div>

    <!-- Click Outside to Close -->
    <div
      v-if="mobileMenuOpen"
      @click="mobileMenuOpen = false"
      class="fixed inset-0 bg-black bg-opacity-20 z-40"
      style="top: 64px;"
    ></div>
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
    const headerLogoExists = ref(true)
    const cartIconExists = ref(true)

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

    const handleClickOutside = (event) => {
      // Close menu if clicking outside the header or menu
      const header = document.querySelector('header')
      const menu = event.target.closest('.hamburger-menu, .hamburger-menu-button')
      if (header && !header.contains(event.target) && !menu) {
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
