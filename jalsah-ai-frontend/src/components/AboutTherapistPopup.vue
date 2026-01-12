<template>
  <TherapistPopup
    :is-open="isOpen"
    :title="$t('therapistDetail.about')"
    header-style="dark"
    @close="handleClose"
    @update:isOpen="handleUpdateIsOpen"
  >
    <AboutTherapistContent
      :therapist="therapist"
      @open-certificates="$emit('open-certificates')"
    />
  </TherapistPopup>
</template>

<script>
import TherapistPopup from './TherapistPopup.vue'
import AboutTherapistContent from './popups/AboutTherapistContent.vue'

export default {
  name: 'AboutTherapistPopup',
  components: {
    TherapistPopup,
    AboutTherapistContent
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
  emits: ['close', 'update:isOpen', 'open-certificates'],
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
