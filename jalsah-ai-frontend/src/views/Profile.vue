<template>
  <div>

    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Profile page -->
      <h1 class="text-3xl text-gray-900 mb-8">{{ $t('profile.title') }}</h1>

      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">{{ $t('profile.loading') }}</p>
      </div>
      <div v-else class="grid md:grid-cols-3 gap-8">
        <!-- Profile Information -->
        <div class="md:col-span-2">
          <div class="card">
            <h2 class="text-xl text-gray-900 mb-6">{{ $t('profile.personalInfo') }}</h2>
            
            <form @submit.prevent="updateProfile" class="space-y-6">
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="form-label">{{ $t('profile.lastName') }}</label>
                  <input 
                    v-model="profile.lastName" 
                    type="text" 
                    class="input-field"
                    required
                  />
                </div>
                <div>
                  <label class="form-label">{{ $t('profile.firstName') }}</label>
                  <input 
                    v-model="profile.firstName" 
                    type="text" 
                    class="input-field"
                    required
                  />
                </div>
              </div>

              <div v-if="showEmailField">
                <label class="form-label">{{ $t('profile.email') }}</label>
                <input 
                  v-model="profile.email" 
                  type="email" 
                  class="input-field"
                  required
                />
              </div>

              <div>
                <label class="form-label">{{ $t('profile.whatsapp') }}</label>
                <div class="flex" style="direction: ltr;">
                  <!-- Country Dial Code Selector -->
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
                          :placeholder="$t('profile.searchCountries')"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                          @click.stop
                        />
                      </div>
                      <div class="max-h-48 overflow-y-auto">
                        <button
                          v-for="country in filteredCountries"
                          :key="country.country_code"
                          type="button"
                          @click="selectCountry(country)"
                          class="w-full px-3 py-2 text-left hover:bg-gray-50 flex items-center text-sm"
                        >
                          <span class="text-lg mr-2 emoji-flag">{{ country.flag }}</span>
                          <span class="flex-1">{{ country.name_en }}</span>
                          <span class="text-gray-500 text-xs">{{ country.dial_code }}</span>
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- WhatsApp Input -->
                  <input
                    v-model="profile.whatsapp"
                    type="tel"
                    required
                    dir="ltr"
                    class="flex-1 px-3 py-3 border border-gray-300 rounded-l-md rounded-r-none border-r-0 focus:outline-none focus:ring-primary-500 focus:border-primary-500 h-12"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': whatsappDialCodeError }"
                    :placeholder="$t('profile.whatsappPlaceholder')"
                    autocomplete="tel"
                    style="text-align: left; direction: ltr;"
                    @input="onWhatsAppInput"
                    @blur="onWhatsAppBlur"
                  />
                </div>
                
                <!-- Phone Validation Message (same UX as registration) -->
                <div v-if="phoneValidationMessage" class="mt-2 text-sm" :class="phoneValidationMessage.type === 'error' ? 'text-red-600' : 'text-green-600'">
                  <div class="font-medium">{{ phoneValidationMessage.title }}</div>
                  <div class="font-mono text-xs mt-1" dir="ltr">{{ phoneValidationMessage.fullNumber }}</div>
                </div>

                <!-- WhatsApp Dial Code Error Message -->
                <div v-if="whatsappDialCodeError" class="mt-1 text-sm text-red-600">
                  {{ $t('auth.register.noNeedDialCode') }}
                </div>
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit" 
                  :disabled="updating"
                  class="btn-primary"
                >
                  <span v-if="updating" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $t('profile.updating') }}
                  </span>
                  <span v-else>{{ $t('profile.updateProfile') }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Change Password -->
          <div class="card mt-8">
            <h2 class="text-xl text-gray-900 mb-6">{{ $t('profile.changePassword') }}</h2>
            
            <form @submit.prevent="changePassword" class="space-y-6">
              <div>
                <label class="form-label">{{ $t('profile.currentPassword') }}</label>
                <input 
                  v-model="password.current" 
                  type="password" 
                  class="input-field"
                  required
                />
              </div>

              <div>
                <label class="form-label">{{ $t('profile.newPassword') }}</label>
                <input 
                  v-model="password.new" 
                  type="password" 
                  class="input-field"
                  required
                />
              </div>

              <div>
                <label class="form-label">{{ $t('profile.confirmNewPassword') }}</label>
                <input 
                  v-model="password.confirm" 
                  type="password" 
                  class="input-field"
                  required
                />
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit" 
                  :disabled="changingPassword"
                  class="btn-outline"
                >
                  <span v-if="changingPassword" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $t('profile.changing') }}
                  </span>
                  <span v-else>{{ $t('profile.changePasswordButton') }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="md:col-span-1">
          <!-- Account Summary -->
          <div class="card mt-8">
            <h2 class="text-xl text-gray-900 mb-6">{{ $t('profile.accountSummary') }}</h2>
            <div class="space-y-4">
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('profile.memberSince') }}</span>
                <span class="font-medium">{{ formatDate(profile.createdAt) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('profile.totalSessions') }}</span>
                <span class="font-medium">{{ profile.totalSessions }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">{{ $t('profile.accountStatus') }}</span>
                <span class="font-medium text-green-600">{{ $t('profile.active') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import { formatGregorianDate } from '@/utils/dateFormatter'
export default {
  name: 'Profile',
  setup() {
    const router = useRouter()
    const toast = useToast()
    const authStore = useAuthStore()
    const settingsStore = useSettingsStore()
    const { locale, t } = useI18n()
    
    const loading = ref(true)
    const updating = ref(false)
    const changingPassword = ref(false)
    
    const profile = ref({
      firstName: '',
      lastName: '',
      email: '',
      whatsapp: '',
      createdAt: '',
      totalSessions: 0
    })

    const password = ref({
      current: '',
      new: '',
      confirm: ''
    })

    // Country dial code functionality
    const selectedCountryCode = ref('EG')
    const showCountryDropdown = ref(false)
    const countrySearch = ref('')
    const isDetectingCountry = ref(false)
    const countries = ref([])
    const isLoadingCountries = ref(false)
    const whatsappDialCodeError = ref(false)
    const whatsappValidationError = ref('')
    const phoneValidationMessage = ref(null)
    const whatsappCountryCode = ref('')

    const loadProfile = async () => {
      loading.value = true
      try {
        const response = await api.get('/api/ai/profile')
        const userData = response.data.data
        
        profile.value = {
          firstName: userData.first_name || '',
          lastName: userData.last_name || '',
          email: userData.email || '',
          whatsapp: userData.whatsapp || '',
          createdAt: userData.created_at || '',
          totalSessions: userData.total_sessions || 0
        }
        
        // Set country code and WhatsApp number
        if (userData.whatsapp_country_code) {
          whatsappCountryCode.value = userData.whatsapp_country_code
          
          // Wait for countries to load if not already loaded
          if (countries.value.length === 0) {
            await loadCountries()
          }
          
          // Find country by dial code
          const country = countries.value.find(c => c.dial_code === userData.whatsapp_country_code)
          
          if (country) {
            selectedCountryCode.value = country.country_code
          } else {
            // Try to find by partial match
            const partialMatch = countries.value.find(c => c.dial_code.includes(userData.whatsapp_country_code) || userData.whatsapp_country_code.includes(c.dial_code))
            if (partialMatch) {
              selectedCountryCode.value = partialMatch.country_code
            }
          }
        }
      } catch (error) {
        toast.error('Failed to load profile')
        console.error('Error loading profile:', error)
      } finally {
        loading.value = false
      }
    }

    const updateProfile = async () => {
      updating.value = true
      
      try {
        const profileData = {
          first_name: profile.value.firstName,
          last_name: profile.value.lastName,
          email: profile.value.email,
          whatsapp: profile.value.whatsapp,
          whatsapp_country_code: whatsappCountryCode.value,
          phone: profile.value.whatsapp
        }

        await api.put('/api/ai/profile', profileData)
        
        toast.success('Profile updated successfully!')
        
      } catch (error) {
        toast.error('Failed to update profile')
        console.error('Error updating profile:', error)
      } finally {
        updating.value = false
      }
    }

    const changePassword = async () => {
      if (password.value.new !== password.value.confirm) {
        toast.error('New passwords do not match')
        return
      }

      changingPassword.value = true
      
      try {
        const passwordData = {
          current_password: password.value.current,
          new_password: password.value.new
        }

        await api.put('/api/ai/profile/password', passwordData)
        
        toast.success('Password changed successfully!')
        
        // Clear password fields
        password.value = {
          current: '',
          new: '',
          confirm: ''
        }
        
      } catch (error) {
        toast.error('Failed to change password')
        console.error('Error changing password:', error)
      } finally {
        changingPassword.value = false
      }
    }

    const logout = () => {
      authStore.logout()
      router.push('/login')
      toast.success('Logged out successfully')
    }

    const formatDate = (dateString) => {
      return formatGregorianDate(dateString, locale.value)
    }

    // Country dial code functions
    const loadCountries = async () => {
      if (countries.value.length > 0) return
      
      isLoadingCountries.value = true
      try {
        const response = await fetch('/countries-codes-and-flags.json')
        const data = await response.json()
        countries.value = data
      } catch (error) {
        console.error('Error loading countries:', error)
      } finally {
        isLoadingCountries.value = false
      }
    }

    const filteredCountries = computed(() => {
      if (!countrySearch.value) return countries.value.slice(0, 20)
      return countries.value.filter(country => 
        country.name_en.toLowerCase().includes(countrySearch.value.toLowerCase()) ||
        country.dial_code.includes(countrySearch.value)
      ).slice(0, 20)
    })

    // Check if email field should be shown based on registration settings
    const showEmailField = computed(() => {
      return settingsStore.settings?.require_email === true
    })

    const getSelectedCountryFlag = () => {
      const country = countries.value.find(c => c.country_code === selectedCountryCode.value)
      return country ? country.flag : '🇪🇬'
    }

    const getSelectedCountryDial = () => {
      const country = countries.value.find(c => c.country_code === selectedCountryCode.value)
      return country ? country.dial_code : '+20'
    }

    const toggleCountryDropdown = () => {
      showCountryDropdown.value = !showCountryDropdown.value
      if (showCountryDropdown.value) {
        loadCountries()
      }
    }

    const selectCountry = (country) => {
      selectedCountryCode.value = country.country_code
      whatsappCountryCode.value = country.dial_code
      showCountryDropdown.value = false
      countrySearch.value = ''
    }

    // Enhanced phone validation function with detailed error messages (mirrors Register.vue)
    const validatePhoneNumber = (phoneNumber, countryCode) => {
      const country = countries.value.find(c => c.country_code === countryCode)
      if (!country || !country.validation_pattern) {
        // Skip validation if no pattern (same as Register.vue)
        return { isValid: true, error: null }
      }

      // Remove any non-digit characters except +
      let cleanNumber = phoneNumber.replace(/[^\d+]/g, '')
      
      // Check if number starts with 0 (common mistake)
      if (cleanNumber.startsWith('0')) {
        return { isValid: false, error: t('auth.register.phoneValidation.startsWithZero') }
      }

      // Check for invalid characters (only digits should remain after cleaning)
      if (!/^\d+$/.test(cleanNumber)) {
        return { isValid: false, error: t('auth.register.phoneValidation.invalidCharacters') }
      }

      // Validate using country regex; error message from JSON (validation_message_en/ar) or generic default
      const fullPhoneNumber = country.dial_code + cleanNumber
      const pattern = new RegExp(country.validation_pattern)
      if (!pattern.test(fullPhoneNumber)) {
        const isArabic = locale.value === 'ar'
        const customMessage = isArabic ? country.validation_message_ar : country.validation_message_en
        const error = (customMessage && customMessage.trim()) ? customMessage : t('auth.register.phoneValidation.invalidFormatForCountry')
        return { isValid: false, error }
      }

      return { isValid: true, error: null }
    }

    const validateWhatsAppNumber = () => {
      const whatsappInput = (profile.value.whatsapp || '').trim()
      const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
      if (!selectedCountry) {
        whatsappDialCodeError.value = false
        phoneValidationMessage.value = null
        return
      }

      const dialCode = selectedCountry.dial_code
      const hasDialCode = whatsappInput.startsWith(dialCode) || whatsappInput.startsWith('00' + dialCode.substring(1))
      whatsappDialCodeError.value = !!hasDialCode

      // Show immediate message for leading zero
      if (whatsappInput.startsWith('0')) {
        const fullPhoneNumber = selectedCountry.dial_code + whatsappInput
        phoneValidationMessage.value = {
          type: 'error',
          title: t('auth.register.phoneValidation.startsWithZero'),
          fullNumber: fullPhoneNumber
        }
      } else {
        // Clear the message if it was about leading zero
        if (phoneValidationMessage.value && phoneValidationMessage.value.title === t('auth.register.phoneValidation.startsWithZero')) {
          phoneValidationMessage.value = null
        }
      }
    }

    const onWhatsAppBlur = () => {
      validateWhatsAppNumber()
      const whatsappInput = (profile.value.whatsapp || '').trim()
      if (!whatsappInput) {
        phoneValidationMessage.value = null
        return
      }

      const selectedCountry = countries.value.find(c => c.country_code === selectedCountryCode.value)
      if (!selectedCountry) {
        phoneValidationMessage.value = null
        return
      }

      const fullPhoneNumber = selectedCountry.dial_code + whatsappInput
      const validation = validatePhoneNumber(whatsappInput, selectedCountryCode.value)

      if (validation.isValid) {
        phoneValidationMessage.value = null
      } else {
        phoneValidationMessage.value = {
          type: 'error',
          title: validation.error,
          fullNumber: fullPhoneNumber
        }
      }
    }

    // Function to filter only numbers for WhatsApp input
    const onWhatsAppInput = (event) => {
      // Remove all non-numeric characters
      const numericValue = event.target.value.replace(/[^0-9]/g, '')
      
      // Update the form value
      profile.value.whatsapp = numericValue
      
      // Trigger validation
      validateWhatsAppNumber()
    }


    onMounted(() => {
      // Ensure countries are loaded so length validation works immediately
      loadCountries()
      loadProfile()
    })

    return {
      loading,
      updating,
      changingPassword,
      profile,
      password,
      updateProfile,
      changePassword,
      logout,
      formatDate,
      // Country dial code functionality
      selectedCountryCode,
      showCountryDropdown,
      countrySearch,
      isDetectingCountry,
      countries,
      isLoadingCountries,
      whatsappDialCodeError,
      whatsappValidationError,
      phoneValidationMessage,
      whatsappCountryCode,
      filteredCountries,
      getSelectedCountryFlag,
      getSelectedCountryDial,
      toggleCountryDropdown,
      selectCountry,
      validatePhoneNumber,
      validateWhatsAppNumber,
      onWhatsAppBlur,
      onWhatsAppInput,
      showEmailField
    }
  }
}
</script> 