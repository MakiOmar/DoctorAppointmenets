import { defineStore } from 'pinia'
import api from '@/services/api'

export const useTherapistRegistrationStore = defineStore('therapistRegistration', {
  state: () => ({
    settings: {
      otp_method: 'email',
      require_email: true,
      country_dial_required: true,
      default_country: 'EG',
      country_codes: {}
    },
    loading: false
  }),

  getters: {
    shouldShowEmail: (state) => {
      const result = state.settings.require_email
      console.log('🔍 shouldShowEmail getter called, result:', result, 'type:', typeof result)
      return result
    },
    shouldShowCountryDialCodes: (state) => state.settings.country_dial_required,
    otpMethod: (state) => state.settings.otp_method,
    defaultCountry: (state) => state.settings.default_country,
    countryCodes: (state) => state.settings.country_codes
  },

  actions: {
    async loadSettings() {
      if (this.loading) return
      
      this.loading = true
      try {
        console.log('🔍 Loading therapist registration settings...')
        const response = await api.get('/wp-json/jalsah-ai/v1/therapist-registration-settings')
        console.log('🔍 API Response:', response.data)
        if (response.data.success) {
          this.settings = response.data.data
          console.log('🔍 Settings loaded:', this.settings)
          console.log('🔍 require_email value:', this.settings.require_email)
        }
      } catch (error) {
        console.error('Failed to load therapist registration settings:', error)
      } finally {
        this.loading = false
      }
    }
  }
})
