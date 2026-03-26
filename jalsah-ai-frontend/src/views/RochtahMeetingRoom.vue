<template>
  <div class="fixed inset-0 bg-gray-900 flex flex-col z-0">
    <!-- Error state -->
    <div
      v-if="status === 'error'"
      class="flex-1 flex items-center justify-center text-white px-4"
    >
      <div class="text-center max-w-md">
        <p class="text-red-300 mb-4">{{ errorMessage }}</p>
        <router-link
          :to="safeReturnUrl"
          class="inline-block px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600"
        >
          {{ $t('common.back') || 'رجوع' }}
        </router-link>
      </div>
    </div>

    <!-- Jitsi meeting container: full viewport so iframe sizes correctly -->
    <div
      v-show="status !== 'error'"
      id="meeting-rochtah"
      class="absolute inset-0 w-full h-full"
    ></div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const status = ref('ready') // 'ready' | 'error'
const errorMessage = ref('')
const meetAPI = ref(null)

let logoHideInterval = null
let hasRedirected = false

const JITSI_SCRIPT_URL = 'https://jitsiserver.jalsah.app/external_api.js'

const safeReturnUrl = computed(() => {
  const returnUrlParam = route.query.returnUrl
  if (typeof returnUrlParam === 'string' && returnUrlParam.startsWith('/')) {
    return returnUrlParam
  }

  const role = authStore.user?.role
  if (role === 'doctor' || role === 'therapist') return '/doctor'
  return '/appointments'
})

function hideJitsiLogo(containerId = '#meeting-rochtah') {
  try {
    const meetingContainer = document.querySelector(containerId)
    if (!meetingContainer) return

    const iframe = meetingContainer.querySelector('iframe')
    if (!iframe) return

    try {
      const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document
      if (iframeDoc) {
        const css = `
          .watermark, .leftwatermark, .rightwatermark,
          [class*="watermark"], [class*="jitsi-logo"], [id*="watermark"], [id*="jitsi-logo"],
          .powered-by, [class*="poweredby"] { display: none !important; visibility: hidden !important; }
        `

        let style = iframeDoc.getElementById('jalsah-hide-logo-style')
        if (!style) {
          style = iframeDoc.createElement('style')
          style.id = 'jalsah-hide-logo-style'
          style.textContent = css
          iframeDoc.head.appendChild(style)
        } else {
          style.textContent = css
        }
      }
    } catch (e) {
      // CORS / cross-origin iframe - ignore.
    }
  } catch (err) {
    // ignore
  }
}

function startLogoHidePolling(containerId = '#meeting-rochtah') {
  if (logoHideInterval) return
  logoHideInterval = setInterval(() => hideJitsiLogo(containerId), 500)
}

function stopLogoHidePolling() {
  if (logoHideInterval) {
    clearInterval(logoHideInterval)
    logoHideInterval = null
  }
}

function loadScript(src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) {
      resolve()
      return
    }
    const script = document.createElement('script')
    script.src = src
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Failed to load Jitsi script'))
    document.head.appendChild(script)
  })
}

function initJitsi(roomName, displayName) {
  const meetingContainer = document.querySelector('#meeting-rochtah')
  if (!meetingContainer) {
    throw new Error('Meeting container not found')
  }

  const options = {
    parentNode: meetingContainer,
    roomName,
    width: '100%',
    height: '100%',
    configOverwrite: {
      prejoinPageEnabled: false,
      startWithAudioMuted: false,
      startWithVideoMuted: false,
      enableClosePage: true,
      enableWelcomePage: false,
      participantsPane: { enabled: true }
    },
    interfaceConfigOverwrite: {
      prejoinPageEnabled: false,
      APP_NAME: 'Jalsah Rochtah',
      DEFAULT_BACKGROUND: '#1a1a1a',
      SHOW_JITSI_WATERMARK: false,
      HIDE_DEEP_LINKING_LOGO: true,
      SHOW_BRAND_WATERMARK: false,
      SHOW_POWERED_BY: false,
      DISPLAY_WELCOME_FOOTER: false,
      TOOLBAR_ALWAYS_VISIBLE: true
    }
  }

  const JitsiMeetExternalAPI = window.JitsiMeetExternalAPI
  if (!JitsiMeetExternalAPI) {
    throw new Error('Jitsi API not loaded')
  }

  meetAPI.value = new JitsiMeetExternalAPI('jitsiserver.jalsah.app', options)
  meetAPI.value.executeCommand('displayName', displayName || 'مشارك')

  meetAPI.value.addListener('videoConferenceJoined', () => {
    startLogoHidePolling('#meeting-rochtah')
    setTimeout(() => hideJitsiLogo('#meeting-rochtah'), 500)
    setTimeout(() => hideJitsiLogo('#meeting-rochtah'), 1500)
    setTimeout(() => hideJitsiLogo('#meeting-rochtah'), 3000)
  })

  const redirectBack = () => {
    stopLogoHidePolling()
    if (!hasRedirected) {
      hasRedirected = true
      router.push(safeReturnUrl.value)
    }
  }

  meetAPI.value.addListener('videoConferenceLeft', redirectBack)
  meetAPI.value.addListener('readyToClose', redirectBack)
}

onMounted(async () => {
  const bookingId = route.params.bookingId
  if (!bookingId) {
    status.value = 'error'
    errorMessage.value = 'رابط غير صالح.'
    return
  }

  try {
    const { data } = await api.get('/wp-json/jalsah-ai/v1/rochtah-meeting-details', {
      params: { booking_id: bookingId }
    })

    const roomName = data?.room_name
    if (!roomName) {
      status.value = 'error'
      errorMessage.value = data?.message || 'تعذر العثور على غرفة الاجتماع.'
      return
    }

    const displayName =
      authStore.user?.name || authStore.user?.username || authStore.user?.email || 'مشارك'

    await loadScript(JITSI_SCRIPT_URL)
    await nextTick()
    initJitsi(roomName, displayName)
  } catch (err) {
    status.value = 'error'
    const msg = err?.response?.data?.message || err?.response?.data?.code || err?.message
    errorMessage.value = typeof msg === 'string' ? msg : 'تعذر بدء اجتماع الروشتا.'
    toast.error(errorMessage.value)
  }
})

onBeforeUnmount(() => {
  stopLogoHidePolling()
  if (meetAPI.value && typeof meetAPI.value.dispose === 'function') {
    try {
      meetAPI.value.dispose()
    } catch (e) {
      console.warn('Error disposing rochtah meeting', e)
    }
  }
})
</script>

