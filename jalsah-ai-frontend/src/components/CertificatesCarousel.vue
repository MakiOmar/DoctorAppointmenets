<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click="closeModal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full" @click.stop>
        <!-- Header -->
        <div class="bg-white px-4 py-3 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              {{ $t('certificates.title') }} - {{ therapistName }}
            </h3>
            <button
              @click="closeModal"
              class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <span class="sr-only">{{ $t('common.close') }}</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="bg-white px-4 py-5 sm:p-6">
          <!-- Loading state -->
          <div v-if="loading" class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600">{{ $t('certificates.loading') }}</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="text-center py-8">
            <svg class="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('certificates.error') }}</h3>
            <p class="text-gray-600">{{ error }}</p>
            <button @click="loadCertificates" class="btn-primary mt-4">
              {{ $t('common.retry') }}
            </button>
          </div>

          <!-- Certificates carousel -->
          <div v-else-if="certificates.length > 0" class="space-y-4">
            <!-- Carousel navigation -->
            <div class="flex items-center justify-between">
              <button
                @click="previousSlide"
                :disabled="currentIndex === 0"
                class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="locale === 'ar' ? 'rotate-180' : ''"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              
              <div class="text-sm text-gray-600">
                {{ currentIndex + 1 }} / {{ certificates.length }}
              </div>
              
              <button
                @click="nextSlide"
                :disabled="currentIndex === certificates.length - 1"
                class="p-2 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="locale === 'ar' ? 'rotate-180' : ''"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            <!-- Main certificate display -->
            <div class="relative bg-gray-100 rounded-lg overflow-hidden" style="height: 400px;">
              <img
                :src="currentCertificate.url"
                :alt="currentCertificate.name"
                class="w-full h-full object-contain cursor-pointer"
                @click="openLightbox"
              />
              
              <!-- Certificate info overlay -->
              <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-4">
                <h4 class="font-medium">{{ currentCertificate.name }}</h4>
                <p class="text-sm opacity-90">{{ currentCertificate.description || $t('certificates.noDescription') }}</p>
              </div>
            </div>

            <!-- Thumbnail navigation -->
            <div class="flex space-x-2 overflow-x-auto pb-2" :class="locale === 'ar' ? 'space-x-reverse' : 'space-x-2'">
              <button
                v-for="(cert, index) in certificates"
                :key="index"
                @click="goToSlide(index)"
                class="flex-shrink-0 w-16 h-16 rounded border-2 overflow-hidden"
                :class="currentIndex === index ? 'border-primary-500' : 'border-gray-300'"
              >
                <img
                  :src="cert.url"
                  :alt="cert.name"
                  class="w-full h-full object-cover"
                />
              </button>
            </div>
          </div>

          <!-- No certificates state -->
          <div v-else class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('certificates.noCertificates') }}</h3>
            <p class="text-gray-600">{{ $t('certificates.noCertificatesMessage') }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="closeModal"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            {{ $t('common.close') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Lightbox overlay -->
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
        <img
          :src="currentCertificate.url"
          :alt="currentCertificate.name"
          class="max-w-full max-h-full object-contain"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'CertificatesCarousel',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    therapistId: {
      type: [String, Number],
      required: true
    },
    therapistName: {
      type: String,
      default: ''
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const { t, locale } = useI18n()
    
    const loading = ref(false)
    const error = ref(null)
    const certificates = ref([])
    const currentIndex = ref(0)
    const lightboxOpen = ref(false)

    const currentCertificate = computed(() => {
      return certificates.value[currentIndex.value] || null
    })

    const loadCertificates = async () => {
      if (!props.therapistId) return
      
      loading.value = true
      error.value = null
      
      try {
        const response = await api.get(`/api/ai/therapists/${props.therapistId}/certificates`)
        certificates.value = response.data.data || []
        currentIndex.value = 0
      } catch (err) {
        error.value = err.response?.data?.message || t('certificates.loadError')
        console.error('Error loading certificates:', err)
      } finally {
        loading.value = false
      }
    }

    const nextSlide = () => {
      if (currentIndex.value < certificates.value.length - 1) {
        currentIndex.value++
      }
    }

    const previousSlide = () => {
      if (currentIndex.value > 0) {
        currentIndex.value--
      }
    }

    const goToSlide = (index) => {
      if (index >= 0 && index < certificates.value.length) {
        currentIndex.value = index
      }
    }

    const openLightbox = () => {
      lightboxOpen.value = true
    }

    const closeLightbox = () => {
      lightboxOpen.value = false
    }

    const closeModal = () => {
      emit('close')
    }

    // Load certificates when modal opens
    watch(() => props.show, (newVal) => {
      if (newVal) {
        loadCertificates()
      }
    })

    // Keyboard navigation
    const handleKeydown = (event) => {
      if (!props.show) return
      
      switch (event.key) {
        case 'ArrowLeft':
          if (locale.value === 'ar') {
            nextSlide()
          } else {
            previousSlide()
          }
          break
        case 'ArrowRight':
          if (locale.value === 'ar') {
            previousSlide()
          } else {
            nextSlide()
          }
          break
        case 'Escape':
          if (lightboxOpen.value) {
            closeLightbox()
          } else {
            closeModal()
          }
          break
      }
    }

    onMounted(() => {
      document.addEventListener('keydown', handleKeydown)
    })

    return {
      loading,
      error,
      certificates,
      currentIndex,
      currentCertificate,
      lightboxOpen,
      locale,
      loadCertificates,
      nextSlide,
      previousSlide,
      goToSlide,
      openLightbox,
      closeLightbox,
      closeModal
    }
  }
}
</script>

<style scoped>
/* Custom scrollbar for thumbnails */
.overflow-x-auto::-webkit-scrollbar {
  height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style> 