<template>
  <div class="relative">
    <!-- Notification Bell Icon -->
    <button
      @click="toggleNotifications"
      class="relative p-2 rounded-full hover:bg-gray-100 transition-colors"
      :class="locale === 'ar' ? 'ml-4' : 'mr-4'"
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
          @click="markAsRead(message)"
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
                {{ message.therapist_name }}
              </p>
              <p class="text-xs text-gray-500 mb-1">
                {{ message.session_date }} - {{ message.session_time }}
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
        <button
          v-if="hasMore"
          @click="loadAllMessages"
          class="w-full py-3 text-center text-sm font-medium text-primary-600 hover:bg-gray-50 transition-colors"
        >
          {{ $t('messages.seeAll') }}
        </button>
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
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'SessionMessagesNotification',
  setup() {
    const { t, locale } = useI18n()
    
    const showNotifications = ref(false)
    const loading = ref(false)
    const messages = ref([])
    const unreadCount = ref(0)
    const hasMore = ref(false)
    
    const toggleNotifications = async () => {
      showNotifications.value = !showNotifications.value
      if (showNotifications.value && messages.value.length === 0) {
        await loadMessages()
      }
    }
    
    const loadMessages = async (limit = 5) => {
      loading.value = true
      try {
        const response = await api.post('/wp-admin/admin-ajax.php', 
          new URLSearchParams({
            action: 'get_session_messages',
            limit: limit,
            offset: 0,
            nonce: await getNonce('session_messages_nonce')
          })
        )
        
        if (response.data.success) {
          messages.value = response.data.data.messages
          unreadCount.value = response.data.data.unread_count
          hasMore.value = response.data.data.has_more
        }
      } catch (error) {
        console.error('Error loading messages:', error)
      } finally {
        loading.value = false
      }
    }
    
    const loadAllMessages = async () => {
      await loadMessages(100) // Load up to 100 messages
    }
    
    const markAsRead = async (message) => {
      if (message.is_read) return
      
      try {
        await api.post('/wp-admin/admin-ajax.php',
          new URLSearchParams({
            action: 'mark_message_read',
            message_id: message.id,
            nonce: await getNonce('session_messages_nonce')
          })
        )
        
        message.is_read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      } catch (error) {
        console.error('Error marking message as read:', error)
      }
    }
    
    const getNonce = async (name) => {
      // In a real implementation, get nonce from WordPress
      // For now, return a placeholder
      return 'nonce_placeholder'
    }
    
    // Auto-refresh every 30 seconds
    onMounted(() => {
      loadMessages()
      setInterval(() => {
        if (!showNotifications.value) {
          loadMessages()
        }
      }, 30000)
    })
    
    return {
      locale,
      showNotifications,
      loading,
      messages,
      unreadCount,
      hasMore,
      toggleNotifications,
      loadMessages,
      loadAllMessages,
      markAsRead
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

