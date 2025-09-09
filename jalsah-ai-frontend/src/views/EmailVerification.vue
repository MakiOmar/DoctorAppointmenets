<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <div class="w-12 h-12 bg-gradient-to-r from-primary-600 to-secondary-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-xl">{{ $t('logo.text') }}</span>
        </div>
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        {{ $t('verification.title') }}
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ $t('verification.subtitle') }}
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <form @submit.prevent="handleVerification" class="space-y-6" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
          <!-- Contact Display -->
          <div class="text-center">
            <p class="text-sm text-gray-600">
              {{ verificationMethod === 'whatsapp' ? $t('verification.whatsappSentTo') : $t('verification.emailSentTo') }}
            </p>
            <p class="text-lg font-medium text-gray-900">{{ contact }}</p>
          </div>

          <!-- Verification Code -->
          <div>
            <label for="verification_code" class="form-label">{{ $t('verification.code') }}</label>
            <input
              id="verification_code"
              v-model="form.verification_code"
              type="text"
              required
              maxlength="6"
              class="input-field text-center text-2xl tracking-widest"
              :placeholder="$t('verification.codePlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="one-time-code"
            />
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('verification.codeHint') }}
            </p>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading || !isFormValid"
              class="w-full btn-primary py-3 text-base font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('verification.verifying') }}
              </span>
              <span v-else>{{ $t('verification.verify') }}</span>
            </button>
          </div>
        </form>

        <!-- Resend Code Section -->
        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            {{ $t('verification.didntReceive') }}
          </p>
          <button
            @click="resendCode"
            :disabled="resendLoading || resendCooldown > 0"
            class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="resendLoading">{{ $t('verification.sending') }}</span>
            <span v-else-if="resendCooldown > 0">{{ $t('verification.resendIn', { seconds: resendCooldown }) }}</span>
            <span v-else>{{ $t('verification.resendCode') }}</span>
          </button>
        </div>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
          <router-link to="/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
            {{ $t('verification.backToLogin') }}
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

export default {
  name: 'EmailVerification',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const { t } = useI18n()
    const authStore = useAuthStore()
    const toast = useToast()
    
    const form = ref({
      verification_code: ''
    })

    const loading = ref(false)
    const resendLoading = ref(false)
    const resendCooldown = ref(0)
    const contact = ref('')
    const verificationMethod = ref('email')
    const countdownInterval = ref(null)

    const isFormValid = computed(() => {
      return form.value.verification_code.length === 6
    })

    // Get contact info from route params or localStorage
    onMounted(() => {
      contact.value = route.params.contact || localStorage.getItem('pending_verification_contact') || ''
      verificationMethod.value = route.query.method || 'email'
      if (!contact.value) {
        router.push('/register')
        return
      }
      
      // Start countdown if user just registered (to prevent immediate resend)
      // Check if we have a recent registration timestamp
      const registrationTime = localStorage.getItem('registration_timestamp')
      if (registrationTime) {
        const timeSinceRegistration = Date.now() - parseInt(registrationTime)
        const timeToWait = 60000 - timeSinceRegistration // 60 seconds - time elapsed
        
        if (timeToWait > 0) {
          // Start countdown from remaining time
          resendCooldown.value = Math.ceil(timeToWait / 1000)
          countdownInterval.value = setInterval(() => {
            resendCooldown.value--
            if (resendCooldown.value <= 0) {
              clearInterval(countdownInterval.value)
            }
          }, 1000)
        }
        
        // Clear the timestamp after using it
        localStorage.removeItem('registration_timestamp')
      }
    })

    // Cleanup interval on unmount
    onUnmounted(() => {
      if (countdownInterval.value) {
        clearInterval(countdownInterval.value)
      }
    })

    const startResendCooldown = () => {
      resendCooldown.value = 60 // 60 seconds
      countdownInterval.value = setInterval(() => {
        resendCooldown.value--
        if (resendCooldown.value <= 0) {
          clearInterval(countdownInterval.value)
        }
      }, 1000)
    }

    const handleVerification = async () => {
      if (!isFormValid.value) {
        return
      }

      loading.value = true
      try {
        const response = await authStore.verifyEmail({
          email: contact.value,
          code: form.value.verification_code
        })
        
        if (response) {
          // Clear pending verification contact
          localStorage.removeItem('pending_verification_contact')
          
          const successMessage = verificationMethod.value === 'whatsapp' 
            ? t('toast.auth.whatsappVerified') 
            : t('toast.auth.emailVerified')
          toast.success(successMessage)
          
          // Redirect to homepage
          router.push('/')
        }
      } catch (error) {
        console.error('Verification error:', error)
      } finally {
        loading.value = false
      }
    }

    const resendCode = async () => {
      resendLoading.value = true
      try {
        const response = await authStore.resendVerification(contact.value)
        
        if (response) {
          toast.success(t('toast.auth.verificationSent'))
          startResendCooldown()
        }
      } catch (error) {
        console.error('Resend error:', error)
      } finally {
        resendLoading.value = false
      }
    }

    return {
      form,
      loading,
      resendLoading,
      resendCooldown,
      contact,
      verificationMethod,
      isFormValid,
      handleVerification,
      resendCode
    }
  }
}
</script>

<style scoped>
.card {
  @apply bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10;
}

.input-field {
  @apply appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.btn-primary {
  @apply relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500;
}
</style>
