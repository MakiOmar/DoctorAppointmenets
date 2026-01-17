<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl text-gray-900">{{ $t('messages.title') }}</h1>
        <p class="mt-2 text-gray-600">{{ $t('messages.subtitle') }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
        <p class="mt-2 text-gray-500">{{ $t('common.loading') }}</p>
      </div>

      <!-- Messages List -->
      <div v-else-if="messages.length > 0" class="space-y-4">
        <div
          v-for="message in messages"
          :key="message.id"
          @click="openMessagePopup(message)"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 cursor-pointer hover:shadow-md transition-shadow"
          :class="!message.is_read ? 'ring-2 ring-blue-100 bg-blue-50' : ''"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <!-- Message Header -->
              <div class="flex items-center space-x-3 mb-3">
                <!-- Unread Indicator -->
                <div
                  class="w-3 h-3 rounded-full flex-shrink-0"
                  :class="message.is_read ? 'bg-gray-300' : 'bg-green-500'"
                ></div>
                
                <!-- Sender Info -->
                <div class="flex items-center space-x-2">
                  <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-sm text-gray-900">
                      {{ message.sender_name || 'معالج' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                      {{ formatDate(message.created_at) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Message Preview -->
              <div class="mb-3">
                <p v-if="message.message" class="text-gray-700 line-clamp-2">
                  {{ message.message }}
                </p>
                <p v-else class="text-gray-500 italic">
                  {{ $t('messages.noTextContent') }}
                </p>
              </div>

              <!-- Attachments Preview -->
              <div v-if="message.attachments && message.attachments.length > 0" class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                </svg>
                <span class="text-sm text-gray-600">
                  {{ message.attachments.length }} {{ $t('messages.attachments') }}
                </span>
                <div class="flex -space-x-1">
                  <div
                    v-for="attachment in message.attachments.slice(0, 3)"
                    :key="attachment.id"
                    class="w-6 h-6 bg-gray-200 rounded border-2 border-white flex items-center justify-center"
                  >
                    <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </div>
                  <div
                    v-if="message.attachments.length > 3"
                    class="w-6 h-6 bg-gray-100 rounded border-2 border-white flex items-center justify-center"
                  >
                    <span class="text-xs text-gray-600">+{{ message.attachments.length - 3 }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Arrow Icon -->
            <div class="flex-shrink-0">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMore" class="text-center pt-6">
          <button
            @click="loadMore"
            :disabled="loadingMore"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loadingMore">{{ $t('common.loading') }}</span>
            <span v-else>{{ $t('messages.loadMore') }}</span>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
        </svg>
        <h3 class="text-lg text-gray-900 mb-2">{{ $t('messages.noMessages') }}</h3>
        <p class="text-gray-500">{{ $t('messages.noMessagesDescription') }}</p>
      </div>
    </div>

    <!-- Message Detail Popup -->
    <div
      v-if="selectedMessage"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="closeMessagePopup"
        ></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg text-gray-900">
                    {{ selectedMessage.sender_name || 'معالج' }}
                  </h3>
                  <p class="text-sm text-gray-500">
                    {{ formatDate(selectedMessage.created_at) }}
                  </p>
                </div>
              </div>
              <button
                @click="closeMessagePopup"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <!-- Message Content -->
            <div class="mb-6">
              <div v-if="selectedMessage.message" class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-800 whitespace-pre-wrap">{{ selectedMessage.message }}</p>
              </div>
              <div v-else class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-gray-500 italic">{{ $t('messages.noTextContent') }}</p>
              </div>
            </div>

            <!-- Attachments -->
            <div v-if="selectedMessage.attachments && selectedMessage.attachments.length > 0">
              <h4 class="text-sm text-gray-900 mb-3">{{ $t('messages.attachments') }}</h4>
              <div class="grid grid-cols-1 gap-3">
                <div
                  v-for="attachment in selectedMessage.attachments"
                  :key="attachment.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </div>
                    <div>
                      <p class="text-sm text-gray-900">{{ attachment.name }}</p>
                      <p class="text-xs text-gray-500">{{ attachment.type }}</p>
                    </div>
                  </div>
                  <button
                    @click="downloadAttachment(attachment)"
                    class="text-primary-600 hover:text-primary-700 text-sm"
                  >
                    {{ $t('messages.download') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="closeMessagePopup"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm transition-colors"
            >
              {{ $t('common.close') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'Notifications',
  setup() {
    const { locale } = useI18n()
    const router = useRouter()
    const authStore = useAuthStore()
    const loading = ref(true)
    const loadingMore = ref(false)
    const messages = ref([])
    const selectedMessage = ref(null)
    const hasMore = ref(true)
    const offset = ref(0)
    const limit = 20

    const loadMessages = async (loadMore = false) => {
      // Only call API if user is authenticated
      if (!authStore.isAuthenticated) {
        // Redirect to login if not authenticated
        router.push('/login')
        return
      }
      
      if (loadMore) {
        loadingMore.value = true
      } else {
        loading.value = true
        offset.value = 0
        messages.value = []
      }

      try {
        const response = await api.get('/api/ai/session-messages', {
          params: {
            limit: limit,
            offset: offset.value
          }
        })

        if (response.data.success) {
          const newMessages = response.data.data.messages || []
          
          if (loadMore) {
            messages.value.push(...newMessages)
          } else {
            messages.value = newMessages
          }
          
          hasMore.value = newMessages.length === limit
          offset.value += newMessages.length
        }
      } catch (error) {
        console.error('Error loading messages:', error)
      } finally {
        loading.value = false
        loadingMore.value = false
      }
    }

    const loadMore = () => {
      loadMessages(true)
    }

    const openMessagePopup = async (message) => {
      selectedMessage.value = message
      // Automatically mark as read when popup opens
      await markAsRead(message)
    }

    const closeMessagePopup = () => {
      selectedMessage.value = null
    }

    const markAsRead = async (message) => {
      // Only call API if user is authenticated
      if (!authStore.isAuthenticated) {
        return
      }
      
      if (message.is_read) return

      try {
        await api.post(`/api/ai/session-messages/${message.id}/read`)
        message.is_read = true
      } catch (error) {
        console.error('Error marking message as read:', error)
      }
    }


    const formatDate = (dateString) => {
      const date = new Date(dateString)
      const now = new Date()
      const diffInHours = (now - date) / (1000 * 60 * 60)

      if (diffInHours < 1) {
        const diffInMinutes = Math.floor((now - date) / (1000 * 60))
        return diffInMinutes < 1 ? 'الآن' : `منذ ${diffInMinutes} دقيقة`
      } else if (diffInHours < 24) {
        return `منذ ${Math.floor(diffInHours)} ساعة`
      } else if (diffInHours < 168) { // 7 days
        const diffInDays = Math.floor(diffInHours / 24)
        return `منذ ${diffInDays} يوم`
      } else {
        return date.toLocaleDateString('ar-SA', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      }
    }

    const downloadAttachment = async (attachment) => {
      try {
        // Create a temporary anchor element to trigger download
        const link = document.createElement('a')
        link.href = attachment.url
        link.download = attachment.name
        link.target = '_blank'
        
        // Add to DOM, click, then remove
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      } catch (error) {
        console.error('Error downloading attachment:', error)
        // Fallback: open in new tab if download fails
        window.open(attachment.url, '_blank')
      }
    }

    onMounted(() => {
      loadMessages()
    })

    return {
      locale,
      loading,
      loadingMore,
      messages,
      selectedMessage,
      hasMore,
      loadMessages,
      loadMore,
      openMessagePopup,
      closeMessagePopup,
      markAsRead,
      formatDate,
      downloadAttachment
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
