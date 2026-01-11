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
        <p class="text-gray-600 mt-4">{{ $t('common.loading') }}</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <p class="text-red-600 mb-4">{{ error }}</p>
        <button @click="loadDiagnosisInfo" class="btn-secondary">
          {{ $t('common.retry') }}
        </button>
      </div>

      <!-- Content -->
      <div v-else-if="suitabilityMessage" class="space-y-6">
        <!-- Therapist Header -->
        <div class="flex items-start gap-6" :class="locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
          <!-- Photo -->
          <div class="flex-shrink-0">
            <img
              :src="therapist.photo || '/default-therapist.svg'"
              :alt="therapist.name"
              class="w-32 h-32 rounded-lg object-cover"
              :class="therapist.photo ? '' : 'bg-gray-100 p-4'"
            />
          </div>

          <!-- Name and Specialty -->
          <div class="flex-1">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ therapist.name }}</h2>
            <p v-if="therapist.doctor_specialty" class="text-lg text-gray-600">
              {{ therapist.doctor_specialty }}
            </p>
          </div>
        </div>

        <!-- Diagnosis Info -->
        <div v-if="diagnosisInfo" class="bg-primary-50 border border-primary-200 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-primary-900 mb-2">{{ $t('therapists.whyBestForDiagnosis') }}</h3>
          <p class="text-primary-800 leading-relaxed whitespace-pre-line">{{ suitabilityMessage }}</p>
        </div>

        <!-- Suitability Message (if no diagnosis info) -->
        <div v-else class="bg-primary-50 border border-primary-200 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-primary-900 mb-2">{{ $t('therapists.whyBestForDiagnosis') }}</h3>
          <p class="text-primary-800 leading-relaxed whitespace-pre-line">{{ suitabilityMessage }}</p>
        </div>
      </div>

      <!-- No Suitability Message -->
      <div v-else class="text-center py-12">
        <p class="text-gray-600">{{ $t('therapistDetails.noSuitabilityMessage') }}</p>
      </div>
    </div>
  </BaseModal>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
import api from '@/services/api'
import BaseModal from './BaseModal.vue'

export default {
  name: 'WhyThisTherapistPopup',
  components: {
    BaseModal
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    therapist: {
      type: Object,
      required: true
    },
    diagnosisId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ['close', 'update:isOpen'],
  setup(props, { emit }) {
    const { locale } = useI18n()
    const cartStore = useCartStore()
    const cartIconExists = ref(true)
    const loading = ref(false)
    const error = ref(null)
    const diagnosisInfo = ref(null)

    const cartItemCount = computed(() => cartStore.itemCount)

    const suitabilityMessage = computed(() => {
      if (!props.diagnosisId || !props.therapist.diagnoses) return null
      
      const diagnosis = props.therapist.diagnoses.find(d => d.id.toString() === props.diagnosisId.toString())
      return diagnosis?.suitability_message || null
    })

    const handleClose = () => {
      emit('close')
      emit('update:isOpen', false)
    }

    const handleUpdateIsOpen = (value) => {
      emit('update:isOpen', value)
    }

    const loadDiagnosisInfo = async () => {
      if (!props.diagnosisId) return

      loading.value = true
      error.value = null

      try {
        const response = await api.get(`/api/ai/diagnoses/${props.diagnosisId}`)
        const data = response.data

        if (data.success) {
          diagnosisInfo.value = data.data
        } else {
          error.value = data.message || 'Failed to load diagnosis information'
        }
      } catch (err) {
        console.error('Error loading diagnosis info:', err)
        // Don't set error for this - suitability message should still work
      } finally {
        loading.value = false
      }
    }

    watch(() => props.isOpen, (newValue) => {
      if (newValue && props.diagnosisId && !diagnosisInfo.value) {
        loadDiagnosisInfo()
      }
    })

    return {
      locale,
      cartItemCount,
      cartIconExists,
      loading,
      error,
      suitabilityMessage,
      diagnosisInfo,
      handleClose,
      handleUpdateIsOpen,
      loadDiagnosisInfo
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