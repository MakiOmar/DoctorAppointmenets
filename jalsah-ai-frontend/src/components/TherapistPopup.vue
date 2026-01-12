<template>
  <BaseModal 
    :is-open="isOpen" 
    @close="handleClose" 
    @update:isOpen="handleUpdateIsOpen" 
    :show-header="false" 
    :show-cart-icon="false" 
    custom-bg-color="bg-primary-500"
  >
    <!-- Header with Close Button -->
    <template #header>
      <div 
        class="relative rounded-t-lg"
        :class="headerStyle === 'white' ? 'bg-white' : 'bg-primary-500'"
      >
      <div class="relative flex items-center justify-between px-4 pt-4">
        <!-- Cart Icon with Badge -->
        <router-link
          to="/cart"
          class="relative p-2 text-white hover:opacity-80 transition-opacity"
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
            class="w-6 h-6 text-white fill-current"
          >
            <!-- svg paths unchanged -->
          </svg>

          <span
            v-if="cartItemCount > 0"
            class="absolute -top-1 -right-1
                  bg-secondary-500 text-white text-xs font-bold
                  rounded-full h-5 min-w-[20px]
                  flex items-center justify-center px-1"
          >
            {{ cartItemCount > 99 ? '99+' : cartItemCount }}
          </span>
        </router-link>
        
        <!-- Close Button -->
        <button
          @click="handleClose"
          class="z-10
                bg-white text-gray-800
                hover:bg-gray-100
                p-2 rounded-full shadow-sm
                transition-colors
                flex items-center justify-center"
          :aria-label="$t('common.close')"
        >
          <svg
            class="w-6 h-6 fill-none stroke-current"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

    </div>



        <!-- Title Bar (if title is provided) -->
        <div v-if="title" class="pt-16 pb-4 px-6" :class="headerStyle === 'white' ? '' : 'text-center'">
          <div 
            v-if="headerStyle === 'dark'"
            class="bg-white rounded-lg px-6 py-3 inline-block mx-auto"
          >
            <h2 class="text-xl font-bold text-primary-500 text-center">{{ title }}</h2>
          </div>
          <h2 
            v-else
            class="text-xl font-bold text-center"
            :class="headerStyle === 'white' ? 'text-gray-900' : 'text-white'"
          >
            {{ title }}
          </h2>
        </div>
        
        <!-- No title, just close button -->
        <div v-else class="pt-4 pb-4"></div>
      </div>
    </template>

    <!-- Content Slot (Dark Blue Background) -->
    <div 
      class="therapist-popup-content bg-primary-500 p-6 min-h-[400px]" 
      :dir="locale === 'ar' ? 'rtl' : 'ltr'"
    >
      <slot></slot>
    </div>
  </BaseModal>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
import BaseModal from './BaseModal.vue'

export default {
  name: 'TherapistPopup',
  components: {
    BaseModal
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: null
    },
    headerStyle: {
      type: String,
      default: 'dark', // 'dark' (dark blue with white title bar) or 'white' (white header)
      validator: (value) => ['dark', 'white'].includes(value)
    }
  },
  emits: ['close', 'update:isOpen'],
  setup(props, { emit }) {
    const { locale } = useI18n()
    const cartStore = useCartStore()
    const cartIconExists = ref(true)

    const cartItemCount = computed(() => cartStore.itemCount)

    const handleClose = () => {
      emit('close')
      emit('update:isOpen', false)
    }

    const handleUpdateIsOpen = (value) => {
      emit('update:isOpen', value)
    }

    // Check if cart icon exists
    const checkImageExists = (url) => {
      return new Promise((resolve) => {
        const img = new Image()
        img.onload = () => resolve(true)
        img.onerror = () => resolve(false)
        img.src = url
      })
    }

    onMounted(async () => {
      cartIconExists.value = await checkImageExists('/home/Layer-26.png')
    })

    return {
      locale,
      cartItemCount,
      cartIconExists,
      handleClose,
      handleUpdateIsOpen
    }
  }
}
</script>

<style scoped>
.therapist-popup-content {
  background-color: #112145; /* primary-500 */
}

.rtl {
  direction: rtl;
  text-align: right;
}
</style>
