<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl text-primary-500 mb-1 text-center">
          {{ $t('browseTherapists.title') }}
        </h1>
        <p class="text-[25px] text-secondary-500 text-center font-jalsah2">
          {{ $t('browseTherapists.subtitle') }}
        </p>
      </div>

      <!-- Price required message -->
      <div v-if="!hasValidPrice" class="card text-center py-12">
        <p class="text-gray-600 text-lg">{{ $t('browseTherapists.priceRequired') }}</p>
        <p class="text-gray-500 text-sm mt-2">Example: /browse-therapists?price=200</p>
      </div>

      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">{{ $t('browseTherapists.loading') }}</p>
      </div>

      <!-- Therapists List (sorted by price from API) -->
      <div v-else-if="therapists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 justify-items-center">
        <TherapistCard
          v-for="(therapist, index) in therapists"
          :key="therapist.id"
          :therapist="therapist"
          :position="index + 1"
          :show-order-badge="false"
          :card-variant="'browse'"
          :view-only-booking="true"
          :settings-store="settingsStore"
          :open-therapist-id="openTherapistId"
          @click="viewTherapist"
          @show-details="handleShowDetails"
          @hide-details="handleHideDetails"
          @open-about="handleOpenAbout"
          @open-booking="handleOpenBooking"
          @open-certificates="handleOpenCertificates"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-lg text-gray-900 mb-2">{{ $t('browseTherapists.noResults') }}</h3>
      </div>
    </div>

    <!-- Popups -->
    <AboutTherapistPopup
      v-if="selectedTherapist"
      :is-open="showAboutPopup"
      :therapist="selectedTherapist"
      @close="closeAboutPopup"
      @update:isOpen="showAboutPopup = $event"
      @open-certificates="handleOpenCertificates(selectedTherapist.id)"
    />

    <BookAppointmentPopup
      v-if="selectedTherapist"
      :is-open="showBookingPopup"
      :therapist="selectedTherapist"
      :view-only="true"
      @close="closeBookingPopup"
      @update:isOpen="showBookingPopup = $event"
    />

    <CertificatesPopup
      v-if="selectedTherapist"
      :is-open="showCertificatesPopup"
      :therapist="selectedTherapist"
      @close="closeCertificatesPopup"
      @update:isOpen="showCertificatesPopup = $event"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useSettingsStore } from '@/stores/settings'
import api from '@/services/api'
import TherapistCard from '@/components/TherapistCard.vue'
import AboutTherapistPopup from '@/components/AboutTherapistPopup.vue'
import BookAppointmentPopup from '@/components/BookAppointmentPopup.vue'
import CertificatesPopup from '@/components/CertificatesPopup.vue'

export default {
  name: 'BrowseTherapists',
  components: {
    TherapistCard,
    AboutTherapistPopup,
    BookAppointmentPopup,
    CertificatesPopup
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const toast = useToast()
    const settingsStore = useSettingsStore()

    const loading = ref(false)
    const therapists = ref([])
    const openTherapistId = ref(null)

    // Popup states
    const showAboutPopup = ref(false)
    const showBookingPopup = ref(false)
    const showCertificatesPopup = ref(false)
    const selectedTherapist = ref(null)

    // Price from URL query (required)
    const priceParam = computed(() => {
      const p = route.query.price
      return p ? parseFloat(p) : 0
    })

    const hasValidPrice = computed(() => priceParam.value > 0)

    const loadTherapists = async () => {
      if (!hasValidPrice.value) return

      loading.value = true
      try {
        const response = await api.get('/api/ai/therapists/browse', {
          params: { price: priceParam.value }
        })
        therapists.value = response.data.data || []
      } catch (error) {
        console.error('Error loading therapists:', error)
        toast.error('Failed to load therapists')
        therapists.value = []
      } finally {
        loading.value = false
      }
    }

    const viewTherapist = (therapistId) => {
      router.push(`/therapist/${therapistId}`)
    }

    const handleShowDetails = (therapistId) => {
      openTherapistId.value = therapistId
    }

    const handleHideDetails = () => {
      openTherapistId.value = null
    }

    const handleOpenAbout = (therapistId) => {
      selectedTherapist.value = therapists.value.find(t => t.id === therapistId)
      showAboutPopup.value = true
    }

    const handleOpenBooking = (therapistId) => {
      selectedTherapist.value = therapists.value.find(t => t.id === therapistId)
      showBookingPopup.value = true
    }

    const handleOpenCertificates = (therapistId) => {
      selectedTherapist.value = therapists.value.find(t => t.id === therapistId)
      showCertificatesPopup.value = true
    }

    const closeAboutPopup = () => {
      showAboutPopup.value = false
      selectedTherapist.value = null
    }

    const closeBookingPopup = () => {
      showBookingPopup.value = false
      selectedTherapist.value = null
    }

    const closeCertificatesPopup = () => {
      showCertificatesPopup.value = false
      selectedTherapist.value = null
    }

    watch(() => route.query.price, () => {
      if (hasValidPrice.value) {
        loadTherapists()
      } else {
        therapists.value = []
      }
    })

    onMounted(() => {
      if (hasValidPrice.value) {
        loadTherapists()
      }
    })

    return {
      loading,
      therapists,
      hasValidPrice,
      openTherapistId,
      settingsStore,
      viewTherapist,
      handleShowDetails,
      handleHideDetails,
      showAboutPopup,
      showBookingPopup,
      showCertificatesPopup,
      selectedTherapist,
      handleOpenAbout,
      handleOpenBooking,
      handleOpenCertificates,
      closeAboutPopup,
      closeBookingPopup,
      closeCertificatesPopup
    }
  }
}
</script>

<style scoped>
.rtl {
  direction: rtl;
  text-align: right;
}
</style>
