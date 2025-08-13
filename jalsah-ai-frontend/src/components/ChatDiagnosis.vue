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

      <!-- Chat Container -->
      <div class="card">
        <div class="flex flex-col h-96">
          <!-- Chat Messages -->
          <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="chatContainer">
            <!-- Welcome Message -->
            <div v-if="messages.length === 0" class="text-center text-gray-500 py-8">
              <div class="mb-4">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              </div>
              <p class="text-lg font-medium">{{ $t('chatDiagnosis.welcome.title') }}</p>
              <p class="text-sm mt-2">{{ $t('chatDiagnosis.welcome.description') }}</p>
            </div>

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
                    <p class="text-sm" v-html="formatMessage(message.content)"></p>
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

      <!-- Diagnosis Results -->
      <div v-if="diagnosisCompleted" class="mt-8">
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('chatDiagnosis.results.title') }}</h2>
          <div class="space-y-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3" :class="$i18n.locale === 'ar' ? 'mr-3' : 'ml-3'">
                  <h3 class="text-sm font-medium text-green-800">{{ diagnosisResult.title }}</h3>
                  <p class="text-sm text-green-700 mt-1">{{ diagnosisResult.description }}</p>
                </div>
              </div>
            </div>
            
            <div class="flex space-x-4" :class="$i18n.locale === 'ar' ? 'space-x-reverse' : 'space-x-4'">
              <button
                @click="viewTherapists"
                class="flex-1 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
              >
                {{ $t('chatDiagnosis.results.findTherapists') }}
              </button>
              <button
                @click="startNewDiagnosis"
                class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
              >
                {{ $t('chatDiagnosis.results.newDiagnosis') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, nextTick, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'ChatDiagnosis',
  setup() {
    const router = useRouter()
    const toast = useToast()
    const { t: $t } = useI18n()
    
    const messages = ref([])
    const newMessage = ref('')
    const isTyping = ref(false)
    const diagnosisCompleted = ref(false)
    const chatContainer = ref(null)
    
    const diagnosisResult = reactive({
      title: '',
      description: '',
      diagnosisId: null
    })

    // Initial welcome message
    const addWelcomeMessage = () => {
      messages.value.push({
        role: 'assistant',
        content: $t('chatDiagnosis.welcome.message'),
        timestamp: new Date()
      })
    }

    const scrollToBottom = async () => {
      await nextTick()
      if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight
      }
    }

    const formatMessage = (content) => {
      // Convert line breaks to <br> tags
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
         
         const response = await api.post('/wp-admin/admin-ajax.php', formData, {
           headers: {
             'Content-Type': 'application/x-www-form-urlencoded'
           }
         })

         if (response.data.success) {
           const assistantMessage = response.data.data.message
           const diagnosis = response.data.data.diagnosis
           
           // Add assistant response
           messages.value.push({
             role: 'assistant',
             content: assistantMessage,
             timestamp: new Date()
           })

           // If diagnosis is complete
           if (diagnosis && diagnosis.completed) {
             diagnosisResult.title = diagnosis.title
             diagnosisResult.description = diagnosis.description
             diagnosisResult.diagnosisId = diagnosis.id
             diagnosisCompleted.value = true
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
      }
    }

    const viewTherapists = () => {
      if (diagnosisResult.diagnosisId) {
        router.push(`/diagnosis-results/${diagnosisResult.diagnosisId}`)
      } else {
        router.push('/therapists')
      }
    }

    const startNewDiagnosis = () => {
      messages.value = []
      diagnosisCompleted.value = false
      diagnosisResult.title = ''
      diagnosisResult.description = ''
      diagnosisResult.diagnosisId = null
      addWelcomeMessage()
    }

    onMounted(() => {
      addWelcomeMessage()
    })

    return {
      messages,
      newMessage,
      isTyping,
      diagnosisCompleted,
      diagnosisResult,
      chatContainer,
      sendMessage,
      viewTherapists,
      startNewDiagnosis,
      formatMessage,
      formatTime
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
