<template>
  <div v-if="show" class="mt-6">
    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
      <p class="text-gray-600 mt-2">{{ $t('therapistDetails.loading') }}</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-600">{{ error }}</p>
      <button @click="loadDetails" class="btn-secondary mt-2">
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Therapist Details -->
    <div v-else-if="details" class="bg-white rounded-lg border border-gray-200 p-6">
      <!-- Basic Information -->
      <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ $t('therapistDetails.basicInfo') }}
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.name') }}</label>
            <p class="text-gray-900">{{ details.name }}</p>
          </div>
          
          <div v-if="details.name_en">
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.nameEn') }}</label>
            <p class="text-gray-900">{{ details.name_en }}</p>
          </div>
          
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.email') }}</label>
            <p class="text-gray-900">{{ details.email }}</p>
          </div>
          
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.phone') }}</label>
            <p class="text-gray-900">{{ details.phone }}</p>
          </div>
          
          <div v-if="details.whatsapp">
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.whatsapp') }}</label>
            <p class="text-gray-900">{{ details.whatsapp }}</p>
          </div>
          
          <div>
            <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.specialty') }}</label>
            <p class="text-gray-900">{{ details.specialty }}</p>
          </div>
        </div>
      </div>

      <!-- Certificates Section -->
      <div v-if="details.certificates && details.certificates.length > 0">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
          {{ $t('therapistDetails.certificates') }} ({{ details.certificates.length }})
        </h3>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div 
            v-for="cert in details.certificates" 
            :key="cert.id"
            class="bg-gray-50 rounded-lg p-3 border border-gray-200"
          >
            <div class="aspect-square bg-white rounded-lg border border-gray-200 overflow-hidden mb-2">
              <img 
                v-if="cert.is_image"
                :src="cert.thumbnail_url || cert.url" 
                :alt="cert.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-900 truncate">{{ cert.name }}</p>
              <p class="text-xs text-gray-500">{{ cert.file_size }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- No Certificates -->
      <div v-else class="text-center py-8">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('therapistDetails.noCertificates') }}</h4>
        <p class="text-gray-600">{{ $t('therapistDetails.noCertificatesMessage') }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'TherapistDetailsInline',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    therapistId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const { t } = useI18n()
    
    const loading = ref(false)
    const error = ref(null)
    const details = ref(null)

    const loadDetails = async () => {
      if (!props.therapistId) return
      
      loading.value = true
      error.value = null
      
      try {
        const response = await api.get(`/api/ai/therapists/${props.therapistId}/details`)
        details.value = response.data
      } catch (err) {
        error.value = err.response?.data?.message || t('therapistDetails.loadError')
        console.error('Error loading therapist details:', err)
      } finally {
        loading.value = false
      }
    }

    // Load details when component is shown
    watch(() => props.show, (newVal) => {
      if (newVal && !details.value) {
        loadDetails()
      }
    })

    return {
      loading,
      error,
      details,
      loadDetails
    }
  }
}
</script> 