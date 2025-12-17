<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
             <!-- Header -->
       <div class="text-center mb-8">
         <h1 class="text-3xl font-bold text-gray-900 mb-4">
           {{ $t('chatDiagnosis.title') }}
         </h1>
         <p class="text-lg text-gray-600">
           {{ $t('chatDiagnosis.subtitle') }}
         </p>
         
         <!-- Question Counter -->
         <div v-if="messages.length > 1" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
           <div class="flex items-center justify-center space-x-2" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-2'">
             <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
             </svg>
             <span class="text-sm text-blue-700">
               {{ $t('chatDiagnosis.questionCounter', { count: aiQuestionsCount }) }}
             </span>
           </div>
         </div>
         
         <!-- Medical Disclaimer -->
         <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
           <div class="flex items-center">
             <div class="flex-shrink-0">
               <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                 <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
               </svg>
             </div>
             <div class="ml-3" :class="$i18n.locale === 'ar' ? 'mr-3' : 'ml-3'">
               <p class="text-sm text-yellow-800">
                 <strong>{{ $t('chatDiagnosis.disclaimer.title') }}:</strong> {{ $t('chatDiagnosis.disclaimer.text') }}
               </p>
             </div>
           </div>
         </div>
       </div>

      <!-- Checking Prompt Availability -->
      <div v-if="checkingPrompt" class="card">
        <div class="text-center py-8">
          <div class="mb-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
          </div>
          <p class="text-gray-600">Loading...</p>
        </div>
      </div>

      <!-- Prompt Not Available -->
      <div v-else-if="!promptAvailable" class="card">
        <div class="text-center py-8">
          <div class="mb-4">
            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h2 class="text-xl font-bold text-gray-900 mb-2">Chat Diagnosis Unavailable</h2>
          <p class="text-gray-600 mb-4">The chat diagnosis feature is currently unavailable. Please contact the administrator.</p>
        </div>
      </div>

      <!-- Loading Diagnosis -->
      <div v-else-if="isLoadingDiagnosis" class="card">
        <div class="text-center py-8">
          <div class="mb-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
          </div>
          <p class="text-gray-600">{{ $t('chatDiagnosis.loadingPrevious') }}</p>
        </div>
      </div>

      <!-- Chat Container -->
      <div v-else class="card">
        <div class="flex flex-col h-96">
          <!-- Chat Messages -->
          <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="chatContainer">
            <!-- Messages -->
            <div v-for="(message, index) in messages" :key="index" class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
              <div 
                class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                :class="message.role === 'user' 
                  ? 'bg-primary-600 text-white' 
                  : 'bg-gray-100 text-gray-900'"
              >
                <div class="flex items-start space-x-2" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-2'">
                  <div v-if="message.role === 'assistant'" class="flex-shrink-0">
                    <div class="w-6 h-6 bg-primary-600 rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm" v-html="formatMessage(message.content || '')"></p>
                    <p class="text-xs opacity-75 mt-1">{{ formatTime(message.timestamp) }}</p>
                  </div>
                  <div v-if="message.role === 'user'" class="flex-shrink-0">
                    <div class="w-6 h-6 bg-gray-400 rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Typing Indicator -->
            <div v-if="isTyping" class="flex justify-start">
              <div class="bg-gray-100 text-gray-900 max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
                <div class="flex items-center space-x-2" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-2'">
                  <div class="flex-shrink-0">
                    <div class="w-6 h-6 bg-primary-600 rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                  <div class="flex space-x-1" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-1'">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="border-t border-gray-200 p-4">
            <form @submit.prevent="sendMessage" class="flex space-x-2" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-2'">
              <input
                ref="messageInput"
                v-model="newMessage"
                type="text"
                :placeholder="$t('chatDiagnosis.input.placeholder')"
                class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                :disabled="isTyping || diagnosisCompleted"
              />
              <button
                type="submit"
                :disabled="!newMessage.trim() || isTyping || diagnosisCompleted"
                class="bg-primary-600 text-white px-6 py-2 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Diagnosis Completion Message -->
      <div v-if="diagnosisCompleted" class="mt-8">
        <div class="card">
          <div class="text-center py-8">
            <div class="mb-4">
              <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ $t('chatDiagnosis.completion.title') }}</h2>
            <p class="text-lg text-gray-600 mb-6">{{ $t('chatDiagnosis.completion.message') }}</p>
            <div class="flex justify-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            </div>
            <p class="text-sm text-gray-500 mt-4">{{ $t('chatDiagnosis.completion.redirecting') }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, nextTick, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

export default {
  name: 'ChatDiagnosis',
  setup() {
         const router = useRouter()
     const toast = useToast()
     const { t: $t, locale } = useI18n()
     const authStore = useAuthStore()
    
    const messages = ref([])
    const newMessage = ref('')
    const isTyping = ref(false)
    const diagnosisCompleted = ref(false)
    const chatContainer = ref(null)
    const messageInput = ref(null)
    const isLoadingDiagnosis = ref(false)
    const promptAvailable = ref(true)
    const checkingPrompt = ref(true)
    
         const diagnosisResult = reactive({
       title: '',
       description: '',
       diagnosisId: null
     })
     
     // Question counter
     const aiQuestionsCount = computed(() => {
       return messages.value.filter(msg => msg.role === 'assistant' && msg.content && isQuestion(msg.content)).length
     })
     
     const isQuestion = (content) => {
       // Simple question detection
       if (!content || typeof content !== 'string') {
         return false
       }
       const questionMarks = ['?', '؟']
       const questionWords = ['what', 'when', 'where', 'how', 'why', 'who', 'which', 'do', 'does', 'did', 'can', 'could', 'would', 'will', 'هل', 'متى', 'أين', 'كيف', 'لماذا', 'من', 'ما', 'أي']
       
       return questionMarks.some(mark => content.includes(mark)) || 
              questionWords.some(word => content.toLowerCase().includes(word.toLowerCase()))
     }


    const scrollToBottom = async () => {
      await nextTick()
      if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight
      }
    }

    const focusInput = () => {
      nextTick(() => {
        if (messageInput.value && !diagnosisCompleted.value) {
          messageInput.value.focus()
        }
      })
    }

    const loadLatestDiagnosis = async () => {
      if (!authStore.user || !authStore.token) {
        return
      }

      try {
        isLoadingDiagnosis.value = true
        const response = await api.get('/api/ai/user-diagnosis-results', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.data.success && response.data.data.current_diagnosis) {
          const diagnosis = response.data.data.current_diagnosis
          
          // Check if diagnosis was completed recently (within last hour)
          const diagnosisTime = new Date(diagnosis.completed_at)
          const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000)
          
          if (diagnosisTime > oneHourAgo) {
            // Load the diagnosis result but DON'T load conversation history
            // This allows user to start a fresh conversation
            diagnosisResult.title = diagnosis.diagnosis_name
            diagnosisResult.description = diagnosis.diagnosis_description
            diagnosisResult.diagnosisId = diagnosis.diagnosis_id
            diagnosisCompleted.value = true
            
            // Don't load conversation history - start fresh for new diagnosis
            // This prevents ChatGPT from seeing the completed diagnosis and refusing to start new one
            messages.value = []
            
            // Show a message that previous diagnosis exists but starting fresh
            toast.info($t('chatDiagnosis.loadedFromPrevious'))
          }
        }
        // If no diagnosis exists, that's fine - just continue with normal flow
      } catch (error) {
        console.error('Error loading latest diagnosis:', error)
        // Don't show error to user as this is not critical
      } finally {
        isLoadingDiagnosis.value = false
      }
    }

    const formatMessage = (content) => {
      // Convert line breaks to <br> tags
      if (!content || typeof content !== 'string') {
        return content || ''
      }
      return content.replace(/\n/g, '<br>')
    }

    const formatTime = (timestamp) => {
      return new Date(timestamp).toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    }

    const sendMessage = async () => {
      if (!newMessage.value.trim() || isTyping.value) return

      const userMessage = newMessage.value.trim()
      
      // Add user message
      messages.value.push({
        role: 'user',
        content: userMessage,
        timestamp: new Date()
      })

      newMessage.value = ''
      isTyping.value = true
      
      await scrollToBottom()

                    try {
         // Send to backend
                   const formData = new URLSearchParams()
          formData.append('action', 'chat_diagnosis_ajax')
          formData.append('message', userMessage)
          formData.append('conversation_history', JSON.stringify(messages.value))
          formData.append('locale', locale.value || 'en')
         
         const response = await api.post('/wp-admin/admin-ajax.php', formData, {
           headers: {
             'Content-Type': 'application/x-www-form-urlencoded'
           }
         })

         if (response.data.success) {
           const assistantMessage = response.data.data.message
           const diagnosis = response.data.data.diagnosis
           
           // Add assistant response - ensure content is a string
           messages.value.push({
             role: 'assistant',
             content: typeof assistantMessage === 'string' ? assistantMessage : String(assistantMessage || ''),
             timestamp: new Date()
           })

           // If diagnosis is complete
           if (diagnosis && diagnosis.completed) {
             diagnosisResult.title = diagnosis.title
             diagnosisResult.description = diagnosis.description
             diagnosisResult.diagnosisId = diagnosis.id
             diagnosisCompleted.value = true
             
             // Auto-redirect to results page after a short delay
             setTimeout(() => {
               if (diagnosisResult.diagnosisId) {
                 router.push(`/diagnosis-results/${diagnosisResult.diagnosisId}`)
               } else {
                 router.push('/therapists')
               }
             }, 3000) // 3 second delay to show completion message
           }
         } else {
           throw new Error(response.data.data || 'Failed to get response')
         }
      } catch (error) {
        console.error('Chat error:', error)
        toast.error($t('chatDiagnosis.error.message'))
        
        // Add error message
        messages.value.push({
          role: 'assistant',
          content: $t('chatDiagnosis.error.response'),
          timestamp: new Date()
        })
      } finally {
        isTyping.value = false
        await scrollToBottom()
        focusInput()
      }
    }



    const checkPromptAvailability = async () => {
      try {
        // Try to send a minimal request to check if prompt is configured
        // We send a single character to pass validation, but the backend will check for prompt
        const formData = new URLSearchParams()
        formData.append('action', 'chat_diagnosis_ajax')
        formData.append('message', 'x')
        formData.append('conversation_history', JSON.stringify([]))
        formData.append('locale', locale.value || 'en')
        
        await api.post('/wp-admin/admin-ajax.php', formData, {
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })
        
        // If we get here without 503 error, prompt is available
        promptAvailable.value = true
      } catch (error) {
        // Check if error is due to missing prompt (503 status)
        if (error.response && error.response.status === 503 && 
            error.response.data && 
            typeof error.response.data === 'object' &&
            error.response.data.data &&
            error.response.data.data.includes('prompt')) {
          promptAvailable.value = false
        } else {
          // Other errors - assume prompt is available but there's another issue
          promptAvailable.value = true
        }
      } finally {
        checkingPrompt.value = false
      }
    }

    const sendInitialWelcomeMessage = async () => {
      // Send a greeting message to trigger ChatGPT's welcome message from the prompt
      if (messages.value.length === 0) {
        isTyping.value = true
        
        try {
          const formData = new URLSearchParams()
          formData.append('action', 'chat_diagnosis_ajax')
          // Send "مرحبا" (hello) to trigger welcome message - ChatGPT will respond with welcome from prompt
          formData.append('message', 'مرحبا')
          formData.append('conversation_history', JSON.stringify([]))
          formData.append('locale', locale.value || 'en')
          
          const response = await api.post('/wp-admin/admin-ajax.php', formData, {
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          })

          if (response.data.success) {
            const assistantMessage = response.data.data.message
            
            // Add assistant welcome message
            messages.value.push({
              role: 'assistant',
              content: typeof assistantMessage === 'string' ? assistantMessage : String(assistantMessage || ''),
              timestamp: new Date()
            })
            
            await scrollToBottom()
          }
        } catch (error) {
          console.error('Error loading welcome message:', error)
          // Don't show error - just continue without welcome message
        } finally {
          isTyping.value = false
          focusInput()
        }
      }
    }

    onMounted(async () => {
      // Check if prompt is available before showing chat
      await checkPromptAvailability()
      
      if (promptAvailable.value) {
        // Load previous diagnosis if exists (but don't load conversation history)
        await loadLatestDiagnosis()
        
        // Only send welcome message if there are no messages (fresh start)
        // If diagnosis was completed, don't send welcome - user should start new conversation manually
        if (messages.value.length === 0 && !diagnosisCompleted.value) {
          await sendInitialWelcomeMessage()
        }
      }
    })

         return {
       messages,
       newMessage,
       isTyping,
       diagnosisCompleted,
       diagnosisResult,
       chatContainer,
       promptAvailable,
       checkingPrompt,
       messageInput,
       aiQuestionsCount,
       isLoadingDiagnosis,
       sendMessage,
       formatMessage,
       formatTime,
       focusInput
     }
  }
}
</script>

<style scoped>
/* RTL Support */
.rtl {
  direction: rtl;
  text-align: right;
}

.rtl .space-x-2 > :not([hidden]) ~ :not([hidden]) {
  --tw-space-x-reverse: 1;
}

.rtl .space-x-1 > :not([hidden]) ~ :not([hidden]) {
  --tw-space-x-reverse: 1;
}

.rtl .space-x-4 > :not([hidden]) ~ :not([hidden]) {
  --tw-space-x-reverse: 1;
}

.rtl .ml-3 {
  margin-left: 0;
  margin-right: 0.75rem;
}

.rtl .mr-3 {
  margin-right: 0;
  margin-left: 0.75rem;
}

/* Chat message styling */
.rtl .flex.justify-end {
  justify-content: flex-start;
}

.rtl .flex.justify-start {
  justify-content: flex-end;
}

/* Input field RTL */
.rtl input[type="text"] {
  text-align: right;
}

.rtl input[type="text"]::placeholder {
  text-align: right;
}

/* Card styling */
.card {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
