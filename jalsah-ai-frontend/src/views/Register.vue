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
      
      <!-- Dummy Data Button -->
      <div class="mt-4 text-center">
        <button
          type="button"
          @click="fillDummyData"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Fill Dummy Data
        </button>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="card">
        <form @submit.prevent="handleRegister" class="space-y-6" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
          <!-- Name Fields -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="last_name" class="form-label">{{ $t('auth.register.lastName') }}</label>
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
            <div>
              <label for="first_name" class="form-label">{{ $t('auth.register.firstName') }}</label>
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
          </div>

          <!-- Age -->
          <div>
            <label for="age" class="form-label">{{ $t('auth.register.age') }}</label>
            <input
              id="age"
              v-model="form.age"
              type="number"
              min="13"
              max="120"
              required
              class="input-field"
              :placeholder="$t('auth.register.agePlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="on"
            />
          </div>

          <!-- Email (conditional based on settings) -->
          <div v-if="shouldShowEmailField">
            <label for="email" class="form-label">{{ $t('auth.register.email') }}</label>
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
            <div class="flex items-center justify-between mb-2">
              <label for="whatsapp" class="form-label">{{ $t('auth.register.whatsapp') }}</label>
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="detectUserCountry"
                  class="text-xs text-primary-600 hover:text-primary-500 underline"
                  title="Refresh country detection"
                >
                  🔄 Refresh Country
                </button>
                <button
                  type="button"
                  @click="testIpDetection"
                  class="text-xs text-blue-600 hover:text-blue-500 underline"
                  title="Test IP detection"
                >
                  🧪 Test IP
                </button>
              </div>
            </div>
            <div class="flex" style="direction: ltr;">
              <!-- Custom Country Selector -->
              <div class="relative flex-shrink-0">
                <button
                  type="button"
                  @click="toggleCountryDropdown"
                  class="w-32 px-3 py-2 border border-gray-300 rounded-l-md bg-gray-50 text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 flex items-center justify-between"
                  style="font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;"
                >
                  <span class="flex items-center">
                    <span class="text-lg mr-1">{{ getSelectedCountryFlag() }}</span>
                    <span class="text-xs">{{ getSelectedCountryDial() }}</span>
                  </span>
                  <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div
                  v-if="showCountryDropdown"
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
                      <span class="text-lg mr-3">{{ country.flag }}</span>
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
                class="flex-1 input-field rounded-l-none border-l-0"
                :placeholder="$t('auth.register.whatsappPlaceholder')"
                dir="ltr"
                autocomplete="tel"
                style="text-align: left; direction: ltr;"
              />
            </div>
          </div>


          <!-- Password -->
          <div>
            <label for="password" class="form-label">{{ $t('auth.register.password') }}</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="input-field"
              :placeholder="$t('auth.register.createPassword')"
              minlength="8"
            />
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('auth.register.passwordHint') }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="confirm_password" class="form-label">{{ $t('auth.register.confirmPassword') }}</label>
            <input
              id="confirm_password"
              v-model="form.confirm_password"
              type="password"
              required
              class="input-field"
              :placeholder="$t('auth.register.confirmPasswordPlaceholder')"
            />
            <div v-if="passwordMismatchError" class="mt-1 text-sm text-red-600">
              {{ $t('auth.register.passwordMismatch') }}
            </div>
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

        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">{{ $t('auth.register.orContinueWith') }}</span>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <button
              type="button"
              class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              <span class="ml-2">{{ $t('auth.register.google') }}</span>
            </button>

            <button
              type="button"
              class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
              <span class="ml-2">{{ $t('auth.register.facebook') }}</span>
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
import { useAuthStore } from '@/stores/auth'
import { useTherapistRegistrationStore } from '@/stores/therapistRegistration'
import api from '@/services/api'

export default {
  name: 'Register',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const therapistRegStore = useTherapistRegistrationStore()
    
    const form = ref({
      first_name: '',
      last_name: '',
      age: '',
      email: '',
      whatsapp: '',
      password: '',
      confirm_password: '',
      agreeToTerms: false
    })
    
    const selectedCountryCode = ref('EG')
    const shouldShowEmailField = ref(true)
    const userCountryCode = ref('EG')
    const showCountryDropdown = ref(false)
    const countrySearch = ref('')
    
    // Country codes with flags - Egypt first, then Arab countries, then alphabetical
    const countryCodesWithFlags = ref([
      // Egypt first
      { code: 'EG', name: 'Egypt', dial: '+20', flag: '🇪🇬' },
      // Arab countries
      { code: 'SA', name: 'Saudi Arabia', dial: '+966', flag: '🇸🇦' },
      { code: 'AE', name: 'UAE', dial: '+971', flag: '🇦🇪' },
      { code: 'KW', name: 'Kuwait', dial: '+965', flag: '🇰🇼' },
      { code: 'QA', name: 'Qatar', dial: '+974', flag: '🇶🇦' },
      { code: 'BH', name: 'Bahrain', dial: '+973', flag: '🇧🇭' },
      { code: 'OM', name: 'Oman', dial: '+968', flag: '🇴🇲' },
      { code: 'JO', name: 'Jordan', dial: '+962', flag: '🇯🇴' },
      { code: 'LB', name: 'Lebanon', dial: '+961', flag: '🇱🇧' },
      { code: 'SY', name: 'Syria', dial: '+963', flag: '🇸🇾' },
      { code: 'IQ', name: 'Iraq', dial: '+964', flag: '🇮🇶' },
      { code: 'YE', name: 'Yemen', dial: '+967', flag: '🇾🇪' },
      { code: 'PS', name: 'Palestine', dial: '+970', flag: '🇵🇸' },
      { code: 'MA', name: 'Morocco', dial: '+212', flag: '🇲🇦' },
      { code: 'TN', name: 'Tunisia', dial: '+216', flag: '🇹🇳' },
      { code: 'DZ', name: 'Algeria', dial: '+213', flag: '🇩🇿' },
      { code: 'LY', name: 'Libya', dial: '+218', flag: '🇱🇾' },
      { code: 'SD', name: 'Sudan', dial: '+249', flag: '🇸🇩' },
      // Other countries alphabetically
      { code: 'AF', name: 'Afghanistan', dial: '+93', flag: '🇦🇫' },
      { code: 'AL', name: 'Albania', dial: '+355', flag: '🇦🇱' },
      { code: 'AR', name: 'Argentina', dial: '+54', flag: '🇦🇷' },
      { code: 'AU', name: 'Australia', dial: '+61', flag: '🇦🇺' },
      { code: 'AT', name: 'Austria', dial: '+43', flag: '🇦🇹' },
      { code: 'BD', name: 'Bangladesh', dial: '+880', flag: '🇧🇩' },
      { code: 'BE', name: 'Belgium', dial: '+32', flag: '🇧🇪' },
      { code: 'BR', name: 'Brazil', dial: '+55', flag: '🇧🇷' },
      { code: 'CA', name: 'Canada', dial: '+1', flag: '🇨🇦' },
      { code: 'CN', name: 'China', dial: '+86', flag: '🇨🇳' },
      { code: 'FR', name: 'France', dial: '+33', flag: '🇫🇷' },
      { code: 'DE', name: 'Germany', dial: '+49', flag: '🇩🇪' },
      { code: 'IN', name: 'India', dial: '+91', flag: '🇮🇳' },
      { code: 'ID', name: 'Indonesia', dial: '+62', flag: '🇮🇩' },
      { code: 'IR', name: 'Iran', dial: '+98', flag: '🇮🇷' },
      { code: 'IT', name: 'Italy', dial: '+39', flag: '🇮🇹' },
      { code: 'JP', name: 'Japan', dial: '+81', flag: '🇯🇵' },
      { code: 'MY', name: 'Malaysia', dial: '+60', flag: '🇲🇾' },
      { code: 'MX', name: 'Mexico', dial: '+52', flag: '🇲🇽' },
      { code: 'NL', name: 'Netherlands', dial: '+31', flag: '🇳🇱' },
      { code: 'PK', name: 'Pakistan', dial: '+92', flag: '🇵🇰' },
      { code: 'RU', name: 'Russia', dial: '+7', flag: '🇷🇺' },
      { code: 'SG', name: 'Singapore', dial: '+65', flag: '🇸🇬' },
      { code: 'ZA', name: 'South Africa', dial: '+27', flag: '🇿🇦' },
      { code: 'KR', name: 'South Korea', dial: '+82', flag: '🇰🇷' },
      { code: 'ES', name: 'Spain', dial: '+34', flag: '🇪🇸' },
      { code: 'TH', name: 'Thailand', dial: '+66', flag: '🇹🇭' },
      { code: 'TR', name: 'Turkey', dial: '+90', flag: '🇹🇷' },
      { code: 'GB', name: 'United Kingdom', dial: '+44', flag: '🇬🇧' },
      { code: 'US', name: 'United States', dial: '+1', flag: '🇺🇸' },
      { code: 'VN', name: 'Vietnam', dial: '+84', flag: '🇻🇳' }
    ])

    const loading = computed(() => authStore.loading)
    
    // Password mismatch error - only show when passwords have same length but don't match
    const passwordMismatchError = computed(() => {
      return form.value.password &&
             form.value.confirm_password &&
             form.value.password.length === form.value.confirm_password.length &&
             form.value.password !== form.value.confirm_password
    })
    
    // Filtered countries based on search
    const filteredCountries = computed(() => {
      if (!countrySearch.value) {
        return countryCodesWithFlags.value
      }
      return countryCodesWithFlags.value.filter(country => 
        country.name.toLowerCase().includes(countrySearch.value.toLowerCase()) ||
        country.dial.includes(countrySearch.value) ||
        country.code.toLowerCase().includes(countrySearch.value.toLowerCase())
      )
    })

    const isFormValid = computed(() => {
      const baseValidation = form.value.first_name &&
                           form.value.last_name &&
                           form.value.age &&
                           form.value.whatsapp &&
                           form.value.password &&
                           form.value.confirm_password &&
                           form.value.agreeToTerms &&
                           form.value.password === form.value.confirm_password &&
                           form.value.password.length >= 8
      
      // Add email validation if email field is required
      if (shouldShowEmailField.value) {
        return baseValidation && form.value.email
      }
      
      return baseValidation
    })

    const fillDummyData = () => {
      form.value = {
        first_name: 'Maki',
        last_name: 'Omar',
        age: '25',
        email: 'maki3omar@gmail.com',
        whatsapp: '1234567890',
        password: 'TestPassword123!',
        confirm_password: 'TestPassword123!',
        agreeToTerms: true
      }
      selectedCountryCode.value = 'EG'
    }
    
    // Load therapist registration settings to check email requirements
    const loadSettings = async () => {
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/therapist-registration-settings')
        if (response.data.success) {
          shouldShowEmailField.value = response.data.data.require_email || false
        }
      } catch (error) {
        console.warn('Could not load registration settings, using defaults')
        shouldShowEmailField.value = true
      }
    }
    
    // Get client IP from external service
    const getClientIP = async () => {
      try {
        console.log('🌐 Fetching client IP from external service...')
        const res = await fetch('https://api.ipify.org?format=json')
        const data = await res.json()
        console.log('🌐 Client IP from ipify:', data.ip)
        return data.ip
      } catch (error) {
        console.warn('⚠️ Could not fetch client IP:', error)
        return null
      }
    }

    // Auto-detect user country
    const detectUserCountry = async () => {
      try {
        console.log('🌍 Detecting user country from API...')
        
        // First, get the client IP from external service
        const clientIP = await getClientIP()
        
        // Add cache-busting parameter and IP parameter
        const timestamp = Date.now()
        const params = new URLSearchParams({
          t: timestamp.toString()
        })
        
        if (clientIP) {
          params.append('ip', clientIP)
          console.log('📤 Sending client IP to backend:', clientIP)
        }
        
        const response = await api.get(`/wp-json/jalsah-ai/v1/user-country?${params.toString()}`)
        console.log('📍 User country API response:', response.data)
        
        // Log debug information if available
        if (response.data.debug_info) {
          console.log('🔍 Debug Info:', response.data.debug_info)
          console.log('🌐 Custom IP:', response.data.debug_info.custom_ip)
          console.log('🌐 Detected IP:', response.data.debug_info.detected_ip)
          console.log('🖥️ Remote Addr:', response.data.debug_info.remote_addr)
          console.log('🔧 Raw Country Code:', response.data.debug_info.raw_country_code)
        }
        
        if (response.data && response.data.country_code) {
          const detectedCountry = response.data.country_code.toUpperCase()
          console.log('🎯 Detected country code:', detectedCountry)
          
          const countryExists = countryCodesWithFlags.value.find(c => c.code === detectedCountry)
          if (countryExists) {
            console.log('✅ Country found in list:', countryExists)
            selectedCountryCode.value = detectedCountry
            userCountryCode.value = detectedCountry
            console.log('🔄 Updated selected country to:', detectedCountry)
          } else {
            console.log('❌ Country not found in list, using default (Egypt)')
          }
        } else {
          console.log('❌ No country code in response, using default (Egypt)')
        }
      } catch (error) {
        console.warn('⚠️ Could not detect user country, using default (Egypt):', error)
      }
    }
    
    // Test IP detection function
    const testIpDetection = async () => {
      try {
        console.log('🧪 Testing IP detection...')
        const response = await api.get('/wp-json/jalsah-ai/v1/test-ip')
        console.log('🧪 IP Test Response:', response.data)
        
        if (response.data && response.data.data) {
          const data = response.data.data
          console.log('🌐 Detected IP:', data.detected_ip)
          console.log('📍 IP Source:', data.ip_source)
          console.log('🏳️ Country Code:', data.country_code)
          console.log('🔗 API URL:', data.api_url)
          console.log('📋 All Headers:', data.all_headers)
          
          // Show alert with results
          alert(`IP Detection Test Results:
          
Detected IP: ${data.detected_ip}
IP Source: ${data.ip_source}
Country Code: ${data.country_code}
API URL: ${data.api_url}

Check console for full details.`)
        }
      } catch (error) {
        console.error('🧪 IP Test Error:', error)
        alert('IP Test failed. Check console for details.')
      }
    }
    
    const handleRegister = async () => {
      if (!isFormValid.value) {
        return
      }
      
      // Get selected country info
      const selectedCountry = countryCodesWithFlags.value.find(c => c.code === selectedCountryCode.value)
      const fullWhatsAppNumber = selectedCountry ? selectedCountry.dial + form.value.whatsapp : form.value.whatsapp

      const registrationData = {
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        age: parseInt(form.value.age),
        whatsapp: fullWhatsAppNumber,
        password: form.value.password
      }
      
      // Add email only if required
      if (shouldShowEmailField.value && form.value.email) {
        registrationData.email = form.value.email
      }
      
      // Add country name for backend compatibility
      if (selectedCountry) {
        registrationData.country = selectedCountry.name
      }

      const result = await authStore.register(registrationData)
      
      if (result && result.requiresVerification) {
        // Redirect to verification page
        const email = form.value.email || fullWhatsAppNumber
        router.push(`/verify-email/${encodeURIComponent(email)}`)
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
      const country = countryCodesWithFlags.value.find(c => c.code === selectedCountryCode.value)
      return country ? country.flag : '🇪🇬'
    }
    
    const getSelectedCountryDial = () => {
      const country = countryCodesWithFlags.value.find(c => c.code === selectedCountryCode.value)
      return country ? country.dial : '+20'
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
      fillDummyData,
      selectedCountryCode,
      countryCodesWithFlags,
      shouldShowEmailField,
      passwordMismatchError,
      showCountryDropdown,
      countrySearch,
      filteredCountries,
      toggleCountryDropdown,
      selectCountry,
      getSelectedCountryFlag,
      getSelectedCountryDial,
      getClientIP,
      detectUserCountry,
      testIpDetection
    }
  }
}
</script> 