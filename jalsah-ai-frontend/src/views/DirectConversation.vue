<template>
  <div class="max-w-3xl mx-auto px-4 py-6">
    <h1 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('messages.title') }}</h1>
    <div v-if="loading" class="text-center py-8 text-gray-500">{{ $t('common.loading') }}</div>
    <div
      v-else
      ref="scrollBox"
      class="border border-gray-200 rounded-lg bg-gray-50 p-4 mb-4 max-h-[60vh] overflow-y-auto space-y-3"
    >
      <div
        v-for="m in thread"
        :key="m.id"
        :class="isMine(m) ? 'ml-12 bg-blue-50 rounded-lg p-3' : 'mr-12 bg-white rounded-lg p-3 border border-gray-100'"
      >
        <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ m.message || m.body }}</p>
        <div v-if="m.attachments && m.attachments.length" class="mt-2 space-y-1">
          <a
            v-for="a in m.attachments"
            :key="a.id"
            :href="a.url"
            target="_blank"
            rel="noopener"
            class="text-xs text-primary-600 block"
          >{{ a.name }}</a>
        </div>
        <p class="text-xs text-gray-400 mt-1">{{ formatDate(m.created_at) }}</p>
      </div>
    </div>
    <div class="flex flex-col gap-2">
      <textarea
        v-model="draft"
        rows="3"
        class="w-full border border-gray-300 rounded-md p-2 text-sm"
        :placeholder="$t('messages.typeHere')"
      />
      <div class="flex items-center gap-2">
        <input ref="fileRef" type="file" class="text-sm" @change="onFile" />
        <button
          type="button"
          class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm disabled:opacity-50"
          :disabled="sending"
          @click="send"
        >
          {{ $t('messages.send') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const POLL_MS_VISIBLE = 12000
const POLL_MS_HIDDEN = 45000

export default {
  name: 'DirectConversation',
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
    let pollTimer = null

    const conversationId = () => parseInt(route.params.id, 10)

    const isMine = (m) => {
      const uid = authStore.user?.id || authStore.user?.ID
      return uid && parseInt(m.sender_user_id, 10) === parseInt(uid, 10)
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
      try {
        const id = conversationId()
        const res = await api.get(`/api/ai/direct-conversations/${id}/messages`)
        if (res.data.success) {
          thread.value = res.data.data.messages || []
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
      // Quick catch-up when returning to the tab; hidden tab uses longer spacing via schedulePoll.
      pollTimer = setTimeout(async () => {
        pollTimer = null
        await pollOnce()
        schedulePoll()
      }, 400)
    }

    const formatDate = (d) => {
      if (!d) return ''
      return new Date(d).toLocaleString()
    }

    const onFile = () => {
      const f = fileRef.value?.files?.[0]
      pendingFile.value = f || null
    }

    const send = async () => {
      if (!authStore.isAuthenticated) return
      const id = conversationId()
      if (!id) return
      sending.value = true
      try {
        let attachmentIds = []
        if (pendingFile.value) {
          const fd = new FormData()
          fd.append('file', pendingFile.value)
          const up = await api.post('/api/ai/direct-conversations/upload', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
          })
          if (up.data.success && up.data.data?.id) {
            attachmentIds = [up.data.data.id]
          }
          pendingFile.value = null
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
    })

    watch(() => route.params.id, () => {
      load()
    })

    return {
      thread,
      loading,
      sending,
      draft,
      scrollBox,
      fileRef,
      isMine,
      send,
      onFile,
      formatDate,
      t,
    }
  },
}
</script>
