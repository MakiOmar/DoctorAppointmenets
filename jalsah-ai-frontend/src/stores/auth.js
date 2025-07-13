import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('jalsah_token'))
  const loading = ref(false)
  const toast = useToast()

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const login = async (credentials) => {
    loading.value = true
    try {
      const response = await api.post('/api/ai/auth', credentials)
      const { token: authToken, user: userData } = response.data.data
      
      token.value = authToken
      user.value = userData
      localStorage.setItem('jalsah_token', authToken)
      
      toast.success('Login successful!')
      return true
    } catch (error) {
      const message = error.response?.data?.error || 'Login failed'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    try {
      const response = await api.post('/api/ai/auth/register', userData)
      const { token: authToken, user: newUser } = response.data.data
      
      token.value = authToken
      user.value = newUser
      localStorage.setItem('jalsah_token', authToken)
      
      toast.success('Registration successful!')
      return true
    } catch (error) {
      const message = error.response?.data?.error || 'Registration failed'
      toast.error(message)
      return false
    } finally {
      loading.value = false
    }
  }

  const logout = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('jalsah_token')
    toast.success('Logged out successfully')
  }

  const loadUser = async () => {
    if (!token.value) return false
    
    try {
      // Set token in API headers
      api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      
      // You might want to add an endpoint to get current user data
      // For now, we'll just check if the token is valid
      return true
    } catch (error) {
      logout()
      return false
    }
  }

  // Initialize auth state
  if (token.value) {
    loadUser()
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    login,
    register,
    logout,
    loadUser
  }
}) 