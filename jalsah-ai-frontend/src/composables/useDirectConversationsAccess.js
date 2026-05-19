import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/** Roles allowed by GET /api/ai/direct-conversations/* (matches WordPress plugin). */
const DIRECT_CONVERSATION_ROLES = ['customer', 'doctor', 'administrator']

/**
 * Whether the logged-in user may use direct therapist–patient messaging APIs.
 *
 * @returns {{ canAccessDirectConversations: import('vue').ComputedRef<boolean> }}
 */
export function useDirectConversationsAccess() {
  const authStore = useAuthStore()

  const canAccessDirectConversations = computed(() => {
    if (!authStore.isAuthenticated || !authStore.user) {
      return false
    }
    const role = authStore.user.role || ''
    const roles = Array.isArray(authStore.user.roles) ? authStore.user.roles : []
    if (DIRECT_CONVERSATION_ROLES.includes(role)) {
      return true
    }
    return roles.some((r) => DIRECT_CONVERSATION_ROLES.includes(r))
  })

  return { canAccessDirectConversations }
}
