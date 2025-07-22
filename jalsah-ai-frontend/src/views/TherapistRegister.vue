<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8" :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        {{ $t('therapistRegister.title') }}
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ $t('therapistRegister.subtitle') }}
      </p>
    </div>
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="onSubmit" enctype="multipart/form-data">
          <div class="space-y-4">
            <div>
              <label class="form-label" for="name">{{ $t('therapistRegister.name') }}</label>
              <input v-model="form.name" id="name" type="text" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="name_en">{{ $t('therapistRegister.nameEn') }}</label>
              <input v-model="form.name_en" id="name_en" type="text" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="email">{{ $t('therapistRegister.email') }}</label>
              <input v-model="form.email" id="email" type="email" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="phone">{{ $t('therapistRegister.phone') }}</label>
              <input v-model="form.phone" id="phone" type="text" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="whatsapp">{{ $t('therapistRegister.whatsapp') }}</label>
              <input v-model="form.whatsapp" id="whatsapp" type="text" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="doctor_specialty">{{ $t('therapistRegister.specialty') }}</label>
              <input v-model="form.doctor_specialty" id="doctor_specialty" type="text" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="profile_image">{{ $t('therapistRegister.profileImage') }}</label>
              <input @change="onFileChange($event, 'profile_image')" id="profile_image" type="file" accept="image/*" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="identity_front">{{ $t('therapistRegister.identityFront') }}</label>
              <input @change="onFileChange($event, 'identity_front')" id="identity_front" type="file" accept="image/*" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="identity_back">{{ $t('therapistRegister.identityBack') }}</label>
              <input @change="onFileChange($event, 'identity_back')" id="identity_back" type="file" accept="image/*" required class="input-field" />
            </div>
            <div>
              <label class="form-label" for="certificates">{{ $t('therapistRegister.certificates') }}</label>
              <input @change="onFileChange($event, 'certificates', true)" id="certificates" type="file" accept="image/*,application/pdf" multiple class="input-field" />
            </div>
            <div v-if="passwordMode === 'user'">
              <label class="form-label" for="password">{{ $t('therapistRegister.password') }}</label>
              <input v-model="form.password" id="password" type="password" required class="input-field" />
            </div>
            <div v-if="passwordMode === 'user'">
              <label class="form-label" for="password_confirm">{{ $t('therapistRegister.passwordConfirm') }}</label>
              <input v-model="form.password_confirm" id="password_confirm" type="password" required class="input-field" />
            </div>
            <div v-if="passwordMode === 'auto'" class="text-sm text-gray-500">
              {{ $t('therapistRegister.passwordAuto') }}
            </div>
            <div>
              <label class="form-label">
                <input type="checkbox" v-model="form.accept_terms" required />
                {{ $t('therapistRegister.acceptTerms') }}
              </label>
            </div>
            <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
            <div v-if="success" class="text-green-600 text-sm">{{ success }}</div>
            <button type="submit" class="btn btn-primary w-full" :disabled="loading">
              <span v-if="loading">{{ $t('therapistRegister.submitting') }}</span>
              <span v-else>{{ $t('therapistRegister.submit') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const settingsStore = useSettingsStore()
settingsStore.loadSettings()
const passwordMode = computed(() => settingsStore.getTherapistRegistrationPasswordMode)

const form = ref({
  name: '',
  name_en: '',
  email: '',
  phone: '',
  whatsapp: '',
  doctor_specialty: '',
  profile_image: null,
  identity_front: null,
  identity_back: null,
  certificates: [],
  password: '',
  password_confirm: '',
  accept_terms: false
})
const loading = ref(false)
const error = ref('')
const success = ref('')

function onFileChange(event, field, multiple = false) {
  if (multiple) {
    form.value[field] = Array.from(event.target.files)
  } else {
    form.value[field] = event.target.files[0]
  }
}

async function onSubmit() {
  error.value = ''
  success.value = ''
  loading.value = true
  try {
    const data = new FormData()
    for (const key in form.value) {
      if (key === 'certificates' && Array.isArray(form.value.certificates)) {
        form.value.certificates.forEach((file, idx) => {
          data.append('certificates[]', file)
        })
      } else if (['profile_image', 'identity_front', 'identity_back'].includes(key)) {
        if (form.value[key]) data.append(key, form.value[key])
      } else {
        data.append(key, form.value[key])
      }
    }
    data.append('action', 'register_therapist')
    const response = await axios.post('/wp-admin/admin-ajax.php', data)
    if (response.data.success) {
      success.value = t('therapistRegister.success')
      form.value = {
        name: '', name_en: '', email: '', phone: '', whatsapp: '', doctor_specialty: '',
        profile_image: null, identity_front: null, identity_back: null, certificates: [],
        password: '', password_confirm: '', accept_terms: false
      }
    } else {
      error.value = response.data.data?.message || t('therapistRegister.error')
    }
  } catch (e) {
    error.value = e.response?.data?.data?.message || t('therapistRegister.error')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.input-field {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  margin-top: 0.25rem;
  margin-bottom: 0.5rem;
}
.form-label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
}
.btn-primary {
  background: #2563eb;
  color: #fff;
  border: none;
  padding: 0.75rem;
  border-radius: 0.375rem;
  font-weight: 600;
  cursor: pointer;
}
.btn-primary:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}
</style> 