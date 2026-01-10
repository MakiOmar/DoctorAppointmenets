<template>
  <div class="min-h-screen bg-white">
    <!-- Section 2: Hero Section -->
    <section 
      class="relative min-h-screen flex items-center justify-center px-4 py-20 bg-cover bg-center bg-no-repeat"
      :style="{ backgroundImage: `url(/home/background.png)` }"
    >
      <!-- Overlay for better text readability -->
      <div class="absolute inset-0 bg-black bg-opacity-30"></div>
      
      <div class="relative z-10 max-w-4xl mx-auto text-center">
        <!-- Logo -->
        <div class="mb-8">
          <img 
            v-if="logoExists"
            src="/home/logo.png" 
            alt="Jalsah Logo" 
            class="w-32 h-32 mx-auto mb-6"
          />
          <div v-else class="w-32 h-32 mx-auto mb-6 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <span class="text-4xl font-bold text-white">{{ $t('logo.text') }}</span>
          </div>
        </div>

        <!-- Welcome Text -->
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">
          {{ $t('home.sections.hero.welcome') }}
        </h1>
        <p class="text-lg md:text-xl text-white mb-12 max-w-2xl mx-auto">
          {{ $t('home.sections.hero.subtitle') }}
        </p>

        <!-- Action Buttons - Conditional based on auth state -->
        <div v-if="!authStore.isAuthenticated" class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <!-- Login Button -->
          <router-link
            to="/login"
            class="flex items-center justify-center gap-3 px-8 py-4 bg-secondary-500 text-primary-500 font-semibold rounded-lg hover:opacity-90 transition-opacity min-w-[200px]"
          >
            <img 
              v-if="signInIconExists"
              src="/home/sign-in-icon.png" 
              alt="Sign In" 
              class="w-5 h-5"
            />
            <svg 
              v-else
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            {{ $t('home.sections.hero.loginButton') }}
          </router-link>

          <!-- Registration Button -->
          <router-link
            to="/register"
            class="flex items-center justify-center gap-3 px-8 py-4 bg-secondary-500 text-primary-500 font-semibold rounded-lg hover:opacity-90 transition-opacity min-w-[200px]"
          >
            <img 
              v-if="newUserIconExists"
              src="/home/new-user.png" 
              alt="Register" 
              class="w-5 h-5"
            />
            <svg 
              v-else
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            {{ $t('home.sections.hero.registerButton') }}
          </router-link>
        </div>

        <!-- Authenticated User Buttons -->
        <div v-else class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <!-- Smart Diagnosis Button -->
          <router-link
            v-if="!loadingDiagnosis"
            :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
            class="flex items-center justify-center gap-3 px-8 py-4 bg-secondary-500 text-primary-500 font-semibold rounded-lg hover:opacity-90 transition-opacity min-w-[200px]"
            @click="handleNavigationClick"
          >
            <img 
              v-if="aiIconExists"
              src="/home/ai-icon.png" 
              alt="AI Diagnosis" 
              class="w-5 h-5"
            />
            <svg 
              v-else
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            {{ hasPreviousDiagnosis ? $t('home.sections.hero.diagnosisResultsButton') : $t('home.sections.hero.diagnosisButton') }}
          </router-link>
          
          <div
            v-else
            class="flex items-center justify-center gap-3 px-8 py-4 bg-secondary-500 text-primary-500 font-semibold rounded-lg opacity-75 min-w-[200px]"
          >
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-primary-500"></div>
            {{ $t('common.loading') }}
          </div>

          <!-- Therapists Button -->
          <router-link
            to="/therapists"
            class="flex items-center justify-center gap-3 px-8 py-4 bg-secondary-500 text-primary-500 font-semibold rounded-lg hover:opacity-90 transition-opacity min-w-[200px]"
          >
            <svg 
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            {{ $t('home.sections.hero.therapistsButton') }}
          </router-link>
        </div>
      </div>
    </section>

    <!-- Section 3: AI Integration Section -->
    <section 
      class="relative py-20 px-4 bg-cover bg-center bg-no-repeat"
      :style="{ backgroundImage: `url(/home/ai-background.png)` }"
    >
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">
          <!-- AI Icon -->
          <div class="flex-shrink-0">
            <img 
              v-if="aiIconExists"
              src="/home/ai-icon.png" 
              alt="AI" 
              class="w-32 h-32 md:w-40 md:h-40"
            />
            <div 
              v-else
              class="w-32 h-32 md:w-40 md:h-40 bg-white bg-opacity-20 rounded-full flex items-center justify-center"
            >
              <span class="text-4xl font-bold text-white">AI</span>
            </div>
          </div>

          <!-- Text Content -->
          <div class="flex-1 text-white">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.ai.text') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 4: Certified Therapists Section -->
    <section class="bg-primary-500 py-20 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
          <!-- Text Content -->
          <div class="flex-1 text-white" :class="locale === 'ar' ? 'md:text-right' : 'md:text-left'">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.certified.text') }}
            </p>
          </div>

          <!-- Icon -->
          <div class="flex-shrink-0">
            <img 
              v-if="layer6Exists"
              src="/home/Layer-6.png" 
              alt="Certified" 
              class="w-32 h-32 md:w-40 md:h-40"
            />
            <div 
              v-else
              class="w-32 h-32 md:w-40 md:h-40 bg-white bg-opacity-20 rounded-full flex items-center justify-center"
            >
              <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 5: Prescription Service Section -->
    <section class="bg-secondary-500 py-20 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">
          <!-- Icon -->
          <div class="flex-shrink-0">
            <img 
              v-if="layer7Exists"
              src="/home/Layer-7.png" 
              alt="Prescription" 
              class="w-32 h-32 md:w-40 md:h-40"
            />
            <div 
              v-else
              class="w-32 h-32 md:w-40 md:h-40 bg-primary-500 bg-opacity-20 rounded-lg flex items-center justify-center"
            >
              <svg class="w-16 h-16 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
          </div>

          <!-- Text Content -->
          <div class="flex-1 text-primary-500">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.prescription.text') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 6: Online Booking & Payment Section -->
    <section class="bg-white py-20 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
          <!-- Text Content -->
          <div class="flex-1 text-primary-500" :class="locale === 'ar' ? 'md:text-right' : 'md:text-left'">
            <p class="text-lg md:text-xl leading-relaxed mb-8">
              {{ $t('home.sections.payment.text') }}
            </p>

            <!-- Payment Icons -->
            <div class="flex flex-col items-start gap-4 mt-8" :class="locale === 'ar' ? 'items-end' : 'items-start'">
              <!-- Globe Icon -->
              <div>
                <img 
                  v-if="layer10Exists"
                  src="/home/Layer-10.png" 
                  alt="Global" 
                  class="w-24 h-24"
                />
                <div 
                  v-else
                  class="w-24 h-24 bg-primary-500 bg-opacity-10 rounded-full flex items-center justify-center"
                >
                  <svg class="w-12 h-12 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
              </div>

              <!-- Payment Method Icons -->
              <div class="flex gap-4 mt-4">
                <img 
                  v-if="layer11Exists"
                  src="/home/Layer-11.png" 
                  alt="MasterCard" 
                  class="h-12 w-auto"
                />
                <img 
                  v-if="layer12Exists"
                  src="/home/Layer-12.png" 
                  alt="Visa" 
                  class="h-12 w-auto"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 7: Secure Sessions Section -->
    <section class="bg-primary-500 py-20 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">
          <!-- Icon -->
          <div class="flex-shrink-0">
            <img 
              v-if="layer13Exists"
              src="/home/Layer-13.png" 
              alt="Security" 
              class="w-32 h-32 md:w-40 md:h-40"
            />
            <div 
              v-else
              class="w-32 h-32 md:w-40 md:h-40 bg-white bg-opacity-20 rounded-lg flex items-center justify-center"
            >
              <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
            </div>
          </div>

          <!-- Text Content -->
          <div class="flex-1 text-white">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.security.text') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-primary-500 py-8 px-4">
      <div class="max-w-6xl mx-auto text-center">
        <p class="text-white text-sm md:text-base">
          {{ $t('home.sections.footer.copyright') }}
        </p>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'Home',
  setup() {
    const authStore = useAuthStore()
    const { locale } = useI18n()
    const lastDiagnosisId = ref(null)
    const loadingDiagnosis = ref(false)
    
    // Image existence flags
    const logoExists = ref(true)
    const signInIconExists = ref(true)
    const newUserIconExists = ref(true)
    const aiIconExists = ref(true)
    const layer6Exists = ref(true)
    const layer7Exists = ref(true)
    const layer10Exists = ref(true)
    const layer11Exists = ref(true)
    const layer12Exists = ref(true)
    const layer13Exists = ref(true)
    
    // Computed property to check if user has a previous diagnosis
    const hasPreviousDiagnosis = computed(() => {
      return lastDiagnosisId.value !== null
    })
    
    // Fetch last diagnosis ID from API
    const fetchLastDiagnosisId = async () => {
      if (!authStore.user || !authStore.token) {
        lastDiagnosisId.value = null
        return
      }
      
      try {
        loadingDiagnosis.value = true
        const response = await api.get('/api/ai/user-diagnosis-results', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
        
        if (response.data.success && response.data.data.current_diagnosis) {
          const diagnosis = response.data.data.current_diagnosis
          lastDiagnosisId.value = diagnosis.diagnosis_id
        } else {
          lastDiagnosisId.value = null
        }
      } catch (error) {
        console.error('Error fetching last diagnosis:', error)
        lastDiagnosisId.value = null
      } finally {
        loadingDiagnosis.value = false
      }
    }
    
    // Watch for user changes and refetch diagnosis
    watch(() => authStore.user, (newUser) => {
      if (newUser) {
        fetchLastDiagnosisId()
      } else {
        lastDiagnosisId.value = null
      }
    }, { immediate: true })
    
    // Handle navigation click for immediate feedback
    const handleNavigationClick = () => {
      loadingDiagnosis.value = true
    }
    
    // Check if images exist
    const checkImageExists = (src) => {
      return new Promise((resolve) => {
        const img = new Image()
        img.onload = () => resolve(true)
        img.onerror = () => resolve(false)
        img.src = src
      })
    }

    onMounted(async () => {
      // Check if images exist
      logoExists.value = await checkImageExists('/home/logo.png')
      signInIconExists.value = await checkImageExists('/home/sign-in-icon.png')
      newUserIconExists.value = await checkImageExists('/home/new-user.png')
      aiIconExists.value = await checkImageExists('/home/ai-icon.png')
      layer6Exists.value = await checkImageExists('/home/Layer-6.png')
      layer7Exists.value = await checkImageExists('/home/Layer-7.png')
      layer10Exists.value = await checkImageExists('/home/Layer-10.png')
      layer11Exists.value = await checkImageExists('/home/Layer-11.png')
      layer12Exists.value = await checkImageExists('/home/Layer-12.png')
      layer13Exists.value = await checkImageExists('/home/Layer-13.png')
      
      if (authStore.user) {
        fetchLastDiagnosisId()
      }
    })
    
    return {
      authStore,
      locale,
      lastDiagnosisId,
      hasPreviousDiagnosis,
      loadingDiagnosis,
      handleNavigationClick,
      logoExists,
      signInIconExists,
      newUserIconExists,
      aiIconExists,
      layer6Exists,
      layer7Exists,
      layer10Exists,
      layer11Exists,
      layer12Exists,
      layer13Exists
    }
  }
}
</script>
