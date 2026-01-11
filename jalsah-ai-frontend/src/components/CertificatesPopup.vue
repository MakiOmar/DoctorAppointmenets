<template>
  <BaseModal :is-open="isOpen" @close="handleClose" @update:isOpen="handleUpdateIsOpen">
    <!-- Header with Close and Cart -->
    <template #header>
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <!-- Close Button -->
        <button
          @click="handleClose"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          :aria-label="$t('common.close')"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <!-- Cart Icon with Badge -->
        <router-link
          to="/cart"
          class="relative p-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          <img
            v-if="cartIconExists"
            src="/home/Layer-26.png"
            alt="Cart"
            class="h-6"
            @error="cartIconExists = false"
          />
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 48 48"
            class="w-6 h-6 text-gray-600 fill-current"
          >
            <title>Cart</title>
            <g id="troley-2" data-name="troley">
              <path d="M15,39a3,3,0,1,0,3-3A3,3,0,0,0,15,39Zm4,0a1,1,0,1,1-1-1A1,1,0,0,1,19,39Z"/>
              <path d="M31,39a3,3,0,1,0,3-3A3,3,0,0,0,31,39Zm4,0a1,1,0,1,1-1-1A1,1,0,0,1,35,39Z"/>
              <circle cx="28.55" cy="20.55" r="1.45"/>
              <path d="M23.45,16.9A1.45,1.45,0,1,0,22,15.45,1.45,1.45,0,0,0,23.45,16.9Z"/>
              <path d="M23,22a1,1,0,0,0,.71-.29l6-6a1,1,0,0,0-1.42-1.42l-6,6a1,1,0,0,0,0,1.42A1,1,0,0,0,23,22Z"/>
              <path d="M7,10A1,1,0,0,0,8,9,1,1,0,0,1,9,8h2.26l5.4,17.27,1.38,5A1,1,0,0,0,19,31H32a1,1,0,0,1,0,2H20a1,1,0,0,0,0,2H32a3,3,0,0,0,0-6H19.76l-.83-3H32.47a6.92,6.92,0,0,0,3.58-1,7,7,0,0,0,3-3.46,6.45,6.45,0,0,0,.21-.62L42,11.27a1,1,0,0,0-.16-.87A1,1,0,0,0,41,10H14L13,6.7A1,1,0,0,0,12,6H9A3,3,0,0,0,6,9,1,1,0,0,0,7,10Zm32.67,2L38,18l-.68,2.37A5,5,0,0,1,32.47,24H18.36l-1.87-6-1.88-6Z"/>
            </g>
          </svg>
          <span
            v-if="cartItemCount > 0"
            class="absolute -top-1 -right-1 bg-secondary-500 text-primary-500 text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center min-w-[20px] px-1"
          >
            {{ cartItemCount > 99 ? '99+' : cartItemCount }}
          </span>
        </router-link>
      </div>
    </template>

    <!-- Content -->
    <div class="p-6" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
        <p class="text-gray-600 mt-4">{{ $t('certificates.loading') }}</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <p class="text-red-600 mb-4">{{ error }}</p>
        <button @click="loadCertificates" class="btn-secondary">
          {{ $t('common.retry') }}
        </button>
      </div>

      <!-- Certificates Grid -->
      <div v-else-if="certificates && certificates.length > 0" class="space-y-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ $t('therapistDetails.certificates') }}</h2>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="(cert, index) in certificates"
            :key="cert.id"
            class="bg-white rounded-lg border border-gray-200 overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
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
            <div class="p-3">
              <h3 class="text-sm font-medium text-gray-900 mb-1 line-clamp-2">{{ cert.name }}</h3>
              <p v-if="cert.file_size" class="text-xs text-gray-500">{{ cert.file_size }}</p>
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
          class="w-16 h-16 text-gray-400 mx-auto mb-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('therapistDetails.noCertificates') }}</h3>
        <p class="text-gray-600">{{ $t('therapistDetails.noCertificatesMessage') }}</p>
      </div>
    </div>

    <!-- Lightbox for Certificate Viewing -->
    <Lightbox
      :is-open="lightboxOpen"
      :images="certificateImages"
      :initial-index="lightboxIndex"
      @close="closeLightbox"
    />
  </BaseModal>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
import api from '@/services/api'
import BaseModal from './BaseModal.vue'
import Lightbox from './Lightbox.vue'

export default {
  name: 'CertificatesPopup',
  components: {
    BaseModal,
    Lightbox
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    therapist: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'update:isOpen'],
  setup(props, { emit }) {
    const { locale } = useI18n()
    const cartStore = useCartStore()
    const cartIconExists = ref(true)
    const certificateIconExists = ref(true)
    const loading = ref(false)
    const error = ref(null)
    const certificates = ref([])
    const lightboxOpen = ref(false)
    const lightboxIndex = ref(0)

    const cartItemCount = computed(() => cartStore.itemCount)

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

    const handleClose = () => {
      emit('close')
      emit('update:isOpen', false)
    }

    const handleUpdateIsOpen = (value) => {
      emit('update:isOpen', value)
    }

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

    watch(() => props.isOpen, (newValue) => {
      if (newValue && (!certificates.value || certificates.value.length === 0)) {
        loadCertificates()
      }
    })

    // Initialize with therapist certificates if available
    watch(() => props.therapist, (therapist) => {
      if (therapist && therapist.certificates && Array.isArray(therapist.certificates)) {
        certificates.value = therapist.certificates
      }
    }, { immediate: true })

    return {
      locale,
      cartItemCount,
      cartIconExists,
      certificateIconExists,
      loading,
      error,
      certificates,
      certificateImages,
      lightboxOpen,
      lightboxIndex,
      handleClose,
      handleUpdateIsOpen,
      loadCertificates,
      openLightbox,
      closeLightbox
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.rtl {
  direction: rtl;
  text-align: right;
}
</style>