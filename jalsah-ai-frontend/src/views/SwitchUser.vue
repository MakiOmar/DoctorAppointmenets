<template>
  <div
    class="min-h-screen bg-gray-50 flex flex-col py-12 sm:px-6 lg:px-8"
    :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
  >
    <!-- State 1: Admin login form (when not logged in as admin) -->
    <template v-if="!showSwitchForm">
      <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ $t('switchUser.title') }}
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          {{ $t('switchUser.adminLogin.subtitle') }}
        </p>
      </div>
      <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Access denied message when logged in as non-admin -->
        <div
          v-if="accessDenied"
          class="mb-4 p-4 rounded-md bg-red-50 border border-red-200"
          role="alert"
        >
          <p class="text-sm text-red-800">{{ $t('switchUser.accessDenied') }}</p>
          <button
            type="button"
            class="mt-2 text-sm font-medium text-red-700 hover:text-red-800 underline"
            @click="handleBackToLogin"
          >
            {{ $t('switchUser.backToLogin') }}
          </button>
        </div>
        <div class="card">
          <form @submit.prevent="handleAdminLogin" class="space-y-6">
            <div>
              <label for="switch-user-email" class="form-label">{{ $t('switchUser.adminLogin.email') }}</label>
              <input
                id="switch-user-email"
                v-model="adminForm.email"
                type="email"
                required
                class="input-field"
                :placeholder="$t('switchUser.adminLogin.emailPlaceholder')"
                autocomplete="email"
              />
            </div>
            <div>
              <label for="switch-user-password" class="form-label">{{ $t('switchUser.adminLogin.password') }}</label>
              <input
                id="switch-user-password"
                v-model="adminForm.password"
                type="password"
                required
                class="input-field"
                :placeholder="$t('switchUser.adminLogin.passwordPlaceholder')"
                autocomplete="current-password"
              />
            </div>
            <button
              type="submit"
              class="btn-primary w-full flex justify-center py-2 px-4"
              :disabled="adminLoading"
            >
              <span v-if="adminLoading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
              </span>
              {{ $t('switchUser.adminLogin.submit') }}
            </button>
          </form>
        </div>
      </div>
    </template>

    <!-- State 2: Switch form (when logged in as administrator) -->
    <template v-else>
      <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ $t('switchUser.switchForm.title') }}
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          {{ $t('switchUser.switchForm.subtitle') }}
        </p>
      </div>
      <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
        <div class="card">
          <form @submit.prevent="handleSwitchUser" class="space-y-6">
            <!-- Search patient -->
            <div>
              <label for="switch-user-search" class="form-label">{{ $t('switchUser.switchForm.searchPlaceholder') }}</label>
              <input
                id="switch-user-search"
                v-model="searchQuery"
                type="text"
                class="input-field"
                :placeholder="$t('switchUser.switchForm.searchPlaceholder')"
                autocomplete="off"
                @input="onSearchInput"
              />
              <div v-if="searchLoading" class="mt-1 text-sm text-gray-500">{{ $t('common.loading') }}</div>
              <!-- Results dropdown -->
              <div
                v-if="searchResults.length > 0 && !selectedUser"
                class="mt-1 border border-gray-200 rounded-md shadow-lg bg-white max-h-48 overflow-y-auto z-10"
              >
                <button
                  v-for="u in searchResults"
                  :key="u.id"
                  type="button"
                  class="w-full px-4 py-3 text-left text-sm hover:bg-gray-100 flex flex-col"
                  @click="selectUser(u)"
                >
                  <span class="font-medium text-gray-900">{{ u.display_name }}</span>
                  <span class="text-gray-500 text-xs">{{ u.user_email }}</span>
                  <span v-if="u.whatsapp" class="text-gray-500 text-xs">{{ u.whatsapp }}</span>
                </button>
              </div>
              <!-- Selected user display -->
              <div
                v-if="selectedUser"
                class="mt-2 p-3 rounded-md bg-primary-50 border border-primary-200 flex items-center justify-between"
              >
                <div>
                  <span class="font-medium text-gray-900">{{ selectedUser.display_name }}</span>
                  <span class="text-gray-500 text-sm block">{{ selectedUser.user_email }}</span>
                </div>
                <button
                  type="button"
                  class="text-sm text-primary-600 hover:text-primary-800"
                  @click="selectedUser = null"
                >
                  {{ $t('common.remove') }}
                </button>
              </div>
            </div>
            <!-- Admin password confirmation -->
            <div>
              <label for="switch-user-admin-password" class="form-label">{{ $t('switchUser.switchForm.adminPassword') }}</label>
              <input
                id="switch-user-admin-password"
                v-model="switchForm.adminPassword"
                type="password"
                required
                class="input-field"
                :placeholder="$t('switchUser.switchForm.adminPasswordPlaceholder')"
                autocomplete="current-password"
              />
            </div>
            <div class="flex gap-3">
              <button
                type="button"
                class="btn-secondary flex-1"
                @click="handleBackToLogin"
              >
                {{ $t('switchUser.switchForm.logout') }}
              </button>
              <button
                type="submit"
                class="btn-primary flex-1"
                :disabled="!selectedUser || !switchForm.adminPassword || switchLoading"
              >
                <span v-if="switchLoading" class="flex items-center justify-center">
                  <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                </span>
                <span v-else>{{ $t('switchUser.switchForm.submit') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()

// State 1: Admin login
const adminForm = ref({ email: '', password: '' })
const adminLoading = ref(false)
const accessDenied = ref(false)

// State 2: Switch form
const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const searchDebounce = ref(null)
const selectedUser = ref(null)
const switchForm = ref({ adminPassword: '' })
const switchLoading = ref(false)

const isAdmin = () => {
  const user = authStore.user
  if (!user) return false
  return user.role === 'administrator' || (Array.isArray(user.roles) && user.roles.includes('administrator'))
}

const showSwitchForm = computed(() => authStore.isAuthenticated && isAdmin())

// When already logged in as non-admin, show access denied
watch(
  () => authStore.isAuthenticated,
  (authenticated) => {
    if (authenticated && !isAdmin()) {
      accessDenied.value = true
    } else {
      accessDenied.value = false
    }
  },
  { immediate: true }
)

function onSearchInput() {
  if (searchDebounce.value) clearTimeout(searchDebounce.value)
  const q = searchQuery.value.trim()
  if (q.length < 1) {
    searchResults.value = []
    return
  }
  searchDebounce.value = setTimeout(async () => {
    searchLoading.value = true
    try {
      const res = await api.get('/api/ai/users/search', { params: { q } })
      if (res.data?.success && Array.isArray(res.data.data)) {
        searchResults.value = res.data.data
      } else {
        searchResults.value = []
      }
    } catch (err) {
      searchResults.value = []
      if (err.response?.status === 403) {
        toast.error(t('switchUser.errors.accessDenied'))
      }
    } finally {
      searchLoading.value = false
    }
  }, 300)
}

function selectUser(user) {
  selectedUser.value = user
  searchQuery.value = ''
  searchResults.value = []
}

async function handleAdminLogin() {
  adminLoading.value = true
  accessDenied.value = false
  try {
    const credentials = {
      email: adminForm.value.email,
      password: adminForm.value.password,
      country_code: 'EG'
    }
    const result = await authStore.login(credentials)
    if (result === true) {
      if (!isAdmin()) {
        toast.error(t('switchUser.accessDenied'))
        accessDenied.value = true
        authStore.logout(false)
      }
    }
  } catch (error) {
    const msg = error.response?.data?.error || error.message
    toast.error(msg)
  } finally {
    adminLoading.value = false
  }
}

function handleBackToLogin() {
  accessDenied.value = false
  authStore.logout(false)
  selectedUser.value = null
  switchForm.value.adminPassword = ''
  searchResults.value = []
}

async function handleSwitchUser() {
  if (!selectedUser.value?.id || !switchForm.value.adminPassword) return
  switchLoading.value = true
  try {
    const res = await api.post('/api/ai/switch-user', {
      user_id: selectedUser.value.id,
      admin_password: switchForm.value.adminPassword
    })
    if (res.data?.success && res.data?.data?.token && res.data?.data?.user) {
      authStore.setSession(res.data.data.token, res.data.data.user)
      toast.success(t('switchUser.success'))
      router.push('/appointments')
    } else {
      toast.error(t('switchUser.errors.generic'))
    }
  } catch (error) {
    const msg = error.response?.data?.error || error.message
    if (error.response?.status === 401) {
      toast.error(t('switchUser.errors.invalidPassword'))
    } else if (error.response?.status === 404) {
      toast.error(t('switchUser.errors.userNotFound'))
    } else {
      toast.error(msg || t('switchUser.errors.generic'))
    }
  } finally {
    switchLoading.value = false
  }
}
</script>
