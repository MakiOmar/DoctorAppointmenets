<template>
  <nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <router-link to="/" class="flex items-center">
            <div class="w-8 h-8 bg-gradient-to-r from-primary-600 to-secondary-600 rounded-lg flex items-center justify-center mr-3">
              <span class="text-white font-bold text-sm">J</span>
            </div>
            <span class="text-xl font-bold text-gray-800">Jalsah</span>
          </router-link>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Guest Navigation -->
          <template v-if="!isAuthenticated">
            <router-link to="/login" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              Sign In
            </router-link>
            <router-link to="/register" class="btn-primary px-4 py-2 rounded-md text-sm font-medium">
              Sign Up
            </router-link>
          </template>

          <!-- Customer Navigation -->
          <template v-else-if="userRole === 'customer'">
            <router-link to="/therapists" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              Therapists
            </router-link>
            <router-link to="/appointments" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              Appointments
            </router-link>
            <router-link to="/cart" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              Cart
            </router-link>
            <div class="relative">
              <button @click="showUserMenu = !showUserMenu" class="flex items-center text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                <span>{{ user?.first_name || 'User' }}</span>
                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  Profile
                </router-link>
                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  Sign Out
                </button>
              </div>
            </div>
          </template>

          <!-- Doctor Navigation -->
          <template v-else-if="userRole === 'doctor' || userRole === 'clinic_manager'">
            <router-link to="/doctor" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              Dashboard
            </router-link>
            <router-link to="/doctor/appointments" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              My Appointments
            </router-link>
            <router-link to="/doctor/schedule" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              Schedule
            </router-link>
            <div class="relative">
              <button @click="showUserMenu = !showUserMenu" class="flex items-center text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                <span>Dr. {{ user?.first_name || 'Doctor' }}</span>
                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <router-link to="/doctor/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  Profile
                </router-link>
                <router-link to="/doctor/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  Settings
                </router-link>
                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  Sign Out
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const showUserMenu = ref(false)

const isAuthenticated = computed(() => authStore.isAuthenticated)
const user = computed(() => authStore.user)
const userRole = computed(() => user.value?.role)

const logout = () => {
  authStore.logout()
  showUserMenu.value = false
}

// Close menu when clicking outside
const closeMenu = () => {
  showUserMenu.value = false
}

// Add click outside listener
document.addEventListener('click', (e) => {
  if (!e.target.closest('.relative')) {
    showUserMenu.value = false
  }
})
</script> 