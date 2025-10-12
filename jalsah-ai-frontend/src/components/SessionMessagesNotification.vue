<template>
  <div class="relative">
    <!-- Notification Bell Icon -->
    <button
      @click="toggleNotifications"
      class="relative p-2 rounded-full hover:bg-gray-100 transition-colors z-50"
      :class="locale === 'ar' ? 'ml-4' : 'mr-4'"
      style="pointer-events: auto; cursor: pointer;"
    >
      <!-- Message Icon -->
      <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
      </svg>
      
      <!-- Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-green-500 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
      <span
        v-else
        class="absolute top-0 right-0 inline-flex items-center justify-center w-3 h-3 transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"
      ></span>
    </button>

    <!-- Notifications Dropdown -->
    <div
      v-if="showNotifications"
      class="absolute left-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-50 border border-gray-200"
      :class="locale === 'ar' ? 'right-auto left-0' : 'right-0 left-auto'"
    >
      <!-- Header -->
      <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('messages.title') }}</h3>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <!-- Messages List -->
      <div v-else-if="messages.length > 0" class="max-h-96 overflow-y-auto">
        <div
          v-for="message in messages"
          :key="message.id"
          @click="openMessage(message)"
          class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors"
          :class="!message.is_read ? 'bg-blue-50' : ''"
        >
          <div class="flex items-start">
            <!-- Unread Indicator -->
            <div class="flex-shrink-0 mt-1" :class="locale === 'ar' ? 'ml-3' : 'mr-3'">
              <div
                class="w-2 h-2 rounded-full"
                :class="message.is_read ? 'bg-gray-300' : 'bg-green-500'"
              ></div>
            </div>
            
            <!-- Message Content -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">
                {{ message.sender_name || 'معالج' }}
              </p>
              <p class="text-xs text-gray-500 mb-1">
                {{ formatDate(message.created_at) }}
              </p>
              <p v-if="message.message" class="text-sm text-gray-700 line-clamp-2">
                {{ message.message }}
              </p>
              
              <!-- Attachments -->
              <div v-if="message.attachments && message.attachments.length > 0" class="mt-2 flex flex-wrap gap-2">
                <div
                  v-for="attachment in message.attachments.slice(0, 3)"
                  :key="attachment.id"
                  class="flex items-center text-xs text-blue-600"
                >
                  <svg class="w-4 h-4" :class="locale === 'ar' ? 'ml-1' : 'mr-1'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                  </svg>
                  {{ attachment.name }}
                </div>
                <span v-if="message.attachments.length > 3" class="text-xs text-gray-500">
                  +{{ message.attachments.length - 3 }} {{ $t('messages.more') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- See All Button -->
        <router-link
          to="/notifications"
          class="block w-full py-3 text-center text-sm font-medium text-primary-600 hover:bg-gray-50 transition-colors border-t border-gray-100"
          @click="showNotifications = false"
        >
          {{ $t('messages.seeAll') }}
        </router-link>
      </div>

      <!-- Empty State -->
      <div v-else class="p-8 text-center">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
        </svg>
        <p class="text-gray-500">{{ $t('messages.noMessages') }}</p>
      </div>
    </div>

    <!-- Click Outside to Close -->
    <div
      v-if="showNotifications"
      @click="showNotifications = false"
      class="fixed inset-0 z-40"
    ></div>

    <!-- Message Detail Popup -->
    <div
      v-if="showMessagePopup && selectedMessage"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="closeMessagePopup"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              {{ $t('messages.title') }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ $t('messages.from') }} {{ selectedMessage.sender_name || 'معالج' }}
            </p>
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
        <div class="p-6">
          <!-- Message Text -->
          <div class="mb-4">
            <p v-if="selectedMessage.message" class="text-gray-800 leading-relaxed">
              {{ selectedMessage.message }}
            </p>
            <p v-else class="text-gray-500 italic">
              {{ $t('messages.noTextContent') }}
            </p>
          </div>

          <!-- Attachments -->
          <div v-if="selectedMessage.attachments && selectedMessage.attachments.length > 0" class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">
              {{ $t('messages.attachments') }}
            </h4>
            <div class="space-y-2">
              <div
                v-for="attachment in selectedMessage.attachments"
                :key="attachment.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-500" :class="locale === 'ar' ? 'ml-2' : 'mr-2'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                  </svg>
                  <span class="text-sm text-gray-700">{{ attachment.name }}</span>
                </div>
                <button
                  @click="downloadAttachment(attachment)"
                  class="px-3 py-1 text-xs font-medium text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded transition-colors"
                >
                  {{ $t('messages.download') }}
                </button>
              </div>
            </div>
          </div>

          <!-- Date -->
          <div class="mt-6 pt-4 border-t border-gray-200">
            <p class="text-xs text-gray-500">
              {{ formatDate(selectedMessage.created_at) }}
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-6 border-t border-gray-200 bg-gray-50 rounded-b-lg">
          <button
            @click="closeMessagePopup"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
          >
            {{ $t('common.close') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import api from '@/services/api'

export default {
  name: 'SessionMessagesNotification',
  setup() {
    const { t, locale } = useI18n()
    const router = useRouter()
    
    const showNotifications = ref(false)
    const loading = ref(false)
    const messages = ref([])
    const unreadCount = ref(0)
    const hasMore = ref(false)
    const selectedMessage = ref(null)
    const showMessagePopup = ref(false)
    
    const toggleNotifications = async () => {
      console.log('Notification bell clicked!')
      showNotifications.value = !showNotifications.value
      console.log('showNotifications:', showNotifications.value)
      if (showNotifications.value && messages.value.length === 0) {
        await loadMessages()
      }
    }
    
    const loadMessages = async (limit = 5) => {
      loading.value = true
      try {
        const response = await api.get('/api/ai/session-messages', {
          params: {
            limit: limit,
            offset: 0
          }
        })
        
        if (response.data.success) {
          messages.value = response.data.data.messages || []
          unreadCount.value = response.data.data.unread_count || 0
          hasMore.value = response.data.data.has_more || false
          console.log('Messages loaded:', messages.value.length)
        }
      } catch (error) {
        console.error('Error loading messages:', error)
      } finally {
        loading.value = false
      }
    }
    
    
    const openMessage = async (message) => {
      console.log('Opening message:', message.id)
      
      // Mark as read if not already read
      if (!message.is_read) {
        await markAsRead(message)
      }
      
      // Show message popup
      selectedMessage.value = message
      showMessagePopup.value = true
      showNotifications.value = false // Close dropdown
    }
    
    const closeMessagePopup = () => {
      selectedMessage.value = null
      showMessagePopup.value = false
    }
    
    const downloadAttachment = async (attachment) => {
      try {
        const link = document.createElement('a')
        link.href = attachment.url
        link.download = attachment.name
        link.target = '_blank'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
      } catch (error) {
        console.error('Error downloading attachment:', error)
        window.open(attachment.url, '_blank')
      }
    }
    
    const markAsRead = async (message) => {
      if (message.is_read) return
      
      try {
        await api.post(`/api/ai/session-messages/${message.id}/read`)
        
        // Find and update the message in the messages array to ensure reactivity
        const messageIndex = messages.value.findIndex(m => m.id === message.id)
        if (messageIndex !== -1) {
          messages.value[messageIndex].is_read = true
        }
        
        // Also update the original message object
        message.is_read = true
        
        // Update unread count
        unreadCount.value = Math.max(0, unreadCount.value - 1)
        
        console.log('Message marked as read:', message.id)
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
    
    // Auto-refresh every 20 seconds
    onMounted(() => {
      console.log('SessionMessagesNotification component mounted')
      loadMessages()
      setInterval(() => {
        if (!showNotifications.value) {
          loadMessages()
        }
      }, 20000)
    })
    
    return {
      locale,
      showNotifications,
      loading,
      messages,
      unreadCount,
      hasMore,
      selectedMessage,
      showMessagePopup,
      toggleNotifications,
      loadMessages,
      openMessage,
      closeMessagePopup,
      downloadAttachment,
      markAsRead,
      formatDate
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

