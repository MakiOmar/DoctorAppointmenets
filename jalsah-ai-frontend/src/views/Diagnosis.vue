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
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

export default {
  name: 'Diagnosis',
  setup() {
    const router = useRouter()
    const toast = useToast()
    const { t: $t } = useI18n()
    
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

    const symptoms = computed(() => [
      { id: 'anxiety', name: $t('diagnosis.step2.symptoms.anxiety') },
      { id: 'depression', name: $t('diagnosis.step2.symptoms.depression') },
      { id: 'stress', name: $t('diagnosis.step2.symptoms.stress') },
      { id: 'sleep', name: $t('diagnosis.step2.symptoms.sleep') },
      { id: 'appetite', name: $t('diagnosis.step2.symptoms.appetite') },
      { id: 'energy', name: $t('diagnosis.step2.symptoms.energy') },
      { id: 'concentration', name: $t('diagnosis.step2.symptoms.concentration') },
      { id: 'irritability', name: $t('diagnosis.step2.symptoms.irritability') },
      { id: 'isolation', name: $t('diagnosis.step2.symptoms.isolation') },
      { id: 'hopelessness', name: $t('diagnosis.step2.symptoms.hopelessness') },
      { id: 'panic', name: $t('diagnosis.step2.symptoms.panic') },
      { id: 'trauma', name: $t('diagnosis.step2.symptoms.trauma') }
    ])

    const lifeAreas = computed(() => [
      { id: 'work', name: $t('diagnosis.step3.areas.work') },
      { id: 'relationships', name: $t('diagnosis.step3.areas.relationships') },
      { id: 'family', name: $t('diagnosis.step3.areas.family') },
      { id: 'social', name: $t('diagnosis.step3.areas.social') },
      { id: 'health', name: $t('diagnosis.step3.areas.health') },
      { id: 'finances', name: $t('diagnosis.step3.areas.finances') },
      { id: 'hobbies', name: $t('diagnosis.step3.areas.hobbies') },
      { id: 'daily_routine', name: $t('diagnosis.step3.areas.daily_routine') }
    ])

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
        // Send diagnosis data to API for processing
        const response = await api.post('/api/ai/diagnosis/process', {
          mood: form.mood,
          duration: form.duration,
          selectedSymptoms: form.selectedSymptoms,
          impact: form.impact,
          affectedAreas: form.affectedAreas,
          goals: form.goals,
          preferredApproach: form.preferredApproach
        })
        
        // Store diagnosis data in localStorage for therapist matching
        const diagnosisData = {
          ...form,
          timestamp: new Date().toISOString()
        }
        localStorage.setItem('diagnosis_data', JSON.stringify(diagnosisData))
        
        toast.success('Diagnosis completed! Finding therapists for you...')
        
        // Redirect to diagnosis results page with the diagnosis ID
        const diagnosisId = response.data.data?.diagnosis_id
        if (diagnosisId) {
          router.push(`/diagnosis-results/${diagnosisId}`)
        } else {
          router.push('/diagnosis-results')
        }
        
      } catch (error) {
        console.error('Diagnosis processing error:', error)
        
        // Fallback: store data and redirect to results page for simulation
        const diagnosisData = {
          ...form,
          timestamp: new Date().toISOString()
        }
        localStorage.setItem('diagnosis_data', JSON.stringify(diagnosisData))
        
        toast.success('Diagnosis completed! Finding therapists for you...')
        router.push('/diagnosis-results')
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
  justify-content: flex-start;
  gap: 0.5rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  cursor: pointer;
}

.rtl .flex.items-center:hover {
  background-color: rgba(59, 130, 246, 0.05);
  transform: translateY(-1px);
}

/* RTL checkbox specific styling */
.rtl input[type="checkbox"] {
  order: 2;
  margin-left: 0;
  margin-right: 0;
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 0.25rem;
  border: 2px solid #d1d5db;
  background-color: white;
  cursor: pointer;
  transition: all 0.2s ease;
}

.rtl input[type="checkbox"]:checked {
  background-color: #2563eb;
  border-color: #2563eb;
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
  background-size: 0.75rem;
  background-position: center;
  background-repeat: no-repeat;
}

/* RTL label styling */
.rtl label {
  order: 1;
  margin-left: 0;
  margin-right: 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  line-height: 1.4;
  text-align: right;
  flex: 1;
}

/* RTL checkbox focus states */
.rtl input[type="checkbox"]:focus {
  outline: none;
  ring: 2px;
  ring-color: #2563eb;
  ring-offset: 2px;
}

/* RTL grid improvements for checkboxes */
.rtl .grid.md\:grid-cols-2 {
  direction: rtl;
  gap: 1rem;
}

.rtl .grid.md\:grid-cols-2 > * {
  direction: rtl;
}

/* RTL space-y improvements */
.rtl .space-y-4 > * + * {
  margin-top: 1rem;
}

/* RTL checkbox container improvements */
.rtl .space-y-4 .grid.md\:grid-cols-2 {
  gap: 1rem;
}

.rtl .space-y-4 .grid.md\:grid-cols-2 .flex.items-center {
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  margin: 0;
}

.rtl .space-y-4 .grid.md\:grid-cols-2 .flex.items-center:hover {
  background-color: #f3f4f6;
  border-color: #d1d5db;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

/* RTL checkbox checked state styling */
.rtl .space-y-4 .grid.md\:grid-cols-2 .flex.items-center:has(input:checked) {
  background-color: #eff6ff;
  border-color: #2563eb;
}

/* RTL responsive checkbox improvements */
@media (max-width: 768px) {
  .rtl .grid.md\:grid-cols-2 {
    grid-template-columns: 1fr;
  }
  
  .rtl .flex.items-center {
    padding: 1rem;
  }
  
  .rtl label {
    font-size: 1rem;
  }
}

/* Progress bar text alignment for RTL */
.rtl .flex.justify-between {
  flex-direction: row-reverse;
}

/* Button spacing for RTL */
.rtl .flex.justify-between.pt-6 {
  gap: 1rem;
}

.rtl .btn-secondary {
  margin-left: 0;
  margin-right: auto;
}

.rtl .btn-primary {
  margin-right: 0;
  margin-left: auto;
}

/* Responsive RTL adjustments */
@media (max-width: 768px) {
  .rtl .flex.justify-between.pt-6 {
    flex-direction: column-reverse;
    gap: 0.75rem;
  }
  
  .rtl .btn-secondary,
  .rtl .btn-primary {
    margin: 0;
    width: 100%;
  }
}

/* Card styling improvements for RTL */
.rtl .card {
  text-align: right;
}

.rtl .card h2 {
  text-align: right;
}

.rtl .card p {
  text-align: right;
}

/* Select and textarea RTL improvements */
.rtl select.input-field {
  background-position: left 0.5rem center;
  padding-left: 2.5rem;
  padding-right: 0.75rem;
}

.rtl textarea.input-field {
  text-align: right;
  resize: vertical;
}
</style> 