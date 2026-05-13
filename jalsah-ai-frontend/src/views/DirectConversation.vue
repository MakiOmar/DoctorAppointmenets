<template>
  <div class="dc-page flex flex-col max-w-3xl mx-auto w-full min-h-[calc(100dvh-5rem)] px-3 sm:px-4 py-3 bg-primary-50/40">
    <!-- Thread header: counterparty name + avatar (Jalsah primary palette) -->
    <header
      v-if="!loading"
      class="dc-header flex items-center gap-3 shrink-0 pb-3 border-b border-primary-100"
    >
      <div
        class="dc-header-avatar relative h-11 w-11 shrink-0 rounded-full bg-primary-200 text-primary-800 flex items-center justify-center text-sm font-bold overflow-hidden ring-2 ring-white shadow-sm"
      >
        <img
          v-if="counterparty.avatar_url"
          :src="counterparty.avatar_url"
          :alt="counterparty.name || 'avatar'"
          class="h-full w-full object-cover"
          @error="onAvatarError"
        />
        <span v-else>{{ initials(displayCounterpartyName) }}</span>
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="text-base font-semibold text-primary-900 truncate">
          {{ displayCounterpartyName }}
        </h1>
      </div>
    </header>

    <div v-if="loading" class="flex-1 flex items-center justify-center text-primary-600 py-12">
      {{ $t('common.loading') }}
    </div>

    <template v-else>
      <div
        ref="scrollBox"
        class="dc-thread flex-1 overflow-y-auto py-3 space-y-2 min-h-[200px]"
        style="-webkit-overflow-scrolling: touch"
      >
        <div
          v-for="m in thread"
          :key="m.id"
          :class="[
            'dc-row flex gap-2 max-w-[92%]',
            isMine(m) ? 'flex-row-reverse ms-auto' : 'me-auto',
          ]"
        >
          <div
            class="dc-avatar mt-0.5 h-8 w-8 shrink-0 rounded-full bg-primary-200 text-primary-800 flex items-center justify-center text-[10px] font-bold overflow-hidden ring-1 ring-primary-100"
          >
            <img
              v-if="m.sender_avatar_url && !brokenMsgAvatars[m.id]"
              :src="m.sender_avatar_url"
              :alt="senderLabel(m)"
              class="h-full w-full object-cover"
              @error="onMsgAvatarError(m)"
            />
            <span v-else>{{ initials(senderLabel(m)) }}</span>
          </div>
          <div class="min-w-0 flex flex-col" :class="isMine(m) ? 'items-end' : 'items-start'">
            <div
              :class="[
                'dc-bubble px-3.5 py-2 rounded-2xl shadow-sm max-w-full',
                isMine(m)
                  ? 'bg-primary-600 text-white rounded-br-md'
                  : 'bg-white text-primary-900 border border-primary-100 rounded-bl-md',
              ]"
            >
              <p class="text-sm whitespace-pre-wrap break-words leading-relaxed">{{ m.message || m.body }}</p>
              <div v-if="m.attachments && m.attachments.length" class="mt-2 flex flex-wrap gap-2">
                <button
                  v-for="a in m.attachments.filter(isImageAttachment)"
                  :key="`img-${a.id}`"
                  type="button"
                  class="rounded-lg overflow-hidden ring-1 ring-white/30"
                  @click="openAttachmentLightbox(m.attachments, a.id)"
                >
                  <img :src="a.url" :alt="a.name" class="w-20 h-20 object-cover" />
                </button>
                <a
                  v-for="a in m.attachments.filter((x) => !isImageAttachment(x))"
                  :key="`file-${a.id}`"
                  :href="a.url"
                  target="_blank"
                  rel="noopener"
                  :class="[
                    'text-xs px-2 py-1 rounded border',
                    isMine(m)
                      ? 'border-white/40 bg-primary-700/40 text-white'
                      : 'border-primary-200 bg-primary-50 text-primary-700',
                  ]"
                >{{ a.name }}</a>
              </div>
            </div>
            <p
              :class="[
                'text-[10px] mt-0.5 px-1',
                isMine(m) ? 'text-primary-500' : 'text-primary-400',
              ]"
            >
              {{ formatDate(m.created_at) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Composer bar (Messenger-style, brand colors) -->
      <div
        class="dc-composer shrink-0 pt-2 pb-1 border-t border-primary-100 bg-primary-50/90 backdrop-blur-sm sticky bottom-0 z-10"
      >
        <input
          ref="fileRef"
          type="file"
          class="hidden"
          accept="image/*,video/*,.pdf,.doc,.docx,.txt"
          @change="onFile"
          @click.stop
        />
        <div v-if="pendingFile" class="mb-2 flex items-center gap-2 rounded-lg border border-primary-200 bg-white px-2 py-1.5">
          <img
            v-if="pendingFilePreview"
            :src="pendingFilePreview"
            alt=""
            class="h-9 w-9 rounded object-cover shrink-0"
          />
          <span class="text-xs text-primary-800 truncate flex-1">{{ pendingFile.name }}</span>
          <button
            type="button"
            class="text-primary-600 hover:text-primary-800 text-lg leading-none px-1 snks-dc-inline-remove"
            aria-label="remove"
            @click="removePendingFile"
          >
            ×
          </button>
        </div>
        <!-- Upload progress (large files) -->
        <div v-if="uploadPercent !== null" class="mb-2 rounded-lg border border-primary-200 bg-white px-3 py-2">
          <div class="flex justify-between text-xs text-primary-800 mb-1">
            <span>{{ $t('messages.uploading') }}</span>
            <span class="tabular-nums font-semibold">{{ uploadPercent }}%</span>
          </div>
          <div class="h-2 rounded-full bg-primary-100 overflow-hidden" role="progressbar" :aria-valuenow="uploadPercent" aria-valuemin="0" aria-valuemax="100">
            <div
              class="h-full rounded-full bg-primary-600 transition-[width] duration-150 ease-out"
              :style="{ width: uploadPercent + '%' }"
            />
          </div>
        </div>
        <div class="flex items-end gap-2 rounded-2xl border border-primary-200 bg-white px-2 py-1.5 shadow-sm focus-within:ring-2 focus-within:ring-primary-300 focus-within:border-primary-300">
          <button
            type="button"
            class="shrink-0 h-9 w-9 rounded-full flex items-center justify-center text-primary-600 hover:bg-primary-50 border border-primary-200"
            :title="$t('messages.attach') || 'Attach'"
            aria-label="attach"
            @click="triggerFileInput"
          >
            +
          </button>
          <textarea
            v-model="draft"
            rows="1"
            class="flex-1 min-h-[40px] max-h-32 resize-y border-0 bg-transparent text-sm text-primary-900 placeholder-primary-400 focus:ring-0 py-2"
            :placeholder="$t('messages.typeHere')"
            @keydown.enter.exact.prevent="onEnterSend"
          />
          <button
            type="button"
            class="shrink-0 h-9 px-3 rounded-full bg-primary-600 text-white text-sm font-semibold hover:bg-primary-700 disabled:opacity-50 min-w-[4.5rem]"
            :disabled="sending"
            @click="send"
          >
            <template v-if="sending && uploadPercent !== null">{{ uploadPercent }}%</template>
            <template v-else-if="sending">{{ $t('messages.sendingMessage') }}</template>
            <template v-else>{{ $t('messages.send') }}</template>
          </button>
        </div>
      </div>
    </template>

    <Lightbox
      :is-open="lightboxOpen"
      :images="lightboxImages"
      :initial-index="lightboxIndex"
      @close="closeLightbox"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import Lightbox from '@/components/Lightbox.vue'

const POLL_MS_VISIBLE = 12000
const POLL_MS_HIDDEN = 45000

export default {
  name: 'DirectConversation',
  components: { Lightbox },
  setup() {
    const route = useRoute()
    const { t } = useI18n()
    const authStore = useAuthStore()
    const thread = ref([])
    const loading = ref(true)
    const sending = ref(false)
    const draft = ref('')
    const scrollBox = ref(null)
    const fileRef = ref(null)
    const pendingFile = ref(null)
    const pendingFilePreview = ref('')
    const lightboxOpen = ref(false)
    const lightboxImages = ref([])
    const lightboxIndex = ref(0)
    const counterparty = ref({ user_id: 0, name: '', avatar_url: '' })
    const headerAvatarFailed = ref(false)
    const brokenMsgAvatars = ref({})
    /** null = idle; 0–100 while multipart upload is in progress */
    const uploadPercent = ref(null)
    let pollTimer = null

    const conversationId = () => parseInt(route.params.id, 10)

    const isMine = (m) => {
      const uid = authStore.user?.id || authStore.user?.ID
      return uid && parseInt(m.sender_user_id, 10) === parseInt(uid, 10)
    }

    /** Chat partner display name — never the session-messages hub label. */
    const displayCounterpartyName = computed(() => {
      const fromApi = (counterparty.value.name || '').trim()
      if (fromApi) return fromApi
      for (const m of thread.value) {
        if (!isMine(m)) {
          const sn = (m.sender_name || '').trim()
          if (sn) return sn
        }
      }
      return t('messages.directChat')
    })

    const initials = (name) => {
      const s = (name || '').trim()
      if (!s) return '?'
      const parts = s.split(/\s+/).filter(Boolean)
      if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase().slice(0, 2)
      }
      return s.slice(0, 2).toUpperCase()
    }

    const senderLabel = (m) => {
      if (isMine(m)) {
        const u = authStore.user
        return (u && (u.display_name || u.name || u.user_login)) || ''
      }
      return m.sender_name || ''
    }

    const onAvatarError = () => {
      headerAvatarFailed.value = true
      counterparty.value = { ...counterparty.value, avatar_url: '' }
    }

    const onMsgAvatarError = (m) => {
      const id = m && m.id
      if (id != null) {
        brokenMsgAvatars.value = { ...brokenMsgAvatars.value, [id]: true }
      }
    }

    const maxThreadId = () => {
      let max = 0
      for (const m of thread.value) {
        const id = parseInt(m.id, 10) || 0
        if (id > max) max = id
      }
      return max
    }

    const isNearBottom = () => {
      const el = scrollBox.value
      if (!el) return true
      return el.scrollHeight - el.scrollTop - el.clientHeight < 120
    }

    const scrollToEnd = () => {
      const el = scrollBox.value
      if (el) {
        el.scrollTop = el.scrollHeight
      }
    }

    const mergeMessages = (incoming) => {
      if (!incoming.length) return
      const byId = new Map(thread.value.map((m) => [m.id, m]))
      incoming.forEach((m) => byId.set(m.id, m))
      thread.value = Array.from(byId.values()).sort((a, b) => parseInt(a.id, 10) - parseInt(b.id, 10))
    }

    const load = async () => {
      stopPoll()
      loading.value = true
      headerAvatarFailed.value = false
      try {
        const id = conversationId()
        if (id) {
          try {
            const readRes = await api.post(`/api/ai/direct-conversations/${id}/read`)
            const markedCount = Number(readRes?.data?.data?.marked_count || 0)
            if (markedCount > 0) {
              window.dispatchEvent(
                new CustomEvent('snks-direct-conversation-read', {
                  detail: {
                    conversationId: id,
                    markedCount
                  }
                })
              )
            }
          } catch (readError) {
            console.error('Error marking conversation as read:', readError)
          }
        }
        const res = await api.get(`/api/ai/direct-conversations/${id}/messages`)
        if (res.data.success) {
          const data = res.data.data || {}
          thread.value = data.messages || []
          const cp = data.counterparty
          if (cp && typeof cp === 'object') {
            counterparty.value = {
              user_id: cp.user_id || 0,
              name: cp.name || '',
              avatar_url: headerAvatarFailed.value ? '' : (cp.avatar_url || ''),
            }
          } else {
            counterparty.value = { user_id: 0, name: '', avatar_url: '' }
          }
          await nextTick()
          scrollToEnd()
        }
      } catch (e) {
        console.error(e)
      } finally {
        loading.value = false
        startPoll()
      }
    }

    const pollOnce = async () => {
      if (!authStore.isAuthenticated) return
      const id = conversationId()
      if (!id) return
      const since = maxThreadId()
      if (since < 1) return
      try {
        const res = await api.get(`/api/ai/direct-conversations/${id}/messages`, {
          params: { since_id: since },
        })
        if (!res.data.success) return
        const incoming = res.data.data.messages || []
        if (!incoming.length) return
        const cp = res.data.data.counterparty
        if (cp && typeof cp === 'object' && cp.name) {
          counterparty.value = {
            user_id: cp.user_id || counterparty.value.user_id,
            name: cp.name,
            avatar_url: headerAvatarFailed.value ? counterparty.value.avatar_url : (cp.avatar_url || counterparty.value.avatar_url),
          }
        }
        const stick = isNearBottom()
        mergeMessages(incoming)
        await nextTick()
        if (stick) scrollToEnd()
      } catch (e) {
        console.error(e)
      }
    }

    const stopPoll = () => {
      if (pollTimer) {
        clearTimeout(pollTimer)
        pollTimer = null
      }
    }

    const schedulePoll = () => {
      stopPoll()
      const delay = document.hidden ? POLL_MS_HIDDEN : POLL_MS_VISIBLE
      pollTimer = setTimeout(async () => {
        pollTimer = null
        await pollOnce()
        if (conversationId()) {
          schedulePoll()
        }
      }, delay)
    }

    const startPoll = () => {
      stopPoll()
      if (!authStore.isAuthenticated || !conversationId()) return
      pollTimer = setTimeout(async () => {
        pollTimer = null
        await pollOnce()
        schedulePoll()
      }, 2500)
    }

    const onVisibility = () => {
      stopPoll()
      if (loading.value || !conversationId()) return
      pollTimer = setTimeout(async () => {
        pollTimer = null
        await pollOnce()
        schedulePoll()
      }, 400)
    }

    const parseServerDate = (value) => {
      if (!value) return null
      const raw = String(value).trim()
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(raw)) {
        return new Date(raw.replace(' ', 'T') + 'Z')
      }
      if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/.test(raw)) {
        return new Date(raw + 'Z')
      }
      return new Date(raw)
    }

    const formatDate = (d) => {
      if (!d) return ''
      const date = parseServerDate(d)
      if (!date || Number.isNaN(date.getTime())) return ''
      return date.toLocaleString()
    }

    const triggerFileInput = () => {
      fileRef.value?.click()
    }

    const isImageAttachment = (attachment) => {
      return !!(attachment?.type && attachment.type.startsWith('image/'))
    }

    const openAttachmentLightbox = (attachments, selectedAttachmentId) => {
      const imageAttachments = (attachments || []).filter(isImageAttachment)
      if (!imageAttachments.length) return
      lightboxImages.value = imageAttachments.map((a) => ({
        url: a.url,
        name: a.name,
      }))
      const idx = imageAttachments.findIndex((a) => a.id === selectedAttachmentId)
      lightboxIndex.value = idx >= 0 ? idx : 0
      lightboxOpen.value = true
    }

    const closeLightbox = () => {
      lightboxOpen.value = false
      lightboxImages.value = []
      lightboxIndex.value = 0
    }

    const onFile = () => {
      const f = fileRef.value?.files?.[0]
      pendingFile.value = f || null
      if (pendingFilePreview.value) {
        URL.revokeObjectURL(pendingFilePreview.value)
        pendingFilePreview.value = ''
      }
      if (f && f.type?.startsWith('image/')) {
        pendingFilePreview.value = URL.createObjectURL(f)
      }
    }

    const removePendingFile = () => {
      pendingFile.value = null
      if (pendingFilePreview.value) {
        URL.revokeObjectURL(pendingFilePreview.value)
        pendingFilePreview.value = ''
      }
      if (fileRef.value) fileRef.value.value = ''
    }

    const onEnterSend = () => {
      if (!draft.value.trim() && !pendingFile.value) return
      send()
    }

    const send = async () => {
      if (!authStore.isAuthenticated) return
      const id = conversationId()
      if (!id) return
      sending.value = true
      uploadPercent.value = null
      try {
        let attachmentIds = []
        if (pendingFile.value) {
          uploadPercent.value = 0
          const fd = new FormData()
          fd.append('file', pendingFile.value)
          const up = await api.post('/api/ai/direct-conversations/upload', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
            timeout: 0,
            onUploadProgress: (ev) => {
              const total = ev.total
              if (total && total > 0) {
                uploadPercent.value = Math.min(100, Math.round((ev.loaded * 100) / total))
              } else if (ev.loaded > 0) {
                const cur = uploadPercent.value ?? 0
                uploadPercent.value = Math.min(95, cur < 5 ? 5 : cur + 3)
              }
            },
          })
          uploadPercent.value = null
          if (up.data.success && up.data.data?.id) {
            attachmentIds = [up.data.data.id]
          }
          pendingFile.value = null
          if (pendingFilePreview.value) {
            URL.revokeObjectURL(pendingFilePreview.value)
            pendingFilePreview.value = ''
          }
          if (fileRef.value) fileRef.value.value = ''
        }
        await api.post(`/api/ai/direct-conversations/${id}/messages`, {
          body: draft.value,
          attachment_ids: attachmentIds,
        })
        draft.value = ''
        await pollOnce()
        if (!thread.value.length || maxThreadId() === 0) {
          await load()
        } else {
          await nextTick()
          scrollToEnd()
        }
      } catch (e) {
        console.error(e)
      } finally {
        uploadPercent.value = null
        sending.value = false
      }
    }

    onMounted(() => {
      load()
      document.addEventListener('visibilitychange', onVisibility)
    })

    onUnmounted(() => {
      stopPoll()
      document.removeEventListener('visibilitychange', onVisibility)
      if (pendingFilePreview.value) {
        URL.revokeObjectURL(pendingFilePreview.value)
      }
    })

    watch(() => route.params.id, () => {
      load()
    })

    return {
      thread,
      loading,
      sending,
      uploadPercent,
      draft,
      scrollBox,
      fileRef,
      counterparty,
      displayCounterpartyName,
      isMine,
      send,
      onFile,
      triggerFileInput,
      removePendingFile,
      formatDate,
      isImageAttachment,
      openAttachmentLightbox,
      closeLightbox,
      lightboxOpen,
      lightboxImages,
      lightboxIndex,
      pendingFile,
      pendingFilePreview,
      t,
      initials,
      senderLabel,
      onAvatarError,
      onMsgAvatarError,
      onEnterSend,
      brokenMsgAvatars,
    }
  },
}
</script>
