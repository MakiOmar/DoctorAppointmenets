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
    shouldShowEmail: (state) => state.settings.require_email,
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
        const response = await api.get('/wp-json/jalsah-ai/v1/therapist-registration-settings')
        if (response.data.success) {
          this.settings = response.data.data
        }
      } catch (error) {
        console.error('Failed to load therapist registration settings:', error)
      } finally {
        this.loading = false
      }
    }
  }
})
