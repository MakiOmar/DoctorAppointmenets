<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
      <p class="text-white mt-4">{{ $t('certificates.loading') }}</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <p class="text-red-300 mb-4">{{ error }}</p>
      <button @click="loadCertificates" class="bg-white text-primary-500 px-4 py-2 rounded-lg font-medium hover:bg-gray-100">
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Certificates Grid -->
    <div v-else-if="certificates && certificates.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="(cert, index) in certificates"
        :key="cert.id"
        class="bg-white rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
        @click="openLightbox(index)"
      >
        <div class="w-full h-48">
          <img
            v-if="cert.is_image"
            :src="cert.thumbnail_url || cert.url"
            :alt="cert.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
            <img
              v-if="certificateIconExists"
              src="/certificate-icon.png"
              alt="Certificate"
              class="w-16 h-16 opacity-50"
              @error="certificateIconExists = false"
            />
            <svg
              v-else
              class="w-16 h-16 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- No Certificates -->
    <div v-else class="text-center py-12">
      <img
        v-if="certificateIconExists"
        src="/certificate-icon.png"
        alt="No Certificates"
        class="w-16 h-16 mx-auto mb-4 opacity-30"
        @error="certificateIconExists = false"
      />
      <svg
        v-else
        class="w-16 h-16 text-white mx-auto mb-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      <h3 class="text-lg font-medium text-white mb-2">{{ $t('therapistDetails.noCertificates') }}</h3>
      <p class="text-white">{{ $t('therapistDetails.noCertificatesMessage') }}</p>
    </div>

    <!-- Lightbox for Certificate Viewing -->
    <Lightbox
      :is-open="lightboxOpen"
      :images="certificateImages"
      :initial-index="lightboxIndex"
      @close="closeLightbox"
    />
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import Lightbox from '../Lightbox.vue'

export default {
  name: 'CertificatesContent',
  components: {
    Lightbox
  },
  props: {
    therapist: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const { locale } = useI18n()
    const certificateIconExists = ref(true)
    const loading = ref(false)
    const error = ref(null)
    const certificates = ref([])
    const lightboxOpen = ref(false)
    const lightboxIndex = ref(0)

    const certificateImages = computed(() => {
      if (!certificates.value) return []
      
      return certificates.value
        .filter(cert => cert.is_image)
        .map(cert => ({
          url: cert.url,
          name: cert.name,
          alt: cert.name
        }))
    })

    const loadCertificates = async () => {
      loading.value = true
      error.value = null

      try {
        // First try to use certificates from therapist prop
        if (props.therapist.certificates && Array.isArray(props.therapist.certificates) && props.therapist.certificates.length > 0) {
          certificates.value = props.therapist.certificates
          loading.value = false
          return
        }

        // Otherwise, load from API
        const response = await api.get(`/api/ai/therapists/${props.therapist.id}/details`)
        const data = response.data

        if (data.success && data.data.certificates) {
          certificates.value = data.data.certificates
        } else {
          certificates.value = []
        }
      } catch (err) {
        console.error('Error loading certificates:', err)
        error.value = 'Failed to load certificates'
        certificates.value = []
      } finally {
        loading.value = false
      }
    }

    const openLightbox = (index) => {
      // Find the index in the filtered image array
      const imageCertificates = certificates.value.filter(cert => cert.is_image)
      const imageIndex = imageCertificates.findIndex((cert, idx) => {
        const originalIndex = certificates.value.findIndex(c => c.id === cert.id)
        return originalIndex === index
      })
      
      lightboxIndex.value = imageIndex >= 0 ? imageIndex : 0
      lightboxOpen.value = true
    }

    const closeLightbox = () => {
      lightboxOpen.value = false
    }

    // Initialize with therapist certificates if available
    watch(() => props.therapist, (therapist) => {
      if (therapist && therapist.certificates && Array.isArray(therapist.certificates)) {
        certificates.value = therapist.certificates
      } else {
        loadCertificates()
      }
    }, { immediate: true })

    return {
      locale,
      certificateIconExists,
      loading,
      error,
      certificates,
      certificateImages,
      lightboxOpen,
      lightboxIndex,
      loadCertificates,
      openLightbox,
      closeLightbox
    }
  }
}
</script>
