import { ref, onMounted } from 'vue'
import api from '@/services/api'

export const googleMeetActive = ref(false)
export const liveStreamProvider = ref('jitsi')
export const useMeetingTimers = ref(true)
let settingsLoaded = false

export async function loadLiveStreamSettings() {
  if (settingsLoaded) {
    return {
      googleMeetActive: googleMeetActive.value,
      liveStreamProvider: liveStreamProvider.value,
      useMeetingTimers: useMeetingTimers.value
    }
  }
  try {
    const { data } = await api.get('/wp-json/jalsah-ai/v1/live-stream-settings')
    liveStreamProvider.value = data?.provider || 'jitsi'
    googleMeetActive.value = !!data?.google_meet_active
    useMeetingTimers.value = data?.use_meeting_timers !== false
  } catch (e) {
    googleMeetActive.value = false
    liveStreamProvider.value = 'jitsi'
    useMeetingTimers.value = true
  }
  settingsLoaded = true
  return {
    googleMeetActive: googleMeetActive.value,
    liveStreamProvider: liveStreamProvider.value,
    useMeetingTimers: useMeetingTimers.value
  }
}

export function openGoogleMeetUrl(url) {
  if (!url) {
    return false
  }
  const opened = window.open(url, '_blank', 'noopener,noreferrer')
  return !!opened
}

export function resolveMeetJoinUrl(entity) {
  if (!entity) {
    return ''
  }
  if (entity.google_meet_join_url) {
    return entity.google_meet_join_url
  }
  if (entity.live_stream_provider === 'google_meet' && entity.join_url) {
    return entity.join_url
  }
  return ''
}

export function isGoogleMeetEntity(entity) {
  if (!entity) {
    return googleMeetActive.value
  }
  return entity.live_stream_provider === 'google_meet' || !!entity.google_meet_join_url
}

export function useLiveMeeting() {
  onMounted(() => {
    loadLiveStreamSettings()
  })

  return {
    googleMeetActive,
    liveStreamProvider,
    useMeetingTimers,
    loadLiveStreamSettings,
    openGoogleMeetUrl,
    resolveMeetJoinUrl,
    isGoogleMeetEntity
  }
}
