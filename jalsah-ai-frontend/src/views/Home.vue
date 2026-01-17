<template>
  <div class="min-h-screen bg-white">
    <!-- Section 2: Hero Section -->
    <section 
      class="relative flex items-center justify-center px-4 py-8 md:py-20 bg-cover bg-center bg-no-repeat"
      :style="heroSectionStyle"
    >
      
      <div class="relative z-10 max-w-[960px] mx-auto text-center">
        <!-- Logo -->
        <div>
            <img 
              v-if="logoExists"
              src="/home/logo.png" 
              class="h-[7rem] md:h-32 mx-auto"
              @error="logoExists = false"
            />
          <div v-else class="w-32 h-32 mx-auto bg-white bg-opacity-20 rounded-full flex items-center justify-center">
            <span class="text-4xl text-white">{{ $t('logo.text') }}</span>
          </div>
        </div>

        <!-- Welcome Text -->
        <h1 class="text-[1.4rem] md:text-5xl md:leading-tight text-white">
          {{ $t('home.sections.hero.welcome') }}
        </h1>
        <p class="text-[1.4rem] md:text-xl text-white mb-2 max-w-2xl mx-auto font-jalsah2" style="line-height: 1.3rem;">
          {{ $t('home.sections.hero.subtitle') }}
        </p>

        <!-- Action Buttons - Conditional based on auth state -->
        <div v-if="!authStore.isAuthenticated" class="flex flex-col sm:flex-row gap-3 mt-[40px] justify-center items-center" dir="rtl">
          <!-- Login Button (Left in RTL, icon after text) -->
          <router-link
            to="/login"
            class="flex items-center justify-center gap-3 px-8 py-1 bg-secondary-500 text-primary-500 rounded-lg hover:opacity-90 transition-opacity min-w-[250px] text-[20px] md:text-base"
            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
          >
            {{ $t('home.sections.hero.loginButton') }}
            <img 
              v-if="signInIconExists"
              src="/home/sign-in-icon.png" 
              alt="Sign In" 
              class="h-5"
              @error="signInIconExists = false"
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
          </router-link>

          <!-- Registration Button (Right in RTL, icon after text) -->
          <router-link
            to="/register"
            class="flex items-center justify-center gap-3 px-8 py-1 bg-primary-500 text-white rounded-lg hover:opacity-90 transition-opacity min-w-[250px] text-[20px] md:text-base"
            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
          >
            <img 
              v-if="newUserIconExists"
              src="/home/new-user.png" 
              alt="Register" 
              class="h-5"
              @error="newUserIconExists = false"
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
        <div v-else class="flex flex-col sm:flex-row gap-3 justify-center mt-[40px] items-center" dir="rtl">
          <!-- Smart Diagnosis Button (Right in RTL, icon after text) -->
          <router-link
            v-if="!loadingDiagnosis"
            :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
            class="flex items-center justify-center gap-3 px-1 py-2 bg-primary-500 text-white rounded-lg hover:opacity-90 transition-opacity w-[250px] text-[20px] md:text-[25px]"
            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
            @click="handleNavigationClick"
          >
            {{ hasPreviousDiagnosis ? $t('home.sections.hero.diagnosisResultsButton') : $t('home.sections.hero.diagnosisButton') }}
            <img 
              v-if="aiIconExists"
              src="/home/ai-icon-white.png" 
              alt="AI Diagnosis" 
              class="h-7"
              @error="aiIconExists = false"
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
          </router-link>
          
          <div
            v-else
            class="flex items-center justify-center gap-3 px-8 py-1 bg-secondary-500 text-primary-500 rounded-lg opacity-75 min-w-[250px]"
          >
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-primary-500"></div>
            {{ $t('common.loading') }}
          </div>

          <!-- Therapists Button (Left in RTL, icon after text) -->
          <router-link
            to="/therapists"
            class="flex items-center justify-center gap-3 px-8 py-2 bg-secondary-500 text-primary-500 rounded-lg hover:opacity-90 transition-opacity min-w-[250px] text-[20px] md:text-[25px]"
            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
          >
            {{ $t('home.sections.hero.therapistsButton') }}
            <svg 
              class="w-7 h-7" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Section 3: AI Integration Section -->
    <section 
      class="flex items-center justify-center relative py-10 px-4 bg-cover bg-center bg-no-repeat md:h-[240px] h-[400px] ai-integration-section"
      :style="squareSectionStyle"
    >
      <div class="max-w-[960px] mx-auto">
        <div class="flex flex-col md:flex-row-reverse items-center gap-8 md:gap-12">
          <!-- Text Content (Right side, primary color) -->
          <div class="flex-1 text-primary-500 text-justify">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.ai.text') }}
            </p>
          </div>

          <!-- Image (Left side) -->
          <div class="flex-shrink-0">
            <img 
              v-if="aiIconExists"
              src="/home/ai-icon.png" 
              alt="AI" 
              class="h-[133px] md:h-40"
              @error="aiIconExists = false"
            />
            <div 
              v-else
              class="w-32 h-32 md:w-40 md:h-40 bg-white bg-opacity-20 rounded-full flex items-center justify-center"
            >
              <span class="text-4xl text-white">AI</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 4: Certified Therapists Section -->
    <section 
      class="flex items-center justify-center bg-primary-500 py-10 px-[1.5rem] md:h-[240px] h-[400px]"
      :style="squareSectionStyle"
    >
      <div class="max-w-[960px] mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">
          <!-- Text Content (Left side) -->
          <div class="flex-1 text-white text-justify">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.certified.text') }}
            </p>
          </div>

          <!-- Icon (Right side) -->
          <div class="flex-shrink-0">
            <img 
              v-if="layer6Exists"
              src="/home/Layer-6.png" 
              alt="Certified" 
              class="h-[127px] md:h-40"
              @error="layer6Exists = false"
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
    <section 
      class="flex items-center justify-center bg-secondary-500 py-10 px-[1.5rem] md:h-[240px] h-[400px]"
      :style="squareSectionStyle"
    >
      <div class="max-w-[960px] mx-auto">
        <div class="flex flex-col-reverse md:flex-row items-center gap-8 md:gap-12">
            <!-- Icon (Left side visually) -->
            <div class="flex-shrink-0">
            <img 
              v-if="layer7Exists"
              src="/home/Layer-7.png" 
              alt="Prescription" 
              class="h-[149px] md:h-40"
              @error="layer7Exists = false"
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
          <!-- Text Content (Right side visually, right-aligned for RTL) -->
          <div class="flex-1 text-primary-500 text-justify">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.prescription.text') }}
            </p>
          </div>

        </div>
      </div>
    </section>

    <!-- Section 6: Online Booking & Payment Section -->
    <section 
      class="flex items-center justify-center bg-white py-10 px-[1.5rem] md:h-[240px] h-[400px]"
      :style="squareSectionStyle"
    >
      <div class="max-w-[960px] mx-auto">
        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">
          <!-- Text Content (Left side) -->
          <div class="flex-1 text-primary-500 text-justify">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.payment.text') }}
            </p>
          </div>

          <!-- Payment Icons (Right side) -->
          <div class="flex-shrink-0 flex flex-row md:flex-col gap-4 items-center">
            <!-- Payment Method Icons - Mobile: Left, Desktop: Below globe -->
            <!-- Visa (Layer-12) - Mobile: First, Desktop: Hidden (shown in separate div) -->
            <div class="flex md:hidden">
              <img 
                v-if="layer12Exists"
                src="/home/Layer-12.png" 
                alt="Visa" 
                class="h-[32px]"
                @error="layer12Exists = false"
              />
            </div>

            <!-- Globe Icon (Layer-10) - Mobile: Middle, Desktop: Top -->
            <div>
              <img 
                v-if="layer10Exists"
                src="/home/Layer-10.png" 
                alt="Global" 
                class="h-[93px]"
                @error="layer10Exists = false"
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

            <!-- Payment Method Icons - Desktop: Below globe (both icons) -->
            <div class="hidden md:flex gap-4">
              <img 
                v-if="layer11Exists"
                src="/home/Layer-11.png" 
                alt="MasterCard" 
                class="h-[22px]"
                @error="layer11Exists = false"
              />
              <img 
                v-if="layer12Exists"
                src="/home/Layer-12.png" 
                alt="Visa" 
                class="h-[32px]"
                @error="layer12Exists = false"
              />
            </div>

            <!-- MasterCard (Layer-11) - Mobile: Right, Desktop: Hidden (shown in separate div) -->
            <div class="flex md:hidden">
              <img 
                v-if="layer11Exists"
                src="/home/Layer-11.png" 
                alt="MasterCard" 
                class="h-[22px]"
                @error="layer11Exists = false"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 7: Secure Sessions Section -->
    <section 
      class="flex items-center justify-center bg-primary-500 py-10 px-[1.5rem] md:h-[240px] h-[400px]"
      :style="squareSectionStyle"
    >
      <div class="max-w-[960px] mx-auto">
        <div class="flex flex-col-reverse md:flex-row items-center gap-8 md:gap-12">
          <!-- Icon (Left side visually) -->
          <div class="flex-shrink-0">
            <img 
              v-if="layer13Exists"
              src="/home/Layer-13.png" 
              alt="Security" 
              class="h-[106px] md:h-40"
              @error="layer13Exists = false"
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
          <!-- Text Content (Right side visually, right-aligned for RTL) -->
          <div class="flex-1 text-white text-justify">
            <p class="text-lg md:text-xl leading-relaxed">
              {{ $t('home.sections.security.text') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-4 px-4">
      <div class="max-w-[960px] mx-auto text-center">
        <p class="text-primary-500 text-sm md:text-base">
          {{ $t('home.sections.footer.copyright') }}
        </p>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
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
    const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 0)
    
    // Image existence flags - start as false, check in onMounted
    const logoExists = ref(false)
    const signInIconExists = ref(false)
    const newUserIconExists = ref(false)
    const aiIconExists = ref(false)
    const layer6Exists = ref(false)
    const layer7Exists = ref(false)
    const layer10Exists = ref(false)
    const layer11Exists = ref(false)
    const layer12Exists = ref(false)
    const layer13Exists = ref(false)
    
    // Computed property to check if user has a previous diagnosis
    const hasPreviousDiagnosis = computed(() => {
      return lastDiagnosisId.value !== null
    })
    
    // Computed property for hero section style - square on mobile (≤480px)
    const heroSectionStyle = computed(() => {
      const baseStyle = {
        backgroundImage: `url(/home/background.png)`
      }
      
      // For screens 480px or less, make it square (height = width)
      if (windowWidth.value <= 480) {
        baseStyle.height = `${windowWidth.value}px`
      }
      
      return baseStyle
    })
    
    // Computed property for square sections style - square on mobile (≤480px)
    const squareSectionStyle = computed(() => {
      const baseStyle = {}
      
      // For screens 480px or less, make it square (height = width)
      if (windowWidth.value <= 480) {
        baseStyle.height = `${windowWidth.value}px`
      }
      
      return baseStyle
    })
    
    // Handle window resize to update width
    const handleResize = () => {
      windowWidth.value = window.innerWidth
    }
    
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
      // Initialize window width
      windowWidth.value = window.innerWidth
      
      // Add resize listener
      window.addEventListener('resize', handleResize)
      
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
    
    onUnmounted(() => {
      // Remove resize listener
      window.removeEventListener('resize', handleResize)
    })
    
    return {
      authStore,
      locale,
      lastDiagnosisId,
      hasPreviousDiagnosis,
      loadingDiagnosis,
      handleNavigationClick,
      heroSectionStyle,
      squareSectionStyle,
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

<style scoped>
/* AI Integration Section - Mobile background */
.ai-integration-section {
  background-image: url(/home/ai-background-mobile.png);
}

/* AI Integration Section - Desktop background */
@media (min-width: 768px) {
  .ai-integration-section {
    background-image: url(/home/ai-background.png);
  }
}
</style>
