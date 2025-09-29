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
          <!-- Contact Input (if not provided) -->
          <div v-if="!contact" class="space-y-4">
            <!-- Email Input -->
            <div v-if="requireEmail">
              <label for="contact" class="form-label">
                {{ $t('auth.login.email') }}
              </label>
              <input
                id="contact"
                v-model="contactInput"
                type="email"
                required
                class="input-field"
                :placeholder="$t('auth.login.emailPlaceholder')"
                :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              />
            </div>
            
            <!-- WhatsApp Input with Country Selector -->
            <div v-else>
              <label for="contact" class="form-label">
                {{ $t('auth.login.whatsapp') }}
              </label>
              <div class="flex" style="direction: ltr;">
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
                  id="contact"
                  v-model="contactInput"
                  type="tel"
                  required
                  class="flex-1 px-3 py-3 border border-gray-300 rounded-l-md rounded-r-none border-r-0 focus:outline-none focus:ring-primary-500 focus:border-primary-500 h-12"
                  :placeholder="$t('auth.login.whatsappPlaceholder')"
                  dir="ltr"
                  style="text-align: left; direction: ltr;"
                />
              </div>
            </div>
            
            <button
              type="button"
              @click="setContact"
              :disabled="updatingPhone"
              class="w-full btn-primary py-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="updatingPhone" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('verification.updatingPhone') }}
              </span>
              <span v-else>{{ $t('verification.continue') }}</span>
            </button>
          </div>

          <!-- Contact Display (if provided) -->
          <div v-else class="text-center">
            <p class="text-sm text-gray-600">
              {{ verificationMethod === 'whatsapp' ? $t('verification.whatsappSentTo') : $t('verification.emailSentTo') }}
            </p>
            <p class="text-lg font-medium text-gray-900" :dir="verificationMethod === 'whatsapp' ? 'ltr' : 'auto'" :style="verificationMethod === 'whatsapp' ? 'text-align: center; direction: ltr;' : ''">{{ contact }}</p>
            <button
              type="button"
              @click="changeContact"
              class="mt-2 text-sm text-primary-600 hover:text-primary-500"
            >
              {{ $t('verification.changeContact') }}
            </button>
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
import { useTherapistRegistrationStore } from '@/stores/therapistRegistration'
import { useToast } from 'vue-toastification'
import Swal from 'sweetalert2'

export default {
  name: 'EmailVerification',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const { t, locale } = useI18n()
    const authStore = useAuthStore()
    const toast = useToast()
    
    const form = ref({
      verification_code: ''
    })

    const loading = ref(false)
    const resendLoading = ref(false)
    const resendCooldown = ref(0)
    const updatingPhone = ref(false)
    const contact = ref('')
    const contactInput = ref('')
    const verificationMethod = ref('email')
    const countdownInterval = ref(null)
    const therapistRegistrationStore = useTherapistRegistrationStore()
    
    // Country selector variables
    const selectedCountryCode = ref('EG')
    const showCountryDropdown = ref(false)
    const countrySearch = ref('')
    const isDetectingCountry = ref(false)
    const countries = ref([])
    const isLoadingCountries = ref(false)

    const isFormValid = computed(() => {
      return form.value.verification_code.length === 6
    })

    const requireEmail = computed(() => {
      return therapistRegistrationStore.shouldShowEmail
    })
    
    // Country selector computed properties
    const localizedCountries = computed(() => {
      const isArabic = locale.value === 'ar'
      return countries.value.map(country => ({
        ...country,
        name: isArabic ? country.name_ar : country.name_en,
        code: country.country_code,
        dial: country.dial_code,
        flag: country.flag
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

    // Enhanced phone validation function with detailed error messages
    const validatePhoneNumber = (phoneNumber, countryCode) => {
      const country = countries.value.find(c => c.country_code === countryCode)
      if (!country || !country.validation_pattern) {
        return { isValid: true, error: null } // Skip validation if no pattern
      }
      
      // Clean the phone number (remove spaces, dashes, etc.)
      let cleanPhoneNumber = phoneNumber.replace(/[\s\-\(\)]/g, '')
      
      // Check for invalid characters (only digits should be allowed)
      if (!/^\d+$/.test(cleanPhoneNumber)) {
        return {
          isValid: false,
          error: t('auth.register.phoneValidation.invalidCharacters')
        }
      }
      
      // Check if number starts with 0 (common mistake)
      if (cleanPhoneNumber.startsWith('0')) {
        return {
          isValid: false,
          error: t('auth.register.phoneValidation.startsWithZero')
        }
      }
      
      // Get expected length based on country
      let expectedLength = 10 // default
      switch (countryCode) {
        case 'SA':
        case 'AE':
          expectedLength = 9
          break
        case 'EG':
          expectedLength = 10
          break
        case 'US':
        case 'CA':
          expectedLength = 10
          break
      }
      
      // Check length constraints with specific messages
      if (cleanPhoneNumber.length !== expectedLength) {
        return {
          isValid: false,
          error: t('auth.register.phoneValidation.invalidLength', { 
            expected: expectedLength, 
            actual: cleanPhoneNumber.length 
          })
        }
      }
      
      const fullPhoneNumber = country.dial_code + cleanPhoneNumber
      const pattern = new RegExp(country.validation_pattern)
      
      if (!pattern.test(fullPhoneNumber)) {
        // Get specific error message based on country
        let specificError = t('auth.register.phoneValidation.specificErrors.default')
        
        switch (countryCode) {
          case 'SA':
            specificError = t('auth.register.phoneValidation.specificErrors.saudiArabia')
            break
          case 'AE':
            specificError = t('auth.register.phoneValidation.specificErrors.uae')
            break
          case 'EG':
            specificError = t('auth.register.phoneValidation.specificErrors.egypt')
            break
        }
        
        return { 
          isValid: false, 
          error: specificError
        }
      }
      
      return { isValid: true, error: null }
    }

    const setContact = async () => {
      if (!contactInput.value) return
      
      if (requireEmail.value) {
        // Email method - no need to check if user exists for email
        contact.value = contactInput.value
        verificationMethod.value = 'email'
      } else {
        // Validate phone number before proceeding
        const phoneValidation = validatePhoneNumber(contactInput.value, selectedCountryCode.value)
        if (!phoneValidation.isValid) {
          toast.error(phoneValidation.error)
          return
        }
        
        // WhatsApp method - allow phone number updates without existence check
        const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
        const fullWhatsAppNumber = selectedCountry ? selectedCountry.dial_code + contactInput.value : contactInput.value
        
        // Show confirmation dialog before updating phone number
        const result = await Swal.fire({
          title: t('verification.confirmPhoneUpdate'),
          html: t('verification.confirmPhoneUpdateText', { phone: `<span dir="ltr" style="direction: ltr; text-align: left; font-family: monospace;">${fullWhatsAppNumber}</span>` }),
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: t('common.confirm'),
          cancelButtonText: t('common.cancel')
        })
        
        if (result.isConfirmed) {
          updatingPhone.value = true
          
          try {
            // Set the contact value
            contact.value = fullWhatsAppNumber
            verificationMethod.value = 'whatsapp'
            
            // Immediately send verification code to update phone number in database
            const success = await authStore.resendVerification(fullWhatsAppNumber)
            if (success) {
              toast.success(t('verification.resetCodeSent'))
              // Start countdown to prevent immediate resend
              startResendCooldown()
            }
          } catch (error) {
            console.error('Error sending verification code:', error)
            
            // Reset contact value on error
            contact.value = ''
            verificationMethod.value = 'email'
            
            // Check if it's the specific "WhatsApp already verified" error
            if (error.response?.data?.error === 'WhatsApp number is already verified') {
              toast.error(t('verification.whatsappAlreadyVerified'))
            } else {
              toast.error(t('verification.resendFailed'))
            }
          } finally {
            updatingPhone.value = false
          }
        }
      }
    }

    const changeContact = () => {
      contact.value = ''
      contactInput.value = ''
      form.value.verification_code = ''
    }

    // Get contact info from route params, query, or localStorage
    onMounted(async () => {
      // Load therapist registration settings
      await therapistRegistrationStore.loadSettings()
      
      // Load countries for country selector
      await loadCountries()
      
      // Add click outside listener
      document.addEventListener('click', handleClickOutside)
      
      // Check for identifier from login form
      const identifier = route.query.identifier
      if (identifier) {
        contact.value = decodeURIComponent(identifier)
        // Determine verification method based on identifier format
        verificationMethod.value = identifier.includes('@') ? 'email' : 'whatsapp'
      } else {
        const contactParam = route.params.contact || localStorage.getItem('pending_verification_contact') || ''
        contact.value = contactParam ? decodeURIComponent(contactParam) : ''
        verificationMethod.value = route.query.method || 'email'
      }
      
      // Don't redirect to register if no contact - let user enter it
      
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
      // Remove click outside listener
      document.removeEventListener('click', handleClickOutside)
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
        // Determine the identifier field based on verification method
        const verificationData = {
          code: form.value.verification_code
        }
        
        if (verificationMethod.value === 'whatsapp') {
          verificationData.whatsapp = contact.value
        } else {
          verificationData.email = contact.value
        }
        
        const response = await authStore.verifyEmail(verificationData)
        
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
        // Ensure we have a valid contact value
        if (!contact.value) {
          console.error('❌ No contact value available for resend')
          toast.error(t('verification.noContactToResend'))
          return
        }
        
        const response = await authStore.resendVerification(contact.value)
        
        if (response) {
          toast.success(t('toast.auth.verificationSent'))
          startResendCooldown()
        }
      } catch (error) {
        console.error('Resend error:', error)
        toast.error(t('verification.resendFailed'))
      } finally {
        resendLoading.value = false
      }
    }
    
    // Country selector functions
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
    
    // Load countries from local JSON file
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
        
        // Sort by Arabic name
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
    
    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.relative')) {
        showCountryDropdown.value = false
      }
    }

    return {
      form,
      loading,
      resendLoading,
      resendCooldown,
      updatingPhone,
      contact,
      contactInput,
      verificationMethod,
      requireEmail,
      isFormValid,
      setContact,
      changeContact,
      handleVerification,
      resendCode,
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
      loadCountries,
      handleClickOutside
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
</style>
