<template>
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <div class="flex items-center">
          <router-link to="/" class="flex items-center space-x-2">
            <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
              </svg>
            </div>
            <span class="text-xl font-bold text-gray-900">جلسة الذكية</span>
          </router-link>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-8">
          <router-link 
            to="/" 
            class="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors"
            :class="{ 'text-primary-600': $route.path === '/' }"
          >
            {{ $t('nav.home') }}
          </router-link>
          <router-link 
            to="/therapists" 
            class="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors"
            :class="{ 'text-primary-600': $route.path === '/therapists' }"
          >
            {{ $t('nav.therapists') }}
          </router-link>
          <router-link 
            to="/diagnosis" 
            class="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors"
            :class="{ 'text-primary-600': $route.path === '/diagnosis' }"
          >
            {{ $t('nav.diagnosis') }}
          </router-link>
          <router-link 
            to="/appointments" 
            class="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors"
            :class="{ 'text-primary-600': $route.path === '/appointments' }"
          >
            {{ $t('nav.appointments') }}
          </router-link>
        </nav>

        <!-- Right side -->
        <div class="flex items-center space-x-4">
          <!-- Language Switcher -->
          <LanguageSwitcher />

          <!-- Cart Icon -->
          <router-link 
            to="/cart" 
            class="relative p-2 text-gray-700 hover:text-primary-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
            </svg>
            <span 
              v-if="cartItemCount > 0"
              class="absolute -top-1 -right-1 bg-primary-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
            >
              {{ cartItemCount }}
            </span>
          </router-link>

          <!-- User Menu -->
          <div v-if="isAuthenticated" class="relative">
            <button
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-2"
            >
              <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>

            <!-- User Dropdown -->
            <div
              v-if="userMenuOpen"
              class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
            >
              <router-link
                to="/profile"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                @click="userMenuOpen = false"
              >
                {{ $t('nav.profile') }}
              </router-link>
              <button
                @click="logout"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
              >
                {{ $t('nav.logout') }}
              </button>
            </div>
          </div>

          <!-- Auth Buttons -->
          <div v-else class="flex items-center space-x-4">
            <router-link
              to="/login"
              class="text-gray-700 hover:text-primary-600 px-3 py-2 text-sm font-medium transition-colors"
            >
              {{ $t('nav.login') }}
            </router-link>
            <router-link
              to="/register"
              class="bg-primary-600 text-white hover:bg-primary-700 px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
              {{ $t('nav.register') }}
            </router-link>
          </div>

          <!-- Mobile menu button -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden p-2 text-gray-700 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 py-4">
        <div class="space-y-2">
          <router-link
            to="/"
            class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.home') }}
          </router-link>
          <router-link
            to="/therapists"
            class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.therapists') }}
          </router-link>
          <router-link
            to="/diagnosis"
            class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.diagnosis') }}
          </router-link>
          <router-link
            to="/appointments"
            class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.appointments') }}
          </router-link>
          <router-link
            to="/cart"
            class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.cart') }}
          </router-link>
        </div>

        <!-- Mobile auth buttons -->
        <div v-if="!isAuthenticated" class="mt-4 pt-4 border-t border-gray-200 space-y-2">
          <router-link
            to="/login"
            class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.login') }}
          </router-link>
          <router-link
            to="/register"
            class="block px-3 py-2 bg-primary-600 text-white hover:bg-primary-700 rounded-md text-center"
            @click="mobileMenuOpen = false"
          >
            {{ $t('nav.register') }}
          </router-link>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import LanguageSwitcher from './LanguageSwitcher.vue'

export default {
  name: 'Header',
  components: {
    LanguageSwitcher
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    
    const mobileMenuOpen = ref(false)
    const userMenuOpen = ref(false)

    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const cartItemCount = computed(() => cartStore.itemCount)

    const logout = () => {
      authStore.logout()
      userMenuOpen.value = false
      mobileMenuOpen.value = false
      router.push('/login')
    }

    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        userMenuOpen.value = false
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      mobileMenuOpen,
      userMenuOpen,
      isAuthenticated,
      cartItemCount,
      logout
    }
  }
}
</script> 