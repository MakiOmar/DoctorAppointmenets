<template>
  <div id="app" class="min-h-screen bg-gray-50 font-sans">
    <Header />
    
    <!-- Navigation Loading Overlay -->
    <div 
      v-if="isNavigating" 
      class="fixed inset-0 bg-black bg-opacity-20 z-50 flex items-center justify-center"
    >
      <div class="bg-white rounded-lg p-6 shadow-lg flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        <span class="text-gray-700 font-medium">{{ $t('common.loading') }}</span>
      </div>
    </div>
    
    <router-view />
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Header from '@/components/Header.vue'

export default {
  name: 'App',
  components: {
    Header
  },
  setup() {
    const router = useRouter()
    const isNavigating = ref(false)
    
    // Show loading immediately when navigation starts
    router.beforeEach((to, from, next) => {
      isNavigating.value = true
      next()
    })
    
    // Hide loading when navigation completes
    router.afterEach(() => {
      // Small delay to ensure smooth transition
      setTimeout(() => {
        isNavigating.value = false
      }, 100)
    })
    
    // Add immediate click feedback for router links
    const handleRouterLinkClick = (event) => {
      const target = event.target.closest('a[href]')
      if (target && target.getAttribute('href')?.startsWith('/')) {
        isNavigating.value = true
      }
    }
    
    // Add event listener for immediate feedback
    document.addEventListener('click', handleRouterLinkClick)
    
    return {
      isNavigating
    }
  }
}
</script> 