<template>
  <TherapistPopup
    :is-open="isOpen"
    :title="$t('therapists.whyBestForDiagnosis')"
    header-style="dark"
    @close="handleClose"
    @update:isOpen="handleUpdateIsOpen"
  >
    <WhyThisTherapistContent
      :therapist="therapist"
      :diagnosis-id="diagnosisId"
    />
  </TherapistPopup>
</template>

<script>
import TherapistPopup from './TherapistPopup.vue'
import WhyThisTherapistContent from './popups/WhyThisTherapistContent.vue'

export default {
  name: 'WhyThisTherapistPopup',
  components: {
    TherapistPopup,
    WhyThisTherapistContent
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
    const handleClose = () => {
      emit('close')
      emit('update:isOpen', false)
    }

    const handleUpdateIsOpen = (value) => {
      emit('update:isOpen', value)
    }

    return {
      handleClose,
      handleUpdateIsOpen
    }
  }
}
</script>
