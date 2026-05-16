<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-md bg-white rounded-xl shadow-lg border border-gray-200 p-6 sm:p-8">
      <h1 class="text-xl text-gray-900 text-center mb-2">{{ $t('dcAccess.title') }}</h1>
      <p class="text-sm text-gray-600 text-center mb-6">{{ $t('dcAccess.subtitle') }}</p>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label for="dc-access-password" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $t('dcAccess.passwordLabel') }}
          </label>
          <input
            id="dc-access-password"
            v-model="password"
            type="password"
            inputmode="numeric"
            autocomplete="one-time-code"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            :placeholder="$t('dcAccess.passwordPlaceholder')"
            :disabled="loading"
            required
          />
        </div>

        <button
          type="submit"
          class="w-full py-2.5 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          :disabled="loading || !password.trim()"
        >
          <span v-if="loading" class="inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
          <span>{{ loading ? $t('dcAccess.opening') : $t('dcAccess.openChat') }}</span>
        </button>
      </form>

      <p v-if="error" class="mt-4 text-sm text-red-600 text-center">{{ error }}</p>

      <p class="mt-6 text-xs text-gray-500 text-center">
        {{ $t('dcAccess.hint') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()

const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  const token = route.params.token
  if (!token || !password.value.trim()) {
    return
  }

  loading.value = true
  error.value = ''

  try {
    const response = await api.post('/api/ai/direct-conversations/guest-enter', {
      token: String(token),
      password: password.value.trim(),
    })

    if (!response.data?.success || !response.data?.data) {
      error.value = response.data?.message || t('dcAccess.invalidCode')
      return
    }

    const { token: authToken, user: userData, conversation_id: conversationId } = response.data.data
    if (!authToken || !userData || !conversationId) {
      error.value = t('dcAccess.invalidCode')
      return
    }

    authStore.setSession(authToken, userData)
    toast.success(t('dcAccess.success'))
    await router.replace(`/direct-conversations/${conversationId}`)
  } catch (err) {
    const msg =
      err.response?.data?.error ||
      err.response?.data?.message ||
      t('dcAccess.invalidCode')
    error.value = msg
  } finally {
    loading.value = false
  }
}
</script>
