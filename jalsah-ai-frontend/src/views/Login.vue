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

          <!-- WhatsApp field with dial code selector (shown when email is not required) -->
          <div v-else>
            <div class="mb-2">
              <label for="whatsapp" class="form-label">{{ $t('auth.login.whatsapp') }}</label>
            </div>
            <div class="flex" style="direction: ltr;">
              <!-- Custom Country Selector -->
              <div class="relative flex-shrink-0">
                <button
                  type="button"
                  @click="toggleCountryDropdown"
                  :disabled="isDetectingCountry"
                  class="w-32 px-3 py-3 border border-gray-300 rounded-r-md bg-white text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between disabled:opacity-50 disabled:cursor-not-allowed h-12"
                  style="font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;"
                >
                  <span class="flex items-center">
                    <span v-if="isDetectingCountry" class="text-lg mr-1">
                      <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </span>
                    <span v-else-if="!isLoadingCountries" class="text-lg mr-1 emoji-flag">{{ getSelectedCountryFlag() }}</span>
                    <span v-else class="text-lg mr-1">🇪🇬</span>
                    <span class="text-xs">{{ getSelectedCountryDial() }}</span>
                  </span>
                  <svg v-if="!isDetectingCountry" class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div
                  v-if="showCountryDropdown && !isLoadingCountries"
                  class="absolute z-10 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                  style="font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;"
                >
                  <div class="p-2">
                    <input
                      v-model="countrySearch"
                      type="text"
                      placeholder="Search countries..."
                      class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                    />
                  </div>
                  <div class="max-h-48 overflow-y-auto">
                    <button
                      v-for="country in filteredCountries"
                      :key="country.code"
                      type="button"
                      @click="selectCountry(country.code)"
                      class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-center"
                      :class="{ 'bg-primary-50 text-primary-700': selectedCountryCode === country.code }"
                    >
                      <span class="text-lg mr-3 emoji-flag">{{ country.flag }}</span>
                      <span class="flex-1">{{ country.name }}</span>
                      <span class="text-gray-500 text-xs">{{ country.dial }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <input
                id="whatsapp"
                v-model="form.whatsapp"
                type="tel"
                required
                class="flex-1 px-3 py-3 border border-gray-300 rounded-l-md rounded-r-none border-r-0 focus:outline-none focus:ring-primary-500 focus:border-primary-500 h-12"
                :placeholder="$t('auth.login.whatsappPlaceholder')"
                dir="ltr"
                autocomplete="tel"
                style="text-align: left; direction: ltr;"
              />
            </div>
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
              <a href="#" @click.prevent="openForgotPasswordModal" class="font-medium text-primary-600 hover:text-primary-500">
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

    <!-- Forgot Password Modal -->
    <div v-if="showForgotPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeForgotPasswordModal">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('verification.forgotPasswordTitle') }}
            </h3>
            <button @click="closeForgotPasswordModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Step 1: Enter WhatsApp Number -->
          <div v-if="forgotPasswordStep === 1">
            <p class="text-sm text-gray-600 mb-4">
              {{ $t('verification.enterWhatsApp') }}
            </p>
            
            <!-- WhatsApp Input with Country Selector -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('auth.login.whatsapp') }}
              </label>
              <div class="flex">
                <!-- WhatsApp Input -->
                <input
                  v-model="forgotPasswordForm.whatsapp"
                  type="tel"
                  required
                  dir="ltr"
                  class="flex-1 px-3 py-3 border border-gray-300 rounded-l-md rounded-r-none border-l-0 focus:outline-none focus:ring-primary-500 focus:border-primary-500 h-12"
                  :placeholder="$t('auth.login.whatsappPlaceholder')"
                />
                
                <!-- Country Selector -->
                <div class="relative flex-shrink-0">
                  <button
                    type="button"
                    @click="toggleCountryDropdown"
                    :disabled="isDetectingCountry"
                    class="w-32 px-3 py-3 border border-gray-300 rounded-r-md bg-white text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between disabled:opacity-50 disabled:cursor-not-allowed h-12"
                    style="font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;"
                  >
                    <span class="flex items-center">
                      <span v-if="isDetectingCountry" class="text-lg mr-1">
                        <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </span>
                      <span v-else-if="!isLoadingCountries" class="text-lg mr-1 emoji-flag">{{ getSelectedCountryFlag() }}</span>
                      <span v-else class="text-lg mr-1">🇪🇬</span>
                      <span class="text-xs">{{ getSelectedCountryDial() }}</span>
                    </span>
                    <svg v-if="!isDetectingCountry" class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  
                  <!-- Country Dropdown -->
                  <div
                    v-if="showCountryDropdown && !isLoadingCountries"
                    class="absolute z-10 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                    style="font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;"
                  >
                    <div class="p-2">
                      <input
                        v-model="countrySearch"
                        type="text"
                        placeholder="Search countries..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                      />
                    </div>
                    <div class="max-h-48 overflow-y-auto">
                      <button
                        v-for="country in filteredCountries"
                        :key="country.code"
                        type="button"
                        @click="selectCountry(country.code)"
                        class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-center"
                        :class="{ 'bg-primary-50 text-primary-700': selectedCountryCode === country.code }"
                      >
                        <span class="text-lg mr-3 emoji-flag">{{ country.flag }}</span>
                        <span class="flex-1">{{ country.name }}</span>
                        <span class="text-gray-500 text-xs">{{ country.dial }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <button
              @click="sendForgotPasswordCode"
              :disabled="!forgotPasswordForm.whatsapp || forgotPasswordLoading"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="forgotPasswordLoading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('verification.sending') }}
              </span>
              <span v-else>{{ $t('verification.sendResetCode') }}</span>
            </button>
          </div>

          <!-- Step 2: Enter Reset Code -->
          <div v-if="forgotPasswordStep === 2">
            <p class="text-sm text-gray-600 mb-4">
              {{ $t('verification.enterResetCode') }}
            </p>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('verification.verifyCode') }}
              </label>
              <input
                v-model="forgotPasswordForm.code"
                type="text"
                maxlength="6"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                :placeholder="$t('verification.enterResetCode')"
              />
            </div>

            <button
              @click="verifyForgotPasswordCode"
              :disabled="!forgotPasswordForm.code || forgotPasswordLoading"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="forgotPasswordLoading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('verification.sending') }}
              </span>
              <span v-else>{{ $t('verification.verifyCode') }}</span>
            </button>
          </div>

          <!-- Step 3: Set New Password -->
          <div v-if="forgotPasswordStep === 3">
            <p class="text-sm text-gray-600 mb-4">
              {{ $t('verification.setNewPassword') }}
            </p>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $t('verification.newPassword') }}
              </label>
              <input
                v-model="forgotPasswordForm.newPassword"
                type="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                :placeholder="$t('verification.newPassword')"
              />
            </div>

                   <div class="mb-4">
                     <label class="block text-sm font-medium text-gray-700 mb-2">
                       {{ $t('verification.confirmNewPassword') }}
                     </label>
                     <input
                       v-model="forgotPasswordForm.confirmPassword"
                       type="password"
                       @blur="validateConfirmPassword"
                       class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                       :class="{
                         'border-gray-300': !confirmPasswordError,
                         'border-red-300 focus:border-red-500 focus:ring-red-500': confirmPasswordError
                       }"
                       :placeholder="$t('verification.confirmNewPassword')"
                     />
                     <p v-if="confirmPasswordError" class="mt-1 text-sm text-red-600">
                       {{ confirmPasswordError }}
                     </p>
                   </div>

            <button
              @click="resetPassword"
              :disabled="!forgotPasswordForm.newPassword || !forgotPasswordForm.confirmPassword || forgotPasswordLoading || forgotPasswordForm.newPassword !== forgotPasswordForm.confirmPassword"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="forgotPasswordLoading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
                {{ $t('verification.sending') }}
              </span>
              <span v-else>{{ $t('verification.resetPassword') }}</span>
            </button>
          </div>

          <!-- Back to Login Button -->
          <div class="mt-4 text-center">
            <button
              @click="closeForgotPasswordModal"
              class="text-sm text-primary-600 hover:text-primary-500"
            >
              {{ $t('verification.backToLogin') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useTherapistRegistrationStore } from '@/stores/therapistRegistration'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    const { locale, t } = useI18n()
    const authStore = useAuthStore()
    const therapistRegistrationStore = useTherapistRegistrationStore()
    const toast = useToast()
    
    const form = ref({
      email: '',
      whatsapp: '',
      password: '',
      rememberMe: false
    })

    // Country selector state
    const selectedCountryCode = ref('EG')
    const showCountryDropdown = ref(false)
    const countrySearch = ref('')
    const isDetectingCountry = ref(false)
    const countries = ref([])
    const isLoadingCountries = ref(false)

    const loading = computed(() => authStore.loading)
    const requireEmail = computed(() => {
      const result = therapistRegistrationStore.shouldShowEmail
      console.log('🔍 requireEmail computed called, result:', result, 'type:', typeof result)
      console.log('🔍 Original require_email value:', therapistRegistrationStore.settings.require_email)
      return result
    })

    const verificationError = ref(null)

    // Forgot password state
    const showForgotPasswordModal = ref(false)
    const forgotPasswordStep = ref(1) // 1: Enter WhatsApp, 2: Enter Code, 3: Set Password
    const forgotPasswordLoading = ref(false)
           const forgotPasswordForm = ref({
             whatsapp: '',
             code: '',
             newPassword: '',
             confirmPassword: ''
           })
           const resetToken = ref('')
           const confirmPasswordError = ref('')

    // Filtered countries based on search
    // Localized country names
    const localizedCountries = computed(() => {
      const isArabic = locale.value === 'ar'
      return countries.value.map(country => ({
        ...country,
        name: isArabic ? country.name_ar : country.name_en,
        code: country.country_code,
        dial: country.dial_code,
        flag: country.flag // Explicitly preserve the flag
      }))
    })

    const filteredCountries = computed(() => {
      if (!countrySearch.value) {
        return localizedCountries.value
      }
      return localizedCountries.value.filter(country => 
        country.name.toLowerCase().includes(countrySearch.value.toLowerCase()) ||
        country.dial.includes(countrySearch.value) ||
        country.code.toLowerCase().includes(countrySearch.value.toLowerCase())
      )
    })

    const handleLogin = async () => {
      verificationError.value = null
      
      const credentials = {
        password: form.value.password
      }
      
      // Add email or WhatsApp based on settings
      if (requireEmail.value) {
        credentials.email = form.value.email
      } else {
        // Get selected country info and build full WhatsApp number
        const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
        const fullWhatsAppNumber = selectedCountry ? selectedCountry.dial_code + form.value.whatsapp : form.value.whatsapp
        credentials.whatsapp = fullWhatsAppNumber
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

    // Country selector functions
    const toggleCountryDropdown = () => {
      showCountryDropdown.value = !showCountryDropdown.value
    }

    const selectCountry = (countryCode) => {
      selectedCountryCode.value = countryCode
      showCountryDropdown.value = false
      countrySearch.value = ''
    }

    const getSelectedCountryFlag = () => {
      const country = countries.value.find(c => c.country_code === selectedCountryCode.value)
      return country ? country.flag : '🇪🇬'
    }

    const getSelectedCountryDial = () => {
      const country = countries.value.find(c => c.country_code === selectedCountryCode.value)
      return country ? country.dial_code : '+20'
    }

    // Load countries data
    const loadCountries = async () => {
      if (isLoadingCountries.value) return
      
      isLoadingCountries.value = true
      try {
        const response = await fetch('/countries-codes-and-flags.json')
        if (response.ok) {
          const data = await response.json()
          countries.value = data
        } else {
          console.warn('Failed to load countries data, using fallback')
          // Fallback data
          countries.value = [
            { country_code: 'EG', name_en: 'Egypt', name_ar: 'مصر', dial_code: '+20', flag: '🇪🇬' }
          ]
        }
      } catch (error) {
        console.error('Error loading countries:', error)
        // Fallback data
        countries.value = [
          { country_code: 'EG', name_en: 'Egypt', name_ar: 'مصر', dial_code: '+20', flag: '🇪🇬' }
        ]
      } finally {
        isLoadingCountries.value = false
      }
    }

    // Handle clicks outside dropdown
    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        showCountryDropdown.value = false
      }
    }

    // Forgot password methods
    const openForgotPasswordModal = () => {
      showForgotPasswordModal.value = true
      forgotPasswordStep.value = 1
      forgotPasswordForm.value = {
        whatsapp: '',
        code: '',
        newPassword: '',
        confirmPassword: ''
      }
      resetToken.value = ''
    }

           const closeForgotPasswordModal = () => {
             showForgotPasswordModal.value = false
             forgotPasswordStep.value = 1
             forgotPasswordForm.value = {
               whatsapp: '',
               code: '',
               newPassword: '',
               confirmPassword: ''
             }
             resetToken.value = ''
             confirmPasswordError.value = ''
           }

           const validateConfirmPassword = () => {
             if (!forgotPasswordForm.value.confirmPassword) {
               confirmPasswordError.value = ''
               return
             }
             
             if (forgotPasswordForm.value.newPassword !== forgotPasswordForm.value.confirmPassword) {
               confirmPasswordError.value = t('verification.passwordMismatch')
             } else {
               confirmPasswordError.value = ''
             }
           }

    const sendForgotPasswordCode = async () => {
      if (!forgotPasswordForm.value.whatsapp) {
        toast.error(t('verification.enterWhatsApp'))
        return
      }

      forgotPasswordLoading.value = true
      try {
        // Combine country code with WhatsApp number
        const fullWhatsAppNumber = getSelectedCountryDial() + forgotPasswordForm.value.whatsapp
        
        const response = await authStore.forgotPassword(fullWhatsAppNumber)
        
        if (response) {
          toast.success(t('verification.resetCodeSent'))
          forgotPasswordStep.value = 2
        }
      } catch (error) {
        console.error('Forgot password error:', error)
        toast.error(error.response?.data?.error || t('verification.resendFailed'))
      } finally {
        forgotPasswordLoading.value = false
      }
    }

    const verifyForgotPasswordCode = async () => {
      if (!forgotPasswordForm.value.code) {
        toast.error(t('verification.enterResetCode'))
        return
      }

      forgotPasswordLoading.value = true
      try {
        const fullWhatsAppNumber = getSelectedCountryDial() + forgotPasswordForm.value.whatsapp
        
        const response = await authStore.verifyForgotPassword(fullWhatsAppNumber, forgotPasswordForm.value.code)
        
        if (response) {
          resetToken.value = response.reset_token
          toast.success(t('verification.codeVerified'))
          forgotPasswordStep.value = 3
        }
      } catch (error) {
        console.error('Verify forgot password error:', error)
        toast.error(error.response?.data?.error || t('verification.resendFailed'))
      } finally {
        forgotPasswordLoading.value = false
      }
    }

           const resetPassword = async () => {
             if (!forgotPasswordForm.value.newPassword || !forgotPasswordForm.value.confirmPassword) {
               toast.error(t('verification.passwordMismatch'))
               return
             }

             if (forgotPasswordForm.value.newPassword !== forgotPasswordForm.value.confirmPassword) {
               confirmPasswordError.value = t('verification.passwordMismatch')
               toast.error(t('verification.passwordMismatch'))
               return
             }

             if (forgotPasswordForm.value.newPassword.length < 6) {
               toast.error(t('verification.passwordTooShort'))
               return
             }

             forgotPasswordLoading.value = true
             try {
               const response = await authStore.resetPassword(resetToken.value, forgotPasswordForm.value.newPassword)
               
               if (response) {
                 toast.success(t('verification.passwordResetSuccess'))
                 closeForgotPasswordModal()
               }
             } catch (error) {
               console.error('Reset password error:', error)
               toast.error(error.response?.data?.error || t('verification.resendFailed'))
             } finally {
               forgotPasswordLoading.value = false
             }
           }

    // Load therapist registration settings and countries on mount
    onMounted(async () => {
      await therapistRegistrationStore.loadSettings()
      await loadCountries()
      console.log('🔍 Login form - Registration settings loaded:', therapistRegistrationStore.settings)
      console.log('🔍 Login form - Should show email:', requireEmail.value)
      
      // Add click outside listener
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      form,
      loading,
      requireEmail,
      verificationError,
      handleLogin,
      goToVerification,
      // Country selector
      selectedCountryCode,
      showCountryDropdown,
      countrySearch,
      isDetectingCountry,
      countries,
      isLoadingCountries,
      localizedCountries,
      filteredCountries,
      toggleCountryDropdown,
      selectCountry,
      getSelectedCountryFlag,
      getSelectedCountryDial,
      // Forgot password
      showForgotPasswordModal,
      forgotPasswordStep,
      forgotPasswordLoading,
      forgotPasswordForm,
      confirmPasswordError,
      openForgotPasswordModal,
      closeForgotPasswordModal,
      sendForgotPasswordCode,
      verifyForgotPasswordCode,
      resetPassword,
      validateConfirmPassword
    }
  }
}
</script> 

<style scoped>
/* Import comprehensive emoji fonts for better cross-browser support */
@import url('https://fonts.googleapis.com/css2?family=Noto+Color+Emoji&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Noto+Emoji&display=swap');

.emoji-flag {
  /* Comprehensive emoji font stack with Chrome-specific fixes */
  font-family: 
    'Noto Color Emoji',           /* Google's color emoji font */
    'Noto Emoji',                 /* Google's monochrome emoji font */
    'Apple Color Emoji',          /* macOS/iOS */
    'Segoe UI Emoji',             /* Windows 10+ */
    'Segoe UI Symbol',            /* Windows 8/8.1 */
    'Twemoji',                    /* Twitter's emoji font */
    'EmojiOne',                   /* Alternative emoji font */
    'Android Emoji',              /* Android */
    'Noto Emoji',                 /* Fallback */
    sans-serif;
  
  font-size: 1.2em;
  line-height: 1;
  display: inline-block;
  vertical-align: middle;
  
  /* Chrome-specific emoji rendering fixes */
  font-variant-emoji: emoji;
  -webkit-font-feature-settings: "liga", "kern";
  font-feature-settings: "liga", "kern";
  text-rendering: optimizeLegibility;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  
  /* Force emoji rendering mode */
  font-variant-ligatures: common-ligatures;
  -webkit-font-variant-ligatures: common-ligatures;
  
  /* Prevent text selection issues with emojis */
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Chrome-specific flag emoji fixes */
@supports (-webkit-appearance: none) {
  .emoji-flag {
    /* Force Chrome to use emoji fonts */
    font-family: 'Noto Color Emoji', 'Apple Color Emoji', 'Segoe UI Emoji', sans-serif;
    -webkit-font-feature-settings: "liga", "kern", "calt";
    font-feature-settings: "liga", "kern", "calt";
  }
}

/* Additional emoji font fallbacks and optimization */
.emoji-flag {
  font-display: swap;
  unicode-range: U+1F1E6-1F1FF, U+1F300-1F5FF, U+1F600-1F64F, U+1F680-1F6FF, U+1F700-1F77F, U+1F780-1F7FF, U+1F800-1F8FF, U+1F900-1F9FF, U+1FA00-1FA6F, U+1FA70-1FAFF, U+2600-26FF, U+2700-27BF;
}
</style> 