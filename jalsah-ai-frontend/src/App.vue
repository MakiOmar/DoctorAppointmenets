<template>
  <div id="app" class="min-h-screen bg-gray-50 font-sans" :class="{ 'pb-16': switchUserMode }">
    <!-- Hide header on full-screen meeting room so Jitsi can use the full viewport -->
    <Header v-if="!isMeetingRoute" />

    <!-- Navigation loading overlay (kept below full-screen meeting routes so Jitsi is never covered) -->
    <div
      v-if="isNavigating && !isMeetingRoute"
      class="fixed inset-0 bg-black bg-opacity-20 z-50 flex items-center justify-center"
    >
      <div class="bg-white rounded-lg p-6 shadow-lg flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        <span class="text-gray-700">{{ $t('common.loading') }}</span>
      </div>
    </div>

    <router-view />

    <!-- Switch user mode: floating bar to revert back to admin account -->
    <div
      v-if="switchUserMode"
      class="fixed bottom-0 left-0 right-0 z-40 flex items-center justify-center gap-3 px-4 py-3 shadow-lg bg-primary-600 text-white"
      :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    >
      <span class="text-sm font-medium">{{ $t('switchUser.revertBar.message') }}</span>
      <button
        type="button"
        class="px-4 py-2 text-sm font-medium rounded-md bg-white text-primary-600 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        @click="handleRevertToAdmin"
      >
        {{ $t('switchUser.revertBar.button') }}
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import Header from '@/components/Header.vue'

export default {
  name: 'App',
  components: {
    Header
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
    const { switchUserMode } = storeToRefs(authStore)
    const isNavigating = ref(false)
    const isMeetingRoute = computed(() =>
      route.name === 'MeetingRoom' || route.name === 'RochtahMeetingRoom'
    )

    // Show loading when navigation starts (skip on meeting pages — avoids race with Jitsi + z-index on slow devices)
    router.beforeEach((to, from, next) => {
      if (to.name === 'MeetingRoom' || to.name === 'RochtahMeetingRoom') {
        isNavigating.value = false
      } else {
        isNavigating.value = true
      }
      next()
    })

    // Hide loading when navigation completes
    router.afterEach((to) => {
      if (to.name === 'MeetingRoom' || to.name === 'RochtahMeetingRoom') {
        isNavigating.value = false
        return
      }
      setTimeout(() => {
        isNavigating.value = false
      }, 100)
    })

    const handleRevertToAdmin = () => {
      authStore.revertToAdmin()
      router.push('/switch-user')
    }

    // Add immediate click feedback for router links
    const handleRouterLinkClick = (event) => {
      const target = event.target.closest('a[href]')
      if (target && target.getAttribute('href')?.startsWith('/')) {
        isNavigating.value = true
      }
    }

    document.addEventListener('click', handleRouterLinkClick)

    return {
      isNavigating,
      switchUserMode,
      handleRevertToAdmin,
      isMeetingRoute
    }
  }
}
</script> 