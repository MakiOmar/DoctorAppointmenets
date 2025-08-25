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

          <!-- Email -->
          <div>
            <label for="email" class="form-label">{{ $t('auth.register.email') }}</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="input-field"
              :placeholder="$t('auth.register.emailPlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="email"
            />
          </div>

          <!-- Phone -->
          <div>
            <label for="phone" class="form-label">{{ $t('auth.register.phone') }}</label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              required
              class="input-field"
              :placeholder="$t('auth.register.phonePlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="tel"
            />
          </div>

          <!-- WhatsApp -->
          <div>
            <label for="whatsapp" class="form-label">{{ $t('auth.register.whatsapp') }}</label>
            <input
              id="whatsapp"
              v-model="form.whatsapp"
              type="tel"
              required
              class="input-field"
              :placeholder="$t('auth.register.whatsappPlaceholder')"
              :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              autocomplete="tel"
            />
          </div>

          <!-- Country -->
          <div>
            <label for="country" class="form-label">{{ $t('auth.register.country') }}</label>
            <select
              id="country"
              v-model="form.country"
              required
              class="input-field"
            >
              <option value="">{{ $t('auth.register.selectCountry') }}</option>
              <option value="Saudi Arabia">{{ $t('auth.register.countries.saudiArabia') }}</option>
              <option value="United Arab Emirates">{{ $t('auth.register.countries.uae') }}</option>
              <option value="Kuwait">{{ $t('auth.register.countries.kuwait') }}</option>
              <option value="Qatar">{{ $t('auth.register.countries.qatar') }}</option>
              <option value="Bahrain">{{ $t('auth.register.countries.bahrain') }}</option>
              <option value="Oman">{{ $t('auth.register.countries.oman') }}</option>
              <option value="Jordan">{{ $t('auth.register.countries.jordan') }}</option>
              <option value="Lebanon">{{ $t('auth.register.countries.lebanon') }}</option>
              <option value="Egypt">{{ $t('auth.register.countries.egypt') }}</option>
              <option value="Morocco">{{ $t('auth.register.countries.morocco') }}</option>
              <option value="Tunisia">{{ $t('auth.register.countries.tunisia') }}</option>
              <option value="Algeria">{{ $t('auth.register.countries.algeria') }}</option>
              <option value="Libya">{{ $t('auth.register.countries.libya') }}</option>
              <option value="Sudan">{{ $t('auth.register.countries.sudan') }}</option>
              <option value="Iraq">{{ $t('auth.register.countries.iraq') }}</option>
              <option value="Syria">{{ $t('auth.register.countries.syria') }}</option>
              <option value="Palestine">{{ $t('auth.register.countries.palestine') }}</option>
              <option value="Yemen">{{ $t('auth.register.countries.yemen') }}</option>
              <option value="Other">{{ $t('auth.register.countries.other') }}</option>
            </select>
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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'Register',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const form = ref({
      first_name: '',
      last_name: '',
      age: '',
      email: '',
      phone: '',
      whatsapp: '',
      country: '',
      password: '',
      confirm_password: '',
      agreeToTerms: false
    })

    const loading = computed(() => authStore.loading)

    const isFormValid = computed(() => {
      return form.value.first_name &&
             form.value.last_name &&
             form.value.age &&
             form.value.email &&
             form.value.phone &&
             form.value.whatsapp &&
             form.value.country &&
             form.value.password &&
             form.value.confirm_password &&
             form.value.agreeToTerms &&
             form.value.password === form.value.confirm_password &&
             form.value.password.length >= 8
    })

    const fillDummyData = () => {
      form.value = {
        first_name: 'Maki',
        last_name: 'Omar',
        age: '25',
        email: 'maki3omar@gmail.com',
        phone: '+201234567890',
        whatsapp: '+201234567890',
        country: 'Egypt',
        password: 'TestPassword123!',
        confirm_password: 'TestPassword123!',
        agreeToTerms: true
      }
    }

    const handleRegister = async () => {
      if (!isFormValid.value) {
        return
      }

      const result = await authStore.register({
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        age: parseInt(form.value.age),
        email: form.value.email,
        phone: form.value.phone,
        whatsapp: form.value.whatsapp,
        country: form.value.country,
        password: form.value.password
      })
      
      if (result && result.requiresVerification) {
        // Redirect to verification page
        router.push(`/verify-email/${encodeURIComponent(form.value.email)}`)
      } else if (result && !result.requiresVerification) {
        // Redirect to homepage after successful registration
        router.push('/')
      }
    }

    return {
      form,
      loading,
      isFormValid,
      handleRegister,
      fillDummyData
    }
  }
}
</script> 