<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        {{ $t('therapistRegister.title') }}
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ $t('therapistRegister.subtitle') }}
      </p>
    </div>
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="onSubmit" enctype="multipart/form-data">
          <div class="space-y-4">
            <div>
              <label class="form-label" for="name">{{ $t('therapistRegister.name') }}</label>
              <input v-model="form.name" id="name" type="text" required class="input-field" autocomplete="name" />
            </div>
            <div>
              <label class="form-label" for="name_en">{{ $t('therapistRegister.nameEn') }}</label>
              <input v-model="form.name_en" id="name_en" type="text" required class="input-field" autocomplete="name" />
            </div>
            <div v-if="registrationStore.shouldShowEmail">
              <label class="form-label" for="email">{{ $t('therapistRegister.email') }}</label>
              <input v-model="form.email" id="email" type="email" :required="registrationStore.shouldShowEmail" class="input-field" autocomplete="email" />
            </div>
            <div>
              <label class="form-label" for="phone">{{ $t('therapistRegister.phone') }}</label>
              <div v-if="registrationStore.shouldShowCountryDialCodes" class="phone-input-group">
                <select v-model="form.phone_country" class="country-code-select">
                  <option v-for="(country, code) in registrationStore.countryCodes" :key="code" :value="code">
                    {{ country.name }} {{ country.code }}
                  </option>
                </select>
                <input v-model="form.phone" id="phone" type="tel" required class="phone-number-input" autocomplete="tel" placeholder="123456789" @input="onPhoneInput" />
              </div>
              <input v-else v-model="form.phone" id="phone" type="tel" required class="input-field" autocomplete="tel" @input="onPhoneInput" />
            </div>
            <div>
              <label class="form-label" for="whatsapp">{{ $t('therapistRegister.whatsapp') }}</label>
              <div v-if="registrationStore.shouldShowCountryDialCodes" class="phone-input-group">
                <select v-model="form.whatsapp_country" class="country-code-select">
                  <option v-for="(country, code) in registrationStore.countryCodes" :key="code" :value="code">
                    {{ country.name }} {{ country.code }}
                  </option>
                </select>
                <input v-model="form.whatsapp" id="whatsapp" type="tel" required class="phone-number-input" autocomplete="tel" placeholder="123456789" @input="onWhatsAppInput" />
              </div>
              <input v-else v-model="form.whatsapp" id="whatsapp" type="tel" required class="input-field" autocomplete="tel" @input="onWhatsAppInput" />
            </div>
            <div>
              <label class="form-label" for="doctor_specialty">{{ $t('therapistRegister.specialty') }}</label>
              <input v-model="form.doctor_specialty" id="doctor_specialty" type="text" required class="input-field" />
            </div>
            <FancyUpload
              v-model="form.profile_image"
              :label="$t('therapistRegister.profileImage')"
              id="profile_image"
              accept="image/*"
              :multiple="false"
              :buttonText="$t('therapistRegister.profileImage')"
            />
            <FancyUpload
              v-model="form.identity_front"
              :label="$t('therapistRegister.identityFront')"
              id="identity_front"
              accept="image/*"
              :multiple="false"
              :buttonText="$t('therapistRegister.identityFront')"
            />
            <FancyUpload
              v-model="form.identity_back"
              :label="$t('therapistRegister.identityBack')"
              id="identity_back"
              accept="image/*"
              :multiple="false"
              :buttonText="$t('therapistRegister.identityBack')"
            />
            <FancyUpload
              v-model="form.certificates"
              :label="$t('therapistRegister.certificates')"
              id="certificates"
              accept="image/*,application/pdf"
              :multiple="true"
              :buttonText="$t('therapistRegister.certificates')"
            />
            <div v-if="passwordMode === 'user'">
              <label class="form-label" for="password">{{ $t('therapistRegister.password') }}</label>
              <input v-model="form.password" id="password" type="password" required class="input-field" />
            </div>
            <div v-if="passwordMode === 'user'">
              <label class="form-label" for="password_confirm">{{ $t('therapistRegister.passwordConfirm') }}</label>
              <input v-model="form.password_confirm" id="password_confirm" type="password" required class="input-field" />
            </div>
            <div v-if="passwordMode === 'auto'" class="text-sm text-gray-500">
              {{ $t('therapistRegister.passwordAuto') }}
            </div>
            <div>
              <label class="form-label">
                <input type="checkbox" v-model="form.accept_terms" required />
                {{ $t('therapistRegister.acceptTerms') }}
              </label>
            </div>
            <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
            <div v-if="success" class="text-green-600 text-sm">{{ success }}</div>
            
            <!-- OTP Verification Step -->
            <div v-if="showOtpStep" class="otp-verification-section border-t pt-4 mt-4">
              <h3 class="text-lg text-gray-900 mb-4">تحقق من رمز التأكيد</h3>
              <p class="text-sm text-gray-600 mb-4">
                تم إرسال رمز التحقق إلى: {{ contactMethod }}
              </p>
              <div class="mb-4">
                <label class="form-label" for="otp_code">رمز التحقق (6 أرقام)</label>
                <input 
                  v-model="otpCode" 
                  id="otp_code" 
                  type="text" 
                  maxlength="6" 
                  pattern="[0-9]{6}"
                  placeholder="أدخل الرمز المكون من 6 أرقام"
                  class="input-field text-center text-lg tracking-widest" 
                  autocomplete="one-time-code"
                  @input="otpCode = otpCode.replace(/\D/g, '')"
                />
              </div>
              <button 
                @click="verifyOtp" 
                type="button" 
                class="btn btn-success w-full mb-2" 
                :disabled="loading || otpCode.length !== 6"
              >
                <span v-if="loading">جاري التحقق...</span>
                <span v-else>تحقق من الرمز</span>
              </button>
              <button 
                @click="showOtpStep = false; resetForm()" 
                type="button" 
                class="btn btn-secondary w-full text-sm"
              >
                إلغاء والعودة للنموذج
              </button>
            </div>
            
            <!-- Regular Submit Button (hidden during OTP step) -->
            <button v-if="!showOtpStep" type="submit" class="btn btn-primary w-full" :disabled="loading">
              <span v-if="loading">{{ $t('therapistRegister.submitting') }}</span>
              <span v-else>{{ $t('therapistRegister.submit') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useTherapistRegistrationStore } from '@/stores/therapistRegistration'
import api from '@/services/api'
import { useI18n } from 'vue-i18n'
import FancyUpload from '@/components/FancyUpload.vue'

const { t, locale } = useI18n()
const settingsStore = useSettingsStore()
const registrationStore = useTherapistRegistrationStore()
settingsStore.loadSettings()
registrationStore.loadSettings()
const passwordMode = computed(() => settingsStore.getTherapistRegistrationPasswordMode)

const form = ref({
  name: '',
  name_en: '',
  email: '',
  phone: '',
  phone_country: 'EG',
  whatsapp: '',
  whatsapp_country: 'EG',
  doctor_specialty: '',
  profile_image: null,
  identity_front: null,
  identity_back: null,
  certificates: [],
  password: '',
  password_confirm: '',
  accept_terms: false
})
const loading = ref(false)
const error = ref('')
const success = ref('')

// OTP verification state
const showOtpStep = ref(false)
const otpCode = ref('')
const sessionKey = ref('')
const contactMethod = ref('')

function onFileChange(event, field, multiple = false) {
  if (multiple) {
    form.value[field] = Array.from(event.target.files)
  } else {
    form.value[field] = event.target.files[0]
  }
}

// Enhanced phone validation function with detailed error messages
const validatePhoneNumber = (phoneNumber, countryCode) => {
  const country = registrationStore.countryCodes[countryCode]
  
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
  
  // Validate using country regex; error message from JSON (validation_message_en/ar) or generic default
  const dialCode = country.dial_code || country.code
  const fullPhoneNumber = dialCode + cleanPhoneNumber
  const pattern = new RegExp(country.validation_pattern)
  
  if (!pattern.test(fullPhoneNumber)) {
    const isArabic = locale.value === 'ar'
    const customMessage = isArabic ? country.validation_message_ar : country.validation_message_en
    const error = (customMessage && customMessage.trim()) ? customMessage : t('auth.register.phoneValidation.invalidFormatForCountry')
    return { isValid: false, error }
  }
  
  return { isValid: true, error: null }
}

async function onSubmit() {
  error.value = ''
  success.value = ''
  loading.value = true
  
  
  try {
    // Validate phone numbers if country codes are enabled
    if (registrationStore.shouldShowCountryDialCodes) {
      // Validate phone number
      if (form.value.phone && form.value.phone_country) {
        const phoneValidation = validatePhoneNumber(form.value.phone, form.value.phone_country)
        if (!phoneValidation.isValid) {
          error.value = phoneValidation.error
          loading.value = false
          return
        }
      }
      
      // Validate WhatsApp number
      if (form.value.whatsapp && form.value.whatsapp_country) {
        const whatsappValidation = validatePhoneNumber(form.value.whatsapp, form.value.whatsapp_country)
        if (!whatsappValidation.isValid) {
          error.value = whatsappValidation.error
          loading.value = false
          return
        }
      }
    }
    
    const data = new FormData()
    
    // Add nonce for security
    data.append('nonce', window.ajaxData?.nonce || '')
    
    for (const key in form.value) {
      if (key === 'certificates' && Array.isArray(form.value.certificates)) {
        form.value.certificates.forEach((file, idx) => {
          data.append('certificates[]', file)
        })
      } else if (['profile_image', 'identity_front', 'identity_back'].includes(key)) {
        if (form.value[key]) data.append(key, form.value[key])
      } else {
        data.append(key, form.value[key])
      }
    }
    
    data.append('action', 'register_therapist_shortcode')
    
    const response = await api.post('/wp-admin/admin-ajax.php', data)
    
    if (response.data.success) {
      if (response.data.data.step === 'otp_verification') {
        // Show OTP verification step
        showOtpStep.value = true
        sessionKey.value = response.data.data.session_key
        contactMethod.value = response.data.data.contact_method
        success.value = response.data.data.message
      } else {
        // Direct success (shouldn't happen with current setup)
        success.value = response.data.data.message
        resetForm()
      }
    } else {
      error.value = response.data.data?.message || t('therapistRegister.error')
    }
  } catch (e) {
    error.value = e.response?.data?.data?.message || t('therapistRegister.error')
  } finally {
    loading.value = false
  }
}

async function verifyOtp() {
  if (!otpCode.value || otpCode.value.length !== 6) {
    error.value = 'يرجى إدخال رمز التحقق المكون من 6 أرقام'
    return
  }
  
  error.value = ''
  loading.value = true
  
  try {
    const data = new FormData()
    data.append('nonce', window.ajaxData?.nonce || '')
    data.append('action', 'register_therapist_shortcode')
    data.append('step', 'verify_otp')
    data.append('session_key', sessionKey.value)
    data.append('otp_code', otpCode.value)
    
    const response = await api.post('/wp-admin/admin-ajax.php', data)
    
    if (response.data.success) {
      success.value = response.data.data.message
      showOtpStep.value = false
      resetForm()
    } else {
      error.value = response.data.data?.message || 'فشل في التحقق من الرمز'
    }
  } catch (e) {
    error.value = e.response?.data?.data?.message || 'حدث خطأ أثناء التحقق'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    name: '', name_en: '', email: '', phone: '', whatsapp: '', doctor_specialty: '',
    profile_image: null, identity_front: null, identity_back: null, certificates: [],
    password: '', password_confirm: '', accept_terms: false,
    phone_country: 'EG', whatsapp_country: 'EG'
  }
  otpCode.value = ''
  sessionKey.value = ''
  contactMethod.value = ''
}

// Function to filter only numbers for phone input
const onPhoneInput = (event) => {
  // Remove all non-numeric characters
  const numericValue = event.target.value.replace(/[^0-9]/g, '')
  
  // Update the form value
  form.value.phone = numericValue
}

// Function to filter only numbers for WhatsApp input
const onWhatsAppInput = (event) => {
  // Remove all non-numeric characters
  const numericValue = event.target.value.replace(/[^0-9]/g, '')
  
  // Update the form value
  form.value.whatsapp = numericValue
}

onMounted(() => {
  // Set default country when settings load
  registrationStore.loadSettings().then(() => {
    form.value.phone_country = registrationStore.defaultCountry
    form.value.whatsapp_country = registrationStore.defaultCountry
  })
})
</script>

<style scoped>
.input-field {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  margin-top: 0.25rem;
  margin-bottom: 0.5rem;
}
.form-label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
}
.btn-primary {
  background: #2563eb;
  color: #fff;
  border: none;
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-primary:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}
.btn-success {
  background: #10b981;
  color: #fff;
  border: none;
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-success:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
.btn-secondary {
  background: #6b7280;
  color: #fff;
  border: none;
  padding: 0.5rem;
  border-radius: 0.375rem;
  font-weight: 500;
  cursor: pointer;
}
.btn-secondary:hover {
  background: #4b5563;
}
.otp-verification-section {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}
.phone-input-group {
  display: flex;
  gap: 10px;
}
.country-code-select {
  flex: 0 0 150px;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
}
.phone-number-input {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
}
/* RTL Support */
[dir="rtl"] .phone-input-group {
  direction: ltr;
}
</style> 