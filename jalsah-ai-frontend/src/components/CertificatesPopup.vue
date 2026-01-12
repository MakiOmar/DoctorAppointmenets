<template>
  <TherapistPopup
    :is-open="isOpen"
    :title="null"
    header-style="dark"
    @close="handleClose"
    @update:isOpen="handleUpdateIsOpen"
  >
    <CertificatesContent
      :therapist="therapist"
    />
  </TherapistPopup>
</template>

<script>
import TherapistPopup from './TherapistPopup.vue'
import CertificatesContent from './popups/CertificatesContent.vue'

export default {
  name: 'CertificatesPopup',
  components: {
    TherapistPopup,
    CertificatesContent
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
