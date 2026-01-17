<template>
  <div>
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
      <p class="text-white mt-4">{{ $t('common.loading') }}</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <p class="text-red-300 mb-4">{{ error }}</p>
      <button @click="loadDiagnosisInfo" class="bg-white text-primary-500 px-4 py-2 rounded-lg hover:bg-gray-100">
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Suitability Message Content -->
    <div v-else-if="suitabilityMessage" class="text-white leading-relaxed whitespace-pre-line" :class="locale === 'ar' ? 'text-right' : 'text-left'">
      {{ suitabilityMessage }}
    </div>

    <!-- No Suitability Message -->
    <div v-else class="text-center py-12 text-white">
      <p>{{ $t('therapistDetails.noSuitabilityMessage') }}</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'WhyThisTherapistContent',
  props: {
    therapist: {
      type: Object,
      required: true
    },
    diagnosisId: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const { locale } = useI18n()
    const loading = ref(false)
    const error = ref(null)
    const diagnosisInfo = ref(null)

    const suitabilityMessage = computed(() => {
      if (!props.diagnosisId || !props.therapist.diagnoses) return null
      
      const diagnosis = props.therapist.diagnoses.find(d => d.id.toString() === props.diagnosisId.toString())
      return diagnosis?.suitability_message || null
    })

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

    watch(() => props.diagnosisId, (newId) => {
      if (newId && !diagnosisInfo.value) {
        loadDiagnosisInfo()
      }
    }, { immediate: true })

    return {
      locale,
      loading,
      error,
      suitabilityMessage,
      diagnosisInfo,
      loadDiagnosisInfo
    }
  }
}
</script>
