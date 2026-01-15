<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="handleClose"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
        
        <!-- Modal Content -->
        <div
          :class="[
            'relative rounded-lg shadow-xl max-w-[480px] w-full max-h-[90vh] overflow-hidden flex flex-col pb-4',
            customBgColor || 'bg-white'
          ]"
          :dir="locale === 'ar' ? 'rtl' : 'ltr'"
          @click.stop
        >
          <!-- Header -->
          <div v-if="showHeader" class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
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
              v-if="showCartIcon"
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

          <!-- Custom Header Slot -->
          <div v-if="$slots.header" class="flex-shrink-0">
            <slot name="header"></slot>
          </div>

          <!-- Body Content -->
          <div class="flex-1 overflow-y-auto">
            <slot></slot>
          </div>

          <!-- Footer Slot (optional) -->
          <div v-if="$slots.footer" class="flex-shrink-0 border-t border-gray-200">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCartStore } from '@/stores/cart'
import { useRouter } from 'vue-router'

export default {
  name: 'BaseModal',
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    showHeader: {
      type: Boolean,
      default: true
    },
    showCartIcon: {
      type: Boolean,
      default: true
    },
    customBgColor: {
      type: String,
      default: null
    }
  },
  emits: ['close', 'update:isOpen'],
  setup(props, { emit }) {
    const { locale } = useI18n()
    const cartStore = useCartStore()
    const router = useRouter()
    const cartIconExists = ref(true)

    const cartItemCount = computed(() => cartStore.itemCount)

    const handleClose = () => {
      emit('close')
      emit('update:isOpen', false)
    }

    const handleKeydown = (event) => {
      if (!props.isOpen) return
      
      if (event.key === 'Escape') {
        handleClose()
      }
    }

    watch(() => props.isOpen, (newValue) => {
      if (newValue) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    })

    onMounted(() => {
      document.addEventListener('keydown', handleKeydown)
      if (props.isOpen) {
        document.body.style.overflow = 'hidden'
      }
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeydown)
      document.body.style.overflow = ''
    })

    return {
      locale,
      cartItemCount,
      cartIconExists,
      handleClose
    }
  }
}
</script>

<style scoped>
/* RTL Support */
.rtl {
  direction: rtl;
}

/* White scrollbar styling for modal body */
.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: white;
  border-radius: 4px;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #f0f0f0;
}

/* Firefox scrollbar */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: white transparent;
}
</style>