<template>
  <TherapistPopup
    :is-open="isOpen"
    :title="null"
    header-style="dark"
    :show-full-header="true"
    @close="handleClose"
    @update:isOpen="handleUpdateIsOpen"
  >
    <BookingContent
      :therapist="therapist"
      :view-only="viewOnly"
    />
  </TherapistPopup>
</template>

<script>
import TherapistPopup from './TherapistPopup.vue'
import BookingContent from './popups/BookingContent.vue'

export default {
  name: 'BookAppointmentPopup',
  components: {
    TherapistPopup,
    BookingContent
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
    /** When true, shows slots without add-to-cart (for visitors) */
    viewOnly: {
      type: Boolean,
      default: false
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
