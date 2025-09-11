<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <div class="w-12 h-12 bg-gradient-to-r from-primary-600 to-secondary-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-xl">{{ $t('logo.text') }}</span>
        </div>
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        {{ $t('auth.login.title') }}
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ $t('auth.login.or') }}
        <router-link to="/register" class="font-medium text-primary-600 hover:text-primary-500">
          {{ $t('auth.login.createAccount') }}
        </router-link>
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <form @submit.prevent="handleLogin" class="space-y-6" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
          <!-- Debug info -->
          <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; font-size: 12px;">
            DEBUG: requireEmail = {{ requireEmail }} (type: {{ typeof requireEmail }})
          </div>
          
          <!-- Email field (shown when email is required) -->
          <div v-if="requireEmail">
            <label for="email" class="form-label">{{ $t('auth.login.email') }}</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="input-field"
              :placeholder="$t('auth.login.emailPlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="email"
            />
          </div>

          <!-- WhatsApp field (shown when email is not required) -->
          <div v-else>
            <label for="whatsapp" class="form-label">{{ $t('auth.login.whatsapp') }}</label>
            <input
              id="whatsapp"
              v-model="form.whatsapp"
              type="tel"
              required
              class="input-field"
              :placeholder="$t('auth.login.whatsappPlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="tel"
            />
          </div>

          <div>
            <label for="password" class="form-label">{{ $t('auth.login.password') }}</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="input-field"
              :placeholder="$t('auth.login.passwordPlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="current-password"
            />
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.rememberMe"
                type="checkbox"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                {{ $t('auth.login.rememberMe') }}
              </label>
            </div>

            <div class="text-sm">
              <a href="#" class="font-medium text-primary-600 hover:text-primary-500">
                {{ $t('auth.login.forgotPassword') }}
              </a>
            </div>
          </div>

          <!-- Verification Error -->
          <div v-if="verificationError" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-yellow-800">{{ verificationError }}</p>
                <div class="mt-3">
                  <button
                    @click="goToVerification"
                    class="bg-yellow-100 text-yellow-800 px-3 py-2 rounded-md text-sm font-medium hover:bg-yellow-200 transition-colors"
                  >
                    {{ $t('auth.verifyAccount') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full btn-primary py-3 text-base font-medium"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('auth.login.signingIn') }}
              </span>
              <span v-else>{{ $t('auth.login.signIn') }}</span>
            </button>
          </div>
        </form>

        <!-- Verification Link -->
        <div class="mt-4 text-center">
          <button
            @click="goToVerification"
            class="text-sm text-primary-600 hover:text-primary-500 font-medium"
          >
            {{ $t('auth.login.needVerification') }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTherapistRegistrationStore } from '@/stores/therapistRegistration'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const therapistRegistrationStore = useTherapistRegistrationStore()
    
    const form = ref({
      email: '',
      whatsapp: '',
      password: '',
      rememberMe: false
    })

    const loading = computed(() => authStore.loading)
    const requireEmail = computed(() => {
      const result = therapistRegistrationStore.shouldShowEmail
      console.log('🔍 requireEmail computed called, result:', result, 'type:', typeof result)
      console.log('🔍 Original require_email value:', therapistRegistrationStore.settings.require_email)
      return result
    })

    const verificationError = ref(null)

    const handleLogin = async () => {
      verificationError.value = null
      
      const credentials = {
        password: form.value.password
      }
      
      // Add email or WhatsApp based on settings
      if (requireEmail.value) {
        credentials.email = form.value.email
      } else {
        credentials.whatsapp = form.value.whatsapp
      }
      
      const result = await authStore.login(credentials)
      
      if (result === true) {
        // Redirect to homepage after successful login
        router.push('/')
      } else if (result && result.needsVerification) {
        // Show verification error and button
        verificationError.value = result.message
      }
    }

    const goToVerification = () => {
      // Navigate to verification page with the identifier if available
      const identifier = requireEmail.value ? form.value.email : form.value.whatsapp
      
      if (identifier) {
        // If user has entered email/WhatsApp, pass it to verification page
        router.push(`/verify?identifier=${encodeURIComponent(identifier)}`)
      } else {
        // If no identifier entered, go to verification page without identifier
        // User will need to enter their email/WhatsApp on verification page
        router.push('/verify')
      }
    }

    // Load therapist registration settings on mount
    onMounted(async () => {
      await therapistRegistrationStore.loadSettings()
      console.log('🔍 Login form - Registration settings loaded:', therapistRegistrationStore.settings)
      console.log('🔍 Login form - Should show email:', requireEmail.value)
    })

    return {
      form,
      loading,
      requireEmail,
      verificationError,
      handleLogin,
      goToVerification
    }
  }
}
</script> 