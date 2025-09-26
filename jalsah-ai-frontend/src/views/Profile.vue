<template>
  <div>

    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Profile page -->
      <h1 class="text-3xl font-bold text-gray-900 mb-8">My Profile</h1>

      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">Loading profile...</p>
      </div>

      <div v-else class="max-w-2xl mx-auto">
        <!-- Profile Information -->
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Personal Information</h2>
          
          <form @submit.prevent="updateProfile" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">First Name</label>
                <input 
                  v-model="profile.firstName" 
                  type="text" 
                  class="input-field"
                  required
                />
              </div>
              <div>
                <label class="form-label">Last Name</label>
                <input 
                  v-model="profile.lastName" 
                  type="text" 
                  class="input-field"
                  required
                />
              </div>
            </div>

            <div>
              <label class="form-label">Email</label>
              <input 
                v-model="profile.email" 
                type="email" 
                class="input-field"
                required
              />
            </div>

            <div>
              <label class="form-label">Phone Number</label>
              <input 
                v-model="profile.phone" 
                type="tel" 
                class="input-field"
              />
            </div>

            <div class="flex justify-end">
              <button 
                type="submit" 
                :disabled="updating"
                class="btn-primary"
              >
                <span v-if="updating" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ $t('profile.updating') }}
                </span>
                <span v-else>{{ $t('profile.updateProfile') }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password -->
        <div class="card mt-8">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $t('profile.changePassword') }}</h2>
          
          <form @submit.prevent="changePassword" class="space-y-6">
            <div>
              <label class="form-label">{{ $t('profile.currentPassword') }}</label>
              <input 
                v-model="password.current" 
                type="password" 
                class="input-field"
                required
              />
            </div>

            <div>
              <label class="form-label">{{ $t('profile.newPassword') }}</label>
              <input 
                v-model="password.new" 
                type="password" 
                class="input-field"
                required
              />
            </div>

            <div>
              <label class="form-label">{{ $t('profile.confirmNewPassword') }}</label>
              <input 
                v-model="password.confirm" 
                type="password" 
                class="input-field"
                required
              />
            </div>

            <div class="flex justify-end">
              <button 
                type="submit" 
                :disabled="changingPassword"
                class="btn-outline"
              >
                <span v-if="changingPassword" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ $t('profile.changing') }}
                </span>
                <span v-else>{{ $t('profile.changePasswordButton') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
export default {
  name: 'Profile',
  setup() {
    const router = useRouter()
    const toast = useToast()
    const authStore = useAuthStore()
    
    const loading = ref(true)
    const updating = ref(false)
    const changingPassword = ref(false)
    
    const profile = ref({
      firstName: '',
      lastName: '',
      email: '',
      phone: ''
    })

    const password = ref({
      current: '',
      new: '',
      confirm: ''
    })

    const loadProfile = async () => {
      loading.value = true
      try {
        const response = await api.get('/api/ai/profile')
        const userData = response.data.data
        
        profile.value = {
          firstName: userData.first_name || '',
          lastName: userData.last_name || '',
          email: userData.email || '',
          phone: userData.phone || ''
        }
      } catch (error) {
        toast.error('Failed to load profile')
        console.error('Error loading profile:', error)
      } finally {
        loading.value = false
      }
    }

    const updateProfile = async () => {
      updating.value = true
      
      try {
        const profileData = {
          first_name: profile.value.firstName,
          last_name: profile.value.lastName,
          email: profile.value.email,
          phone: profile.value.phone
        }

        await api.put('/api/ai/profile', profileData)
        
        toast.success('Profile updated successfully!')
        
      } catch (error) {
        toast.error('Failed to update profile')
        console.error('Error updating profile:', error)
      } finally {
        updating.value = false
      }
    }

    const changePassword = async () => {
      if (password.value.new !== password.value.confirm) {
        toast.error('New passwords do not match')
        return
      }

      changingPassword.value = true
      
      try {
        const passwordData = {
          current_password: password.value.current,
          new_password: password.value.new
        }

        await api.put('/api/ai/profile/password', passwordData)
        
        toast.success('Password changed successfully!')
        
        // Clear password fields
        password.value = {
          current: '',
          new: '',
          confirm: ''
        }
        
      } catch (error) {
        toast.error('Failed to change password')
        console.error('Error changing password:', error)
      } finally {
        changingPassword.value = false
      }
    }

    const logout = () => {
      authStore.logout()
      router.push('/login')
      toast.success('Logged out successfully')
    }


    onMounted(() => {
      loadProfile()
    })

    return {
      loading,
      updating,
      changingPassword,
      profile,
      password,
      updateProfile,
      changePassword,
      logout
    }
  }
}
</script> 