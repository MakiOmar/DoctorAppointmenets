<template>
  <div v-if="show" class="mt-6">
    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <p class="text-gray-600">{{ $t('therapistDetails.loading') }}</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="text-center py-8">
      <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('therapistDetails.error') }}</h3>
      <p class="text-gray-600">{{ error }}</p>
      <button @click="loadDetails" class="btn-primary mt-4">
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Therapist Details -->
    <div v-else-if="details" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="grid md:grid-cols-2 gap-8">
        <!-- Personal Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            {{ $t('therapistDetails.personalInfo') }}
          </h3>
          
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.name') }}</label>
              <p class="text-gray-900">{{ details.name }}</p>
            </div>
            
            <div v-if="details.name_en">
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.nameEn') }}</label>
              <p class="text-gray-900">{{ details.name_en }}</p>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.specialty') }}</label>
              <p class="text-gray-900">{{ details.specialty }}</p>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.jalsahAiName') }}</label>
              <p class="text-gray-900 font-medium">{{ details.jalsah_ai_name }}</p>
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
          </div>
        </div>

        <!-- Application Information -->
        <div class="space-y-4">
          <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            {{ $t('therapistDetails.applicationInfo') }}
          </h3>
          
          <div class="space-y-3">
            <div>
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.applicationDate') }}</label>
              <p class="text-gray-900">{{ formatDate(details.application_date) }}</p>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.approvalDate') }}</label>
              <p class="text-gray-900">{{ formatDate(details.approval_date) }}</p>
            </div>
            
            <div>
              <label class="text-sm font-medium text-gray-500">{{ $t('therapistDetails.certificatesCount') }}</label>
              <p class="text-gray-900">{{ details.certificates.length }} {{ $t('therapistDetails.certificates') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Certificates Section -->
      <div v-if="details.certificates.length > 0" class="mt-8">
        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2 mb-4">
          {{ $t('therapistDetails.certificates') }}
        </h3>
        
        <!-- Certificates Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div 
            v-for="(cert, index) in details.certificates" 
            :key="cert.id"
            class="relative group cursor-pointer"
            @click="openLightbox(index)"
          >
            <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
              <img 
                v-if="cert.is_image"
                :src="cert.thumbnail_url || cert.url" 
                :alt="cert.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
              />
              <div 
                v-else
                class="w-full h-full flex items-center justify-center bg-blue-50"
              >
                <div class="text-center">
                  <svg class="w-8 h-8 text-blue-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <p class="text-xs text-blue-600 font-medium">{{ cert.file_extension }}</p>
                </div>
              </div>
            </div>
            
            <!-- Certificate Info Overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-end">
              <div class="p-2 w-full text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <p class="text-xs font-medium truncate">{{ cert.name }}</p>
                <p class="text-xs opacity-75">{{ cert.file_size }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Certificates -->
      <div v-else class="mt-8 text-center py-8">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('therapistDetails.noCertificates') }}</h3>
        <p class="text-gray-600">{{ $t('therapistDetails.noCertificatesMessage') }}</p>
      </div>
    </div>
  </div>

  <!-- Lightbox for certificates -->
  <div v-if="lightboxOpen" class="fixed inset-0 z-60 bg-black bg-opacity-90 flex items-center justify-center" @click="closeLightbox">
    <div class="relative max-w-4xl max-h-full p-4" @click.stop>
      <button
        @click="closeLightbox"
        class="absolute top-4 right-4 text-white hover:text-gray-300 z-10"
      >
        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      
      <div v-if="currentCertificate" class="text-center">
        <img
          v-if="currentCertificate.is_image"
          :src="currentCertificate.url"
          :alt="currentCertificate.name"
          class="max-w-full max-h-full object-contain"
        />
        <div v-else class="text-white text-center">
          <svg class="w-16 h-16 text-white mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="text-xl font-medium mb-2">{{ currentCertificate.name }}</h3>
          <p class="text-gray-300">{{ currentCertificate.file_size }}</p>
          <a 
            :href="currentCertificate.url" 
            target="_blank"
            class="inline-block mt-4 px-4 py-2 bg-white text-black rounded hover:bg-gray-100"
          >
            {{ $t('therapistDetails.downloadFile') }}
          </a>
        </div>
        
        <div class="mt-4 text-white">
          <h4 class="font-medium">{{ currentCertificate.name }}</h4>
          <p class="text-sm text-gray-300">{{ currentCertificate.description || $t('therapistDetails.noDescription') }}</p>
        </div>
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
    const { t, locale } = useI18n()
    
    const loading = ref(false)
    const error = ref(null)
    const details = ref(null)
    const lightboxOpen = ref(false)
    const currentCertificateIndex = ref(0)

    const currentCertificate = computed(() => {
      if (!details.value || !details.value.certificates) return null
      return details.value.certificates[currentCertificateIndex.value] || null
    })

    const loadDetails = async () => {
      if (!props.therapistId) return
      
      loading.value = true
      error.value = null
      
      try {
        const response = await api.get(`/api/ai/therapists/${props.therapistId}/details`)
        details.value = response.data.data
      } catch (err) {
        error.value = err.response?.data?.message || t('therapistDetails.loadError')
        console.error('Error loading therapist details:', err)
      } finally {
        loading.value = false
      }
    }

    const openLightbox = (index) => {
      currentCertificateIndex.value = index
      lightboxOpen.value = true
    }

    const closeLightbox = () => {
      lightboxOpen.value = false
    }

    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      const currentLocale = locale.value === 'ar' ? 'ar-SA' : 'en-US'
      return date.toLocaleDateString(currentLocale, { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
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
      lightboxOpen,
      currentCertificate,
      loadDetails,
      openLightbox,
      closeLightbox,
      formatDate,
      locale
    }
  }
}
</script> 