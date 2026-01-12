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
      <button @click="loadTherapistDetails" class="bg-white text-primary-500 px-4 py-2 rounded-lg font-medium hover:bg-gray-100">
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Therapist Bio Content -->
    <div v-else-if="therapistDetails && therapistDetails.bio" class="text-white leading-relaxed whitespace-pre-line text-center">
      {{ therapistDetails.bio }}
    </div>

    <!-- No Bio Available -->
    <div v-else-if="therapistDetails" class="text-center py-12 text-white">
      <p>{{ $t('therapistDetail.bioDefault') }}</p>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSettingsStore } from '@/stores/settings'
import api from '@/services/api'

export default {
  name: 'AboutTherapistContent',
  props: {
    therapist: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const { locale } = useI18n()
    const settingsStore = useSettingsStore()
    const loading = ref(false)
    const error = ref(null)
    const therapistDetails = ref(null)

    const loadTherapistDetails = async () => {
      if (therapistDetails.value || loading.value) return

      loading.value = true
      error.value = null

      try {
        const response = await api.get(`/api/ai/therapists/${props.therapist.id}/details`)
        const data = response.data

        if (data.success) {
          therapistDetails.value = data.data
        } else {
          error.value = data.message || 'Failed to load therapist details'
        }
      } catch (err) {
        console.error('Error loading therapist details:', err)
        error.value = 'Failed to load therapist details'
      } finally {
        loading.value = false
      }
    }

    // Initialize with basic therapist data if available
    watch(() => props.therapist, (therapist) => {
      if (therapist) {
        therapistDetails.value = {
          ...therapist,
          name: therapist.name,
          photo: therapist.photo,
          doctor_specialty: therapist.doctor_specialty,
          bio: therapist.bio,
          certificates: therapist.certificates || [],
          rating: therapist.rating,
          total_ratings: therapist.total_ratings
        }
        // Load details if bio is not available
        if (!therapistDetails.value.bio) {
          loadTherapistDetails()
        }
      }
    }, { immediate: true })

    return {
      locale,
      loading,
      error,
      therapistDetails,
      settingsStore,
      loadTherapistDetails
    }
  }
}
</script>
