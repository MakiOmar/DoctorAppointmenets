<template>
  <!-- z-[100]: stay above app nav overlay (z-50) on slow devices where timing races otherwise hide Jitsi -->
  <div
    class="fixed inset-0 bg-gray-900 flex flex-col z-[100] min-h-[100dvh] min-h-[100vh]"
    style="min-height: -webkit-fill-available"
  >
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
      id="meeting-guest"
      class="absolute inset-0 w-full h-full min-h-0"
    ></div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
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
let onResizeBound = null

const JITSI_SCRIPT_URL = 'https://jitsiserver.jalsah.app/external_api.js'

function jitsiApiReady() {
  return typeof window.JitsiMeetExternalAPI === 'function'
}

/** Some browsers leave a <script> tag after a failed/blocked load — do not resolve until the API exists. */
function loadJitsiScript(src, timeoutMs = 12000) {
  return new Promise((resolve, reject) => {
    if (jitsiApiReady()) {
      resolve()
      return
    }

    const appendFresh = () => {
      const script = document.createElement('script')
      script.src = src
      script.async = true
      const onLoad = () => {
        if (jitsiApiReady()) {
          resolve()
        } else {
          reject(new Error('Jitsi script loaded but API missing'))
        }
      }
      script.onload = onLoad
      script.onerror = () => reject(new Error('Failed to load Jitsi script'))
      document.head.appendChild(script)
    }

    const existing = document.querySelector(`script[src="${src}"]`)
    if (existing) {
      const start = Date.now()
      const poll = setInterval(() => {
        if (jitsiApiReady()) {
          clearInterval(poll)
          resolve()
        } else if (Date.now() - start > timeoutMs) {
          clearInterval(poll)
          try {
            existing.remove()
          } catch (e) {
            // ignore
          }
          appendFresh()
        }
      }, 80)
      return
    }

    appendFresh()
  })
}

function getContainerPixelSize(containerEl) {
  const rect = containerEl.getBoundingClientRect()
  let w = Math.floor(rect.width)
  let h = Math.floor(rect.height)
  if (w < 200 || h < 200) {
    w = Math.max(w, window.innerWidth || 0, 320)
    h = Math.max(h, window.innerHeight || 0, 400)
  }
  return { width: w, height: h }
}

function syncJitsiIframeSize() {
  const api = meetAPI.value
  const container = document.querySelector('#meeting-guest')
  if (!api || !container) return
  const { width, height } = getContainerPixelSize(container)
  const iframe = typeof api.getIFrame === 'function' ? api.getIFrame() : container.querySelector('iframe')
  if (iframe && iframe.style) {
    iframe.style.width = `${width}px`
    iframe.style.height = `${height}px`
  }
}

const safeReturnUrl = computed(() => {
  const returnUrlParam = route.query.returnUrl
  if (typeof returnUrlParam === 'string' && returnUrlParam.startsWith('/')) {
    return returnUrlParam
  }

  const role = authStore.user?.role
  if (role === 'doctor' || role === 'therapist') return '/doctor'
  return '/appointments'
})

function hideJitsiLogo(containerId = '#meeting-guest', apiInstance = null) {
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
      // CORS / cross-origin iframe
    }
  } catch (err) {
    // ignore
  }
}

function startLogoHidePolling(containerId = '#meeting-guest') {
  if (logoHideInterval) return
  logoHideInterval = setInterval(() => hideJitsiLogo(containerId), 500)
}

function stopLogoHidePolling() {
  if (logoHideInterval) {
    clearInterval(logoHideInterval)
    logoHideInterval = null
  }
}

async function markUserJoined(timetableId) {
  // Best-effort: meeting links can be opened by guests, so JWT might not exist.
  if (!timetableId) return

  try {
    const role = authStore.user?.role
    const isTherapist = role === 'doctor' || role === 'therapist'
    const endpoint = isTherapist
      ? `/wp-json/jalsah-ai/v1/session/${timetableId}/therapist-join`
      : `/wp-json/jalsah-ai/v1/session/${timetableId}/patient-join`

    await api.post(endpoint)
  } catch (e) {
    // Ignore join notifications failures (e.g., user is a guest / token missing).
  }
}

function initJitsi(roomName, displayName, timetableId) {
  const meetingContainer = document.querySelector('#meeting-guest')
  if (!meetingContainer) {
    throw new Error('Meeting container not found')
  }

  const { width, height } = getContainerPixelSize(meetingContainer)

  const options = {
    parentNode: meetingContainer,
    roomName,
    width,
    height,
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
      APP_NAME: 'Jalsah AI',
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

  syncJitsiIframeSize()
  onResizeBound = () => syncJitsiIframeSize()
  window.addEventListener('resize', onResizeBound, { passive: true })
  window.addEventListener('orientationchange', onResizeBound, { passive: true })

  meetAPI.value.addListener('videoConferenceJoined', () => {
    startLogoHidePolling('#meeting-guest')
    setTimeout(() => hideJitsiLogo('#meeting-guest', meetAPI.value), 500)
    setTimeout(() => hideJitsiLogo('#meeting-guest', meetAPI.value), 1500)

    // Notify backend (patient/therapist join tracking) - best-effort.
    void markUserJoined(timetableId)
  })

  meetAPI.value.addListener('videoConferenceLeft', () => {
    stopLogoHidePolling()
    if (!hasRedirected) {
      hasRedirected = true
      router.push(safeReturnUrl.value)
    }
  })

  meetAPI.value.addListener('readyToClose', () => {
    stopLogoHidePolling()
    if (!hasRedirected) {
      hasRedirected = true
      router.push(safeReturnUrl.value)
    }
  })
}

onMounted(async () => {
  const token = route.params.token
  if (!token) {
    status.value = 'error'
    errorMessage.value = 'رابط غير صالح.'
    return
  }

  try {
    const { data } = await api.get('/wp-json/jalsah-ai/v1/meeting-by-token', {
      params: { token }
    })
    const roomName = data?.room_name
    const displayName = data?.display_name
    const timetableId = data?.timetable_id
    if (!roomName) {
      status.value = 'error'
      errorMessage.value = data?.message || 'رابط الجلسة غير صالح أو منتهي الصلاحية.'
      return
    }

    await loadJitsiScript(JITSI_SCRIPT_URL)
    await nextTick()
    await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)))
    initJitsi(roomName, displayName, timetableId)
  } catch (err) {
    status.value = 'error'
    const msg = err.response?.data?.message || err.response?.data?.code || err.message
    errorMessage.value = typeof msg === 'string' ? msg : 'رابط الجلسة غير صالح أو منتهي الصلاحية.'
    toast.error(errorMessage.value)
  }
})

onBeforeUnmount(() => {
  if (onResizeBound) {
    window.removeEventListener('resize', onResizeBound)
    window.removeEventListener('orientationchange', onResizeBound)
    onResizeBound = null
  }
  stopLogoHidePolling()
  if (meetAPI.value && typeof meetAPI.value.dispose === 'function') {
    try {
      meetAPI.value.dispose()
    } catch (e) {
      console.warn('Error disposing Jitsi meeting', e)
    }
  }
})
</script>
