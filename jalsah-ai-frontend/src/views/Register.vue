<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
        <div class="w-12 h-12 bg-gradient-to-r from-primary-600 to-secondary-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-xl">{{ $t('logo.text') }}</span>
        </div>
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        {{ $t('auth.register.title') }}
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ $t('auth.register.or') }}
        <router-link to="/login" class="font-medium text-primary-600 hover:text-primary-500">
          {{ $t('auth.register.signInToExisting') }}
        </router-link>
      </p>
      
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <form @submit.prevent="handleRegister" class="space-y-6" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
          <!-- Name Fields -->
          <div class="flex gap-4 name-fields-container">
            <!-- First Name Field -->
            <div class="flex-1">
              <label for="first_name" class="form-label">{{ $t('auth.register.firstName') }} <span class="text-red-500">*</span></label>
              <input
                id="first_name"
                v-model="form.first_name"
                type="text"
                required
                class="input-field"
                :placeholder="$t('auth.register.firstName')"
                :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
                autocomplete="given-name"
              />
            </div>
            <!-- Last Name Field -->
            <div class="flex-1">
              <label for="last_name" class="form-label">{{ $t('auth.register.lastName') }} <span class="text-red-500">*</span></label>
              <input
                id="last_name"
                v-model="form.last_name"
                type="text"
                required
                class="input-field"
                :placeholder="$t('auth.register.lastName')"
                :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
                autocomplete="family-name"
              />
            </div>
          </div>

          <!-- Email (conditional based on settings) -->
          <div v-if="shouldShowEmailField">
            <label for="email" class="form-label">{{ $t('auth.register.email') }} <span class="text-red-500">*</span></label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              :required="shouldShowEmailField"
              class="input-field"
              :placeholder="$t('auth.register.emailPlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="email"
            />
          </div>


          <!-- WhatsApp with International Prefix -->
          <div>
            <div class="mb-2">
              <label for="whatsapp" class="form-label">{{ $t('auth.register.whatsapp') }} <span class="text-red-500">*</span></label>
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
              dir="ltr"
                class="flex-1 px-3 py-3 border border-gray-300 rounded-l-md rounded-r-none border-r-0 focus:outline-none focus:ring-primary-500 focus:border-primary-500 h-12"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': whatsappDialCodeError }"
              :placeholder="$t('auth.register.whatsappPlaceholder')"
                autocomplete="tel"
                style="text-align: left; direction: ltr;"
                @input="validateWhatsAppNumber"
                @blur="onWhatsAppBlur"
            />
          </div>
          </div>
          
          <!-- WhatsApp Phone Validation Message -->
          <div v-if="phoneValidationMessage" class="mt-2 text-sm" :class="phoneValidationMessage.type === 'error' ? 'text-red-600' : 'text-green-600'">
            <div class="font-medium">{{ phoneValidationMessage.title }}</div>
            <div class="font-mono text-xs mt-1">{{ phoneValidationMessage.fullNumber }}</div>
            <div v-if="phoneValidationMessage.type === 'error'" class="mt-1">{{ phoneValidationMessage.error }}</div>
          </div>
          
          <!-- WhatsApp Dial Code Error Message -->
          <div v-if="whatsappDialCodeError" class="mt-1 text-sm text-red-600">
            {{ $t('auth.register.noNeedDialCode') }}
          </div>


          <!-- Password -->
          <div>
            <label for="password" class="form-label">{{ $t('auth.register.password') }} <span class="text-red-500">*</span></label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              @blur="validatePasswordMatch"
              class="input-field"
              :class="{
                'border-red-300 focus:border-red-500 focus:ring-red-500': passwordMismatchError
              }"
              :placeholder="$t('auth.register.createPassword')"
              minlength="8"
            />
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('auth.register.passwordHint') }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="confirm_password" class="form-label">{{ $t('auth.register.confirmPassword') }} <span class="text-red-500">*</span></label>
            <input
              id="confirm_password"
              v-model="form.confirm_password"
              type="password"
              required
              @blur="validatePasswordMatch"
              class="input-field"
              :class="{
                'border-red-300 focus:border-red-500 focus:ring-red-500': passwordMismatchError
              }"
              :placeholder="$t('auth.register.confirmPasswordPlaceholder')"
            />
            <p v-if="passwordMismatchError" class="mt-1 text-sm text-red-600">
              {{ $t('verification.passwordMismatch') }}
            </p>
          </div>

          <!-- Terms -->
          <div class="flex items-center">
            <input
              id="terms"
              v-model="form.agreeToTerms"
              type="checkbox"
              required
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label for="terms" class="ml-2 block text-sm text-gray-900">
              {{ $t('auth.register.agreeTo') }}
              <a href="#" class="text-primary-600 hover:text-primary-500">{{ $t('auth.register.termsOfService') }}</a>
              {{ $t('auth.register.and') }}
              <a href="#" class="text-primary-600 hover:text-primary-500">{{ $t('auth.register.privacyPolicy') }}</a>
              <span class="text-red-500">*</span>
            </label>
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
                {{ $t('auth.register.creatingAccount') }}
              </span>
              <span v-else>{{ $t('auth.register.createAccount') }}</span>
            </button>
          </div>
        </form>

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
  name: 'Register',
  setup() {
    const router = useRouter()
    const { locale, t } = useI18n()
    const authStore = useAuthStore()
    const therapistRegStore = useTherapistRegistrationStore()
    const toast = useToast()
    
    const form = ref({
      first_name: '',
      last_name: '',
      email: '',
      whatsapp: '',
      password: '',
      confirm_password: '',
      agreeToTerms: false
    })
    
    const selectedCountryCode = ref('EG')
    const shouldShowEmailField = ref(false)
    const otpMethod = ref('email')
    const userCountryCode = ref('EG')
    const showCountryDropdown = ref(false)
    const countrySearch = ref('')
    const isDetectingCountry = ref(false)
    const countries = ref([])
    const isLoadingCountries = ref(false)

    const loading = computed(() => authStore.loading)
    
    // Password mismatch error - shown when user focuses out of confirm password field
    const passwordMismatchError = ref(false)
    
    // WhatsApp dial code error - shown when user includes dial code in input
    const whatsappDialCodeError = ref(false)
    
    // Phone validation message - shown when user focuses out of WhatsApp input
    const phoneValidationMessage = ref(null)
    
    // Function to validate password match on focusout
    const validatePasswordMatch = () => {
      if (form.value.password && form.value.confirm_password) {
        passwordMismatchError.value = form.value.password !== form.value.confirm_password
      } else {
        passwordMismatchError.value = false
      }
    }
    
    // Function to validate WhatsApp number doesn't include dial code
    const validateWhatsAppNumber = () => {
      if (!form.value.whatsapp) {
        whatsappDialCodeError.value = false
        return
      }
      
      const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
      if (!selectedCountry) {
        whatsappDialCodeError.value = false
        return
      }
      
      const dialCode = selectedCountry.dial_code
      const whatsappInput = form.value.whatsapp.trim()
      
      // Check if input starts with dial code (+x or 00x format)
      const hasDialCode = whatsappInput.startsWith(dialCode) || 
                         whatsappInput.startsWith('00' + dialCode.substring(1))
      
      whatsappDialCodeError.value = hasDialCode
    }
    
    // Function to validate phone number and show message on blur
    const onWhatsAppBlur = () => {
      if (!form.value.whatsapp || !form.value.whatsapp.trim()) {
        phoneValidationMessage.value = null
        return
      }
      
      const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
      if (!selectedCountry) {
        phoneValidationMessage.value = null
        return
      }
      
      const fullPhoneNumber = selectedCountry.dial_code + form.value.whatsapp
      
      // Validate phone number
      const validation = validatePhoneNumber(form.value.whatsapp, selectedCountryCode.value)
      
      if (validation.isValid) {
        phoneValidationMessage.value = {
          type: 'success',
          title: t('auth.register.phoneValidation.valid'),
          fullNumber: fullPhoneNumber,
          error: null
        }
      } else {
        phoneValidationMessage.value = {
          type: 'error',
          title: t('auth.register.phoneValidation.invalid'),
          fullNumber: fullPhoneNumber,
          error: validation.error
        }
      }
    }
    
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

    const isFormValid = computed(() => {
      const baseValidation = form.value.first_name &&
             form.value.last_name &&
             form.value.whatsapp &&
             form.value.password &&
             form.value.confirm_password &&
             form.value.agreeToTerms &&
             form.value.password === form.value.confirm_password &&
             form.value.password.length >= 8 &&
             !whatsappDialCodeError.value
      
      // Add email validation if email field is required
      if (shouldShowEmailField.value) {
        return baseValidation && form.value.email
      }
      
      return baseValidation
    })

    
    // Load therapist registration settings to check email requirements
    const loadSettings = async () => {
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/therapist-registration-settings')
        if (response.data.success) {
          shouldShowEmailField.value = response.data.data.require_email === 1 || response.data.data.require_email === true
          otpMethod.value = response.data.data.otp_method || 'email'
        }
      } catch (error) {
        console.warn('Could not load registration settings, using defaults')
        shouldShowEmailField.value = false
        otpMethod.value = 'email'
      }
    }

    // Fetch countries from local JSON file (to avoid CORS issues)
    const loadCountries = async () => {
      try {
        isLoadingCountries.value = true
        const response = await fetch('/countries-codes-and-flags.json')
        
        if (!response.ok) {
          throw new Error('Failed to fetch countries')
        }
        
        const countriesData = await response.json()
        
        if (!Array.isArray(countriesData)) {
          throw new Error('Invalid countries data format')
        }
        
        // Sort by Arabic name (same as phone_input_cb function)
        const keyValues = countriesData.map(country => country.name_ar)
        countriesData.sort((a, b) => {
          const indexA = keyValues.indexOf(a.name_ar)
          const indexB = keyValues.indexOf(b.name_ar)
          return a.name_ar.localeCompare(b.name_ar)
        })
        
        // Reorder to put Egypt first, then Arab countries, then others
        const egyptIndex = countriesData.findIndex(c => c.country_code === 'EG')
        if (egyptIndex > 0) {
          const egypt = countriesData.splice(egyptIndex, 1)[0]
          countriesData.unshift(egypt)
        }
        
        // Add Arab countries after Egypt
        const arabCountries = ['SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO', 'LB', 'SY', 'IQ', 'YE', 'PS', 'MA', 'TN', 'DZ', 'LY', 'SD']
        const reorderedCountries = [countriesData[0]] // Start with Egypt
        
        // Add Arab countries in order
        arabCountries.forEach(code => {
          const index = countriesData.findIndex(c => c.country_code === code)
          if (index > 0) {
            reorderedCountries.push(countriesData[index])
          }
        })
        
        // Add remaining countries
        countriesData.forEach(country => {
          if (!reorderedCountries.includes(country)) {
            reorderedCountries.push(country)
          }
        })
        
        countries.value = reorderedCountries
        
      } catch (error) {
        console.error('Error loading countries:', error)
        // Fallback to basic countries if API fails
        countries.value = [
          { country_code: 'EG', name_ar: 'مصر', name_en: 'Egypt', dial_code: '+20', flag: '🇪🇬' },
          { country_code: 'SA', name_ar: 'السعودية', name_en: 'Saudi Arabia', dial_code: '+966', flag: '🇸🇦' },
          { country_code: 'AE', name_ar: 'الإمارات', name_en: 'UAE', dial_code: '+971', flag: '🇦🇪' },
          { country_code: 'US', name_ar: 'الولايات المتحدة', name_en: 'United States', dial_code: '+1', flag: '🇺🇸' },
          { country_code: 'GB', name_ar: 'بريطانيا', name_en: 'United Kingdom', dial_code: '+44', flag: '🇬🇧' }
        ]
      } finally {
        isLoadingCountries.value = false
      }
    }
    
    // Get client IP from external service
    const getClientIP = async () => {
      try {
        const res = await fetch('https://api.ipify.org?format=json')
        const data = await res.json()
        return data.ip
      } catch (error) {
        return null
      }
    }

    // Auto-detect user country
    const detectUserCountry = async () => {
      try {
        isDetectingCountry.value = true
        
        // First, get the client IP from external service
        const clientIP = await getClientIP()
        
        // Add cache-busting parameter and IP parameter
        const timestamp = Date.now()
        const params = new URLSearchParams({
          t: timestamp.toString()
        })
        
        if (clientIP) {
          params.append('ip', clientIP)
        }
        
        const response = await api.get(`/wp-json/jalsah-ai/v1/user-country?${params.toString()}`)
        
        if (response.data && response.data.country_code) {
          const detectedCountry = response.data.country_code.toUpperCase()
          
          const countryExists = countries.value.find(c => c.country_code === detectedCountry)
          if (countryExists) {
            selectedCountryCode.value = detectedCountry
            userCountryCode.value = detectedCountry
          }
        }
      } catch (error) {
        // Silent fallback to default
      } finally {
        isDetectingCountry.value = false
      }
    }
    

    // Phone validation function
    const validatePhoneNumber = (phoneNumber, countryCode) => {
      const country = countries.value.find(c => c.country_code === countryCode)
      if (!country || !country.validation_pattern) {
        return { isValid: true, error: null } // Skip validation if no pattern
      }
      
      const fullPhoneNumber = country.dial_code + phoneNumber
      const pattern = new RegExp(country.validation_pattern)
      
      if (!pattern.test(fullPhoneNumber)) {
        return { 
          isValid: false, 
          error: `${t('auth.register.invalidPhoneFormat')} ${country.name_en}` 
        }
      }
      
      return { isValid: true, error: null }
    }

    const handleRegister = async () => {
      if (!isFormValid.value) {
        return
      }

      // Get selected country info
      const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
      const fullWhatsAppNumber = selectedCountry ? selectedCountry.dial_code + form.value.whatsapp : form.value.whatsapp

      // Validate phone number before proceeding
      const phoneValidation = validatePhoneNumber(form.value.whatsapp, selectedCountryCode.value)
      if (!phoneValidation.isValid) {
        console.log('🔍 Phone validation failed:', phoneValidation.error)
        toast.error(phoneValidation.error)
        return
      }

      // Debug: Log the phone number being sent
      console.log('📱 Frontend Debug - Phone Number:', {
        selectedCountry: selectedCountry,
        dialCode: selectedCountry?.dial_code,
        userInput: form.value.whatsapp,
        fullNumber: fullWhatsAppNumber,
        otpMethod: otpMethod.value,
        validationPassed: true
      })

      const registrationData = {
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        whatsapp: fullWhatsAppNumber,
        password: form.value.password
      }
      
      // Add email only if required
      if (shouldShowEmailField.value && form.value.email) {
        registrationData.email = form.value.email
      }
      
      // Add country name for backend compatibility
      if (selectedCountry) {
        registrationData.country = selectedCountry.name_en
      }

      const result = await authStore.register(registrationData, otpMethod.value)
      
      if (result && result.requiresVerification) {
        // Redirect to verification page using contact method from backend response
        const contact = result.contact || form.value.email || fullWhatsAppNumber
        const routeName = otpMethod.value === 'whatsapp' ? 'WhatsAppVerification' : 'EmailVerification'
        router.push({
          name: routeName,
          params: { contact: encodeURIComponent(contact) },
          query: { method: otpMethod.value }
        })
      } else if (result && !result.requiresVerification) {
        // Redirect to homepage after successful registration
        router.push('/')
      }
    }
    
    // Country dropdown methods
    const toggleCountryDropdown = () => {
      showCountryDropdown.value = !showCountryDropdown.value
      if (showCountryDropdown.value) {
        countrySearch.value = ''
      }
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
    
    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        showCountryDropdown.value = false
      }
    }
    
    // Initialize on mount
    onMounted(async () => {
      await Promise.all([
        loadSettings(),
        loadCountries(),
        detectUserCountry()
      ])
      
      // Add click outside listener
      document.addEventListener('click', handleClickOutside)
    })
    
    // Cleanup on unmount
    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      form,
      loading,
      isFormValid,
      handleRegister,
      selectedCountryCode,
      countries,
      shouldShowEmailField,
      otpMethod,
      passwordMismatchError,
      whatsappDialCodeError,
      phoneValidationMessage,
      validatePasswordMatch,
      validateWhatsAppNumber,
      onWhatsAppBlur,
      showCountryDropdown,
      countrySearch,
      filteredCountries,
      isDetectingCountry,
      isLoadingCountries,
      toggleCountryDropdown,
      selectCountry,
      getSelectedCountryFlag,
      getSelectedCountryDial,
      getClientIP,
      detectUserCountry
    }
  }
}
</script> 

<style scoped>
/* Emoji fonts are imported globally in style.css */

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

/* Flexbox RTL Layout for Name Fields */
.name-fields-container[dir="rtl"] {
  flex-direction: row-reverse;
}

</style> 