<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" :class="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          {{ $t('diagnosis.title') }}
        </h1>
        <p class="text-lg text-gray-600">
          {{ $t('diagnosis.subtitle') }}
        </p>
      </div>

      <!-- Diagnosis Form -->
      <div class="card">
        <form @submit.prevent="submitDiagnosis" class="space-y-6">
          <!-- Step 1: Basic Information -->
          <div v-if="currentStep === 1">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('diagnosis.step1.title') }}</h2>
            
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">{{ $t('diagnosis.step1.mood') }}</label>
                <select v-model="form.mood" class="input-field" required>
                  <option value="">{{ $t('common.select') }}</option>
                  <option value="happy">{{ $t('diagnosis.step1.moodOptions.happy') }}</option>
                  <option value="neutral">{{ $t('diagnosis.step1.moodOptions.neutral') }}</option>
                  <option value="sad">{{ $t('diagnosis.step1.moodOptions.sad') }}</option>
                  <option value="anxious">{{ $t('diagnosis.step1.moodOptions.anxious') }}</option>
                  <option value="angry">{{ $t('diagnosis.step1.moodOptions.angry') }}</option>
                  <option value="stressed">{{ $t('diagnosis.step1.moodOptions.stressed') }}</option>
                </select>
              </div>
              
              <div>
                <label class="form-label">{{ $t('diagnosis.step1.duration') }}</label>
                <select v-model="form.duration" class="input-field" required>
                  <option value="">{{ $t('common.select') }}</option>
                  <option value="less_than_week">{{ $t('diagnosis.step1.durationOptions.less_than_week') }}</option>
                  <option value="few_weeks">{{ $t('diagnosis.step1.durationOptions.few_weeks') }}</option>
                  <option value="few_months">{{ $t('diagnosis.step1.durationOptions.few_months') }}</option>
                  <option value="six_months">{{ $t('diagnosis.step1.durationOptions.six_months') }}</option>
                  <option value="year_plus">{{ $t('diagnosis.step1.durationOptions.year_plus') }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Step 2: Symptoms -->
          <div v-if="currentStep === 2">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('diagnosis.step2.title') }}</h2>
            
            <div class="space-y-4">
              <div class="grid md:grid-cols-2 gap-4">
                <div v-for="symptom in symptoms" :key="symptom.id" class="flex items-center">
                  <input
                    :id="symptom.id"
                    v-model="form.selectedSymptoms"
                    :value="symptom.id"
                    type="checkbox"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                  />
                  <label :for="symptom.id" class="ml-2 text-sm text-gray-900">
                    {{ $t(`diagnosis.step2.symptoms.${symptom.id}`) }}
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Impact -->
          <div v-if="currentStep === 3">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('diagnosis.step3.title') }}</h2>
            
            <div class="space-y-4">
              <div>
                <label class="form-label">{{ $t('diagnosis.step3.impact') }}</label>
                <select v-model="form.impact" class="input-field" required>
                  <option value="">{{ $t('common.select') }}</option>
                  <option value="minimal">{{ $t('diagnosis.step3.impactOptions.minimal') }}</option>
                  <option value="mild">{{ $t('diagnosis.step3.impactOptions.mild') }}</option>
                  <option value="moderate">{{ $t('diagnosis.step3.impactOptions.moderate') }}</option>
                  <option value="severe">{{ $t('diagnosis.step3.impactOptions.severe') }}</option>
                  <option value="extreme">{{ $t('diagnosis.step3.impactOptions.extreme') }}</option>
                </select>
              </div>
              
              <div>
                <label class="form-label">{{ $t('diagnosis.step3.affectedAreas') }}</label>
                <div class="grid md:grid-cols-2 gap-4 mt-2">
                  <div v-for="area in lifeAreas" :key="area.id" class="flex items-center">
                    <input
                      :id="area.id"
                      v-model="form.affectedAreas"
                      :value="area.id"
                      type="checkbox"
                      class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                    />
                    <label :for="area.id" class="ml-2 text-sm text-gray-900">
                      {{ $t(`diagnosis.step3.areas.${area.id}`) }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4: Goals -->
          <div v-if="currentStep === 4">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $t('diagnosis.step4.title') }}</h2>
            
            <div class="space-y-4">
              <div>
                <label class="form-label">{{ $t('diagnosis.step4.goals') }}</label>
                <textarea
                  v-model="form.goals"
                  rows="4"
                  class="input-field"
                  :placeholder="$t('diagnosis.step4.goalsPlaceholder')"
                  required
                ></textarea>
              </div>
              
              <div>
                <label class="form-label">{{ $t('diagnosis.step4.preferredApproach') }}</label>
                <select v-model="form.preferredApproach" class="input-field">
                  <option value="">{{ $t('diagnosis.step4.approachOptions.none') }}</option>
                  <option value="cbt">{{ $t('diagnosis.step4.approachOptions.cbt') }}</option>
                  <option value="psychodynamic">{{ $t('diagnosis.step4.approachOptions.psychodynamic') }}</option>
                  <option value="humanistic">{{ $t('diagnosis.step4.approachOptions.humanistic') }}</option>
                  <option value="mindfulness">{{ $t('diagnosis.step4.approachOptions.mindfulness') }}</option>
                  <option value="solution_focused">{{ $t('diagnosis.step4.approachOptions.solution_focused') }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-between pt-6" :class="$i18n.locale === 'ar' ? 'flex-row-reverse' : 'flex-row'">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="previousStep"
              class="btn-secondary"
            >
              {{ $t('common.previous') }}
            </button>
            <div></div>
            
            <button
              v-if="currentStep < 4"
              type="button"
              @click="nextStep"
              class="btn-primary"
            >
              {{ $t('common.next') }}
            </button>
            
            <button
              v-if="currentStep === 4"
              type="submit"
              :disabled="loading"
              class="btn-primary"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('diagnosis.analyzing') }}
              </span>
              <span v-else>{{ $t('diagnosis.submit') }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Progress Bar -->
      <div class="mt-8">
        <div class="flex justify-between text-sm text-gray-600 mb-2">
          <span>{{ $t('diagnosis.progress', { step: currentStep }) }}</span>
          <span>{{ $t('diagnosis.complete', { percent: Math.round((currentStep / 4) * 100) }) }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="bg-primary-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${(currentStep / 4) * 100}%` }"
          ></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
export default {
  name: 'Diagnosis',
  setup() {
    const router = useRouter()
    const toast = useToast()
    
    const currentStep = ref(1)
    const loading = ref(false)
    
    const form = reactive({
      mood: '',
      duration: '',
      selectedSymptoms: [],
      impact: '',
      affectedAreas: [],
      goals: '',
      preferredApproach: ''
    })

    const symptoms = [
      { id: 'anxiety', name: 'Anxiety or excessive worry' },
      { id: 'depression', name: 'Depression or low mood' },
      { id: 'stress', name: 'High stress levels' },
      { id: 'sleep', name: 'Sleep problems' },
      { id: 'appetite', name: 'Changes in appetite' },
      { id: 'energy', name: 'Low energy or fatigue' },
      { id: 'concentration', name: 'Difficulty concentrating' },
      { id: 'irritability', name: 'Irritability or anger' },
      { id: 'isolation', name: 'Social withdrawal' },
      { id: 'hopelessness', name: 'Feelings of hopelessness' },
      { id: 'panic', name: 'Panic attacks' },
      { id: 'trauma', name: 'Trauma-related symptoms' }
    ]

    const lifeAreas = [
      { id: 'work', name: 'Work or career' },
      { id: 'relationships', name: 'Relationships' },
      { id: 'family', name: 'Family life' },
      { id: 'social', name: 'Social life' },
      { id: 'health', name: 'Physical health' },
      { id: 'finances', name: 'Financial situation' },
      { id: 'hobbies', name: 'Hobbies and interests' },
      { id: 'daily_routine', name: 'Daily routine' }
    ]

    const nextStep = () => {
      if (currentStep.value < 4) {
        currentStep.value++
      }
    }

    const previousStep = () => {
      if (currentStep.value > 1) {
        currentStep.value--
      }
    }

    const submitDiagnosis = async () => {
      loading.value = true
      
      try {
        // Simulate AI diagnosis processing
        await new Promise(resolve => setTimeout(resolve, 2000))
        
        // Store diagnosis data in localStorage for therapist matching
        const diagnosisData = {
          ...form,
          timestamp: new Date().toISOString()
        }
        localStorage.setItem('diagnosis_data', JSON.stringify(diagnosisData))
        
        toast.success('Diagnosis completed! Finding therapists for you...')
        
        // Redirect to therapists page with diagnosis filter
        router.push('/therapists')
        
      } catch (error) {
        toast.error('Failed to process diagnosis. Please try again.')
      } finally {
        loading.value = false
      }
    }

    return {
      currentStep,
      loading,
      form,
      symptoms,
      lifeAreas,
      nextStep,
      previousStep,
      submitDiagnosis
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

.rtl .ml-2 {
  margin-left: 0;
  margin-right: 0.5rem;
}

.rtl .mr-3 {
  margin-right: 0;
  margin-left: 0.75rem;
}

.rtl .-ml-1 {
  margin-left: 0;
  margin-right: -0.25rem;
}

/* Form styling for RTL */
.rtl .form-label {
  text-align: right;
}

.rtl .input-field {
  text-align: right;
}

.rtl .input-field::placeholder {
  text-align: right;
}

/* Checkbox and label alignment for RTL */
.rtl .flex.items-center {
  flex-direction: row-reverse;
}

/* Progress bar text alignment for RTL */
.rtl .flex.justify-between {
  flex-direction: row-reverse;
}
</style> 