<template>
  <div class="therapist-card hover:shadow-lg transition-shadow overflow-hidden rounded-lg" :class="cardVariant === 'diagnosis-results' ? 'diagnosis-results-card' : 'therapists-page-card'" :data-therapist-id="therapist.id">
    <!-- Top Header Section (Dark Blue Background) -->
    <div
  class="bg-primary-500 text-white px-4 py-3 relative rounded-t-[10px]"
  :class="locale === 'ar' ? 'text-right' : 'text-left'">
      <div class="flex items-center justify-center" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
        <!-- Name and Specialty -->
        <div class="flex flex-col items-end">
          <h3 class="text-[1.55rem] text-white" style="line-height: 1.75rem;">{{ therapist.name }}</h3>
          <p v-if="displaySpecialty" class="text-[1rem] mt-1 text-secondary-500 specialty-text">{{ displaySpecialty }}</p>
        </div>
        
        <!-- Order Badge (only on diagnosis results) -->
        <div 
          v-if="cardVariant === 'diagnosis-results' && showOrderBadge && currentDiagnosisDisplayOrder" 
          class="flex-shrink-0"
          :class="locale === 'ar' ? 'mr-2' : 'ml-2'"
        >
        <div class="absolute top-[-8px] left-0 w-[7rem] h-[7rem] flex items-center justify-center z-[9]">

            <img
              v-if="orderBadgeIconExists"
              src="/order-badge.png"
              alt="Order Badge"
              class="w-full h-full object-contain"
              @error="orderBadgeIconExists = false"
            />
            <div
              v-else
              class="bg-secondary-300 text-primary-500 w-16 h-16 rounded-full flex items-center justify-center"
            >
              <span class="text-[1.55rem] font-jalsah1">{{ therapistPosition }}</span>
            </div>
            <div
              v-if="orderBadgeIconExists"
              class="absolute inset-0 flex items-center justify-center"
              style="pointer-events: none;"
            >
              <span class="text-[1.55rem] font-jalsah1 text-primary-500">{{ therapistPosition }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Therapist Photo Section -->
    <div class="relative w-full therapist-photo-section">
      <img 
        :src="therapistPhotoUrl" 
        :alt="therapist.name"
        class="w-full h-full object-cover"
        :class="therapistPhotoUrl !== '/default-therapist.svg' ? '' : 'bg-gray-100 p-4'"
      />
    </div>

    <!-- Session Details Section (Dark Blue Band) -->
    <div class="bg-primary-500 text-white px-4 py-3 text-center">
      <div class="text-[1.55rem]">
        <span>45 {{ $t('common.minutes') }}</span>  
        <span class="mx-2">|</span>
        {{ formatPrice(therapist.price?.price || therapist.price?.others, getTherapistCurrencySymbol()) }}
      </div>
    </div>

    <!-- Action Buttons Section -->
    <div class="bg-white relative">
      <!-- About Therapist Button (only on therapists page, after price section) -->
      <button
        v-if="cardVariant === 'therapists-page'"
        @click.stop="handleOpenAbout"
        class="w-full flex-row flex items-center justify-center gap-2 bg-secondary-500 text-primary-500 px-2 py-1 text-[18px] hover:bg-secondary-400 transition-colors font-jalsah1"
        style="line-height: 1.8rem;"
      >
        <img
          v-if="infoIconExists"
          src="/info-icon.png"
          alt="About"
          class="h-[25px] absolute left-[25px]"
          @error="infoIconExists = false"

        />
        <svg
          v-else
          class="h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        {{ $t('popups.aboutTherapist') }}
      </button>

    <!-- Why This Therapist Button (only on diagnosis results, after price section) -->
    <button
      v-if="cardVariant === 'diagnosis-results' && diagnosisId && suitabilityMessage"
      @click.stop="handleOpenWhy"
      class="w-full relative flex items-center justify-center gap-2 bg-secondary-500 text-primary-500 px-4 py-1 text-[18px] hover:bg-secondary-400 transition-colors font-jalsah1"
      :class="locale === 'ar' ? 'flex-row' : 'flex-row'"
    >
      <img
        v-if="infoIconExists"
        src="/info-icon.png"
        alt="Why"
        class="h-[25px] absolute left-[10px]"
        @error="infoIconExists = false"
      />
      <svg
        v-else
        class="h-4 absolute left-[10px]"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      {{ $t('popups.whyThisTherapist') }}
    </button>
      <!-- Bottom Row Buttons (Certificates and Book) -->
      <div class="flex" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
        <!-- View Certificates Button -->
        <button
          v-if="therapist.certificates && therapist.certificates.length > 0"
          @click.stop="handleOpenCertificates"
          class="flex-1 flex-row flex items-center justify-center gap-2 bg-primary-500 text-white px-2 py-1 text-[17px] hover:bg-primary-600 transition-colors border-l border-l-1 border-white font-jalsah1"
          style="line-height: 1.8rem;"
        >
          <img
            v-if="certificateIconExists"
            src="/certificate-icon.png"
            alt="Certificates"
            class="h-[1.4rem]"
            @error="certificateIconExists = false"
          />
          <svg
            v-else
            class="h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          {{ $t('popups.viewCertificates') }}
        </button>

        <!-- Book Appointment Button -->
        <button
          @click.stop="handleOpenBooking"
          class="flex-1 flex items-center justify-center gap-2 bg-primary-500 text-white px-2 py-1 text-[17px] hover:bg-primary-600 transition-colors font-jalsah1"
          :class="locale === 'ar' ? 'flex-row' : 'flex-row'"
        >
          <img
            v-if="calendarIconExists"
            src="/calendar-icon.png"
            alt="Book"
            class="h-[1.4rem]"
            @error="calendarIconExists = false"
          />
          <svg
            v-else
            class="h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          {{ $t('popups.bookAppointment') }}
        </button>
      </div>
    </div>

    <!-- Lightbox Component -->
    <Lightbox
      :is-open="lightboxOpen"
      :images="certificateImages"
      :initial-index="lightboxIndex"
      @close="closeLightbox"
    />
  </div>
</template>

<script>
import { computed, ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'
import { useToast } from 'vue-toastification'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import Lightbox from './Lightbox.vue'
import { formatPrice as formatPriceUtil, getCurrencySymbol } from '@/utils/currency'

export default {
  name: 'TherapistCard',
  components: {
    Lightbox
  },
  props: {
    therapist: {
      type: Object,
      required: true
    },
    diagnosisId: {
      type: [String, Number],
      default: null
    },
    position: {
      type: Number,
      default: null
    },
    settingsStore: {
      type: Object,
      required: false,
      default: null
    },
    showOrderBadge: {
      type: Boolean,
      default: false
    },
    openTherapistId: {
      type: [String, Number],
      default: null
    },
    cardVariant: {
      type: String,
      default: 'therapists-page',
      validator: (value) => ['therapists-page', 'diagnosis-results'].includes(value)
    }
  },
  emits: ['click', 'book', 'show-details', 'hide-details', 'open-about', 'open-booking', 'open-why', 'open-certificates'],
  setup(props, { emit }) {
    const { t, locale } = useI18n()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    const settingsStore = useSettingsStore()
    const toast = useToast()
    const router = useRouter()

    // Icon existence refs
    const orderBadgeIconExists = ref(true)
    const infoIconExists = ref(true)
    const calendarIconExists = ref(true)
    const certificateIconExists = ref(true)

    // Lightbox state
    const lightboxOpen = ref(false)
    const lightboxIndex = ref(0)

    // Computed properties
    const currentDiagnosisDisplayOrder = computed(() => {
      if (props.cardVariant !== 'diagnosis-results' || !props.showOrderBadge) return null
      return props.position || null
    })

    const therapistPosition = computed(() => {
      return currentDiagnosisDisplayOrder.value?.toString() || '1'
    })

    const suitabilityMessage = computed(() => {
      if (!props.diagnosisId || !props.therapist.diagnoses) return null
      
      const diagnosis = props.therapist.diagnoses.find(d => d.id.toString() === props.diagnosisId.toString())
      return diagnosis?.suitability_message || null
    })

    const therapistPhotoUrl = computed(() => {
      // Use full image URL, fallback to photo, then default
      return props.therapist.photo_url || 
             props.therapist.full_photo || 
             props.therapist.image_url || 
             props.therapist.photo || 
             '/default-therapist.svg'
    })

    const certificateImages = computed(() => {
      if (!props.therapist.certificates || props.therapist.certificates.length === 0) return []
      return props.therapist.certificates
        .filter(cert => cert.is_image)
        .map(cert => ({
          url: cert.url || cert.thumbnail_url,
          alt: cert.name || 'Certificate'
        }))
    })

    // Display specialty based on role
    // If role is 'psychiatrist' → show 'طبيب نفسي'
    // If role is 'clinical_psychologist' → show 'أخصائي نفسي إكلينيكي'
    // Otherwise fallback to doctor_specialty
    const displaySpecialty = computed(() => {
      if (props.therapist?.role === 'psychiatrist') {
        return 'طبيب نفسي'
      }
      if (props.therapist?.role === 'clinical_psychologist') {
        return 'أخصائي نفسي إكلينيكي'
      }
      // Fallback to doctor_specialty if role is not set
      return props.therapist?.doctor_specialty || ''
    })

    // Format functions
    const formatPrice = (price, currencySymbol) => {
      // formatPriceUtil expects (amount, locale, currency) - pass locale from i18n and currencySymbol as the currency parameter
      return formatPriceUtil(price, locale.value, currencySymbol)
    }

    // Get currency symbol for therapist price display
    // Priority: 1) User's selected currency from settings (most important - user's choice)
    //           2) currency_symbol from API (if available and matches user's currency)
    //           3) currency code from API converted to symbol
    const getTherapistCurrencySymbol = () => {
      // CRITICAL: Always use user's selected currency from settings store
      // The price is already converted correctly, we just need the right symbol
      // User may have manually selected a currency different from their country
      const userCurrencyCode = settingsStore.userCurrencyCode || 'EGP'
      
      // If therapist price has currency_symbol and it matches user's currency, use it
      // Otherwise, convert user's currency code to symbol
      if (props.therapist?.price?.currency_symbol && 
          props.therapist?.price?.currency === userCurrencyCode) {
        return props.therapist.price.currency_symbol
      }
      
      // Always use user's currency code to get symbol
      return getCurrencySymbol(userCurrencyCode)
    }

    // Action handlers
    const handleOpenAbout = () => {
      emit('open-about', props.therapist.id)
    }

    const handleOpenBooking = () => {
      // Check if user is authenticated
      if (!authStore.isAuthenticated) {
        // Redirect to login page if not authenticated
        router.push('/login')
        return
      }
      // If authenticated, emit the event to open booking popup
      emit('open-booking', props.therapist.id)
    }

    const handleOpenWhy = () => {
      emit('open-why', props.therapist.id)
    }

    const handleOpenCertificates = () => {
      // Open lightbox directly with first certificate image
      if (certificateImages.value && certificateImages.value.length > 0) {
        openLightbox(0)
      }
    }

    // Lightbox handlers
    const openLightbox = (index) => {
      lightboxIndex.value = index
      lightboxOpen.value = true
    }

    const closeLightbox = () => {
      lightboxOpen.value = false
    }

    return {
      locale,
      formatPrice,
      getCurrencySymbol,
      getTherapistCurrencySymbol,
      currentDiagnosisDisplayOrder,
      therapistPosition,
      suitabilityMessage,
      therapistPhotoUrl,
      certificateImages,
      displaySpecialty,
      // Icons
      orderBadgeIconExists,
      infoIconExists,
      calendarIconExists,
      certificateIconExists,
      // Action handlers
      handleOpenAbout,
      handleOpenBooking,
      handleOpenWhy,
      handleOpenCertificates,
      // Lightbox
      lightboxOpen,
      lightboxIndex,
      openLightbox,
      closeLightbox,
      settingsStore
    }
  }
}
</script>

<style scoped>
.therapist-card {
  display: flex;
  flex-direction: column;
  background: white;
  border-radius: 0.5rem;
  overflow: initial;
  width: 100%;
  max-width: 100%;
}

/* Photo section should always be square relative to card width */
.therapist-photo-section {
  aspect-ratio: 1 / 1;
}

/* Desktop: Remove fixed width, let grid control the width */
@media (min-width: 481px) {
  .therapist-card {
    width: 100%;
    max-width: 100%;
  }
}

.specialty-text {
  font-family: 'jalsah2', sans-serif !important;
}

/* RTL Support */
.rtl {
  direction: rtl;
  text-align: right;
}
</style>
