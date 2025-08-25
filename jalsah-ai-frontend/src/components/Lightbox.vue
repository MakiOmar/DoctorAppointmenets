<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center"
        @click="closeLightbox"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-75"></div>
        
        <!-- Lightbox Content -->
        <div class="relative z-10 max-w-4xl max-h-[90vh] mx-4" @click.stop>
                     <!-- Close Button -->
           <button
             @click="closeLightbox"
             class="absolute -top-8 right-0 text-white hover:text-gray-300 transition-colors"
             :aria-label="$t('common.close')"
           >
             <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
             </svg>
           </button>
          
                     <!-- Navigation Buttons -->
           <button
             @click="previousImage"
             :disabled="images.length <= 1"
             class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 rounded-full p-3 z-20 disabled:opacity-30 disabled:cursor-not-allowed"
             :aria-label="$t('common.previous')"
           >
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
             </svg>
           </button>
           
           <button
             @click="nextImage"
             :disabled="images.length <= 1"
             class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 transition-colors bg-black bg-opacity-50 rounded-full p-3 z-20 disabled:opacity-30 disabled:cursor-not-allowed"
             :aria-label="$t('common.next')"
           >
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
             </svg>
           </button>
          
          <!-- Image Container -->
          <div class="relative">
            <img
              :src="currentImage.url"
              :alt="currentImage.name || currentImage.alt || 'Certificate'"
              class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl"
              @load="imageLoaded = true"
              @error="imageLoaded = false"
            />
            
            <!-- Loading Spinner -->
            <div v-if="!imageLoaded" class="absolute inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 rounded-lg">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
            </div>
          </div>
          
          <!-- Image Info -->
          <div v-if="currentImage.name" class="mt-4 text-center text-white">
            <h3 class="text-lg font-semibold">{{ currentImage.name }}</h3>
          </div>
          
                     <!-- Image Counter -->
           <div class="mt-2 text-center text-white text-sm">
             {{ currentIndex + 1 }} / {{ images.length }}
           </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

export default {
  name: 'Lightbox',
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    images: {
      type: Array,
      default: () => []
    },
    initialIndex: {
      type: Number,
      default: 0
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const currentIndex = ref(props.initialIndex)
    const imageLoaded = ref(false)
    
    const currentImage = computed(() => {
      return props.images[currentIndex.value] || {}
    })
    
    const closeLightbox = () => {
      emit('close')
    }
    
    const nextImage = () => {
      if (props.images.length > 1) {
        currentIndex.value = (currentIndex.value + 1) % props.images.length
        imageLoaded.value = false
      }
    }
    
    const previousImage = () => {
      if (props.images.length > 1) {
        currentIndex.value = currentIndex.value === 0 
          ? props.images.length - 1 
          : currentIndex.value - 1
        imageLoaded.value = false
      }
    }
    
    const handleKeydown = (event) => {
      if (!props.isOpen) return
      
      switch (event.key) {
        case 'Escape':
          closeLightbox()
          break
        case 'ArrowRight':
          nextImage()
          break
        case 'ArrowLeft':
          previousImage()
          break
      }
    }
    
    // Reset image loaded state when lightbox opens
    watch(() => props.isOpen, (newValue) => {
      if (newValue) {
        imageLoaded.value = false

      }
    })
    
    // Update current index when initial index changes
    watch(() => props.initialIndex, (newValue) => {
      currentIndex.value = newValue
    })
    
    onMounted(() => {
      document.addEventListener('keydown', handleKeydown)
    })
    
    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeydown)
    })
    
    return {
      currentIndex,
      currentImage,
      imageLoaded,
      closeLightbox,
      nextImage,
      previousImage
    }
  }
}
</script>
