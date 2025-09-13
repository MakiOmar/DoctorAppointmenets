<template>
  <div>
    
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-primary-50 to-secondary-50 py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
            {{ $t('home.hero.title') }}
          </h1>
          <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            {{ $t('home.hero.subtitle') }}
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <router-link 
              :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
              class="btn-primary text-lg px-8 py-3"
            >
              {{ hasPreviousDiagnosis ? $t('home.hero.ctaWithDiagnosis') : $t('home.hero.cta') }}
            </router-link>
            <router-link 
              to="/therapists"
              class="btn-outline text-lg px-8 py-3"
            >
              {{ $t('home.hero.secondaryCta') }}
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {{ $t('home.features.title') }}
          </h2>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- AI Diagnosis -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.features.aiDiagnosis.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.features.aiDiagnosis.description') }}
            </p>
          </div>

          <!-- Expert Therapists -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.features.expertTherapists.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.features.expertTherapists.description') }}
            </p>
          </div>

          <!-- Online Sessions -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.features.onlineSessions.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.features.onlineSessions.description') }}
            </p>
          </div>

          <!-- Flexible Scheduling -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.features.flexibleScheduling.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.features.flexibleScheduling.description') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {{ $t('home.howItWorks.title') }}
          </h2>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Step 1 -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="text-white font-bold text-xl">1</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.howItWorks.step1.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.howItWorks.step1.description') }}
            </p>
          </div>

          <!-- Step 2 -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="text-white font-bold text-xl">2</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.howItWorks.step2.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.howItWorks.step2.description') }}
            </p>
          </div>

          <!-- Step 3 -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="text-white font-bold text-xl">3</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.howItWorks.step3.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.howItWorks.step3.description') }}
            </p>
          </div>

          <!-- Step 4 -->
          <div class="text-center">
            <div class="w-16 h-16 bg-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <span class="text-white font-bold text-xl">4</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ $t('home.howItWorks.step4.title') }}
            </h3>
            <p class="text-gray-600">
              {{ $t('home.howItWorks.step4.description') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-primary-600">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
          {{ $t('home.hero.title') }}
        </h2>
        <p class="text-xl text-primary-100 mb-8">
          {{ $t('home.hero.subtitle') }}
        </p>
        <router-link 
          :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
          class="bg-white text-primary-600 hover:bg-gray-100 px-8 py-3 rounded-md text-lg font-medium transition-colors"
        >
          {{ hasPreviousDiagnosis ? $t('home.hero.ctaWithDiagnosis') : $t('home.hero.cta') }}
        </router-link>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'

export default {
  name: 'Home',
  setup() {
    const lastDiagnosisId = ref(null)
    
    // Computed property to check if user has a previous diagnosis
    const hasPreviousDiagnosis = computed(() => {
      return lastDiagnosisId.value !== null
    })
    
    // Check for stored diagnosis ID on component mount
    onMounted(() => {
      const storedDiagnosisId = localStorage.getItem('lastDiagnosisId')
      if (storedDiagnosisId) {
        lastDiagnosisId.value = storedDiagnosisId
        console.log('🔍 Found stored diagnosis ID:', storedDiagnosisId)
      }
    })
    
    return {
      lastDiagnosisId,
      hasPreviousDiagnosis
    }
  }
}
</script> 