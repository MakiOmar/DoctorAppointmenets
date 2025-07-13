<template>
  <div>
    <Header />
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          AI-Powered Mental Health Diagnosis
        </h1>
        <p class="text-lg text-gray-600">
          Get a personalized assessment to help you find the right therapist for your needs.
        </p>
      </div>

      <!-- Diagnosis Form -->
      <div class="card">
        <form @submit.prevent="submitDiagnosis" class="space-y-6">
          <!-- Step 1: Basic Information -->
          <div v-if="currentStep === 1">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 1: Tell us about yourself</h2>
            
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="form-label">How would you describe your current mood?</label>
                <select v-model="form.mood" class="input-field" required>
                  <option value="">Select your mood</option>
                  <option value="happy">Happy and content</option>
                  <option value="neutral">Neutral</option>
                  <option value="sad">Sad or depressed</option>
                  <option value="anxious">Anxious or worried</option>
                  <option value="angry">Angry or irritable</option>
                  <option value="stressed">Stressed or overwhelmed</option>
                </select>
              </div>
              
              <div>
                <label class="form-label">How long have you been feeling this way?</label>
                <select v-model="form.duration" class="input-field" required>
                  <option value="">Select duration</option>
                  <option value="less_than_week">Less than a week</option>
                  <option value="few_weeks">A few weeks</option>
                  <option value="few_months">A few months</option>
                  <option value="six_months">6 months or more</option>
                  <option value="year_plus">Over a year</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Step 2: Symptoms -->
          <div v-if="currentStep === 2">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 2: What symptoms are you experiencing?</h2>
            
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
                    {{ symptom.name }}
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Impact -->
          <div v-if="currentStep === 3">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 3: How is this affecting your life?</h2>
            
            <div class="space-y-4">
              <div>
                <label class="form-label">How much are these symptoms affecting your daily life?</label>
                <select v-model="form.impact" class="input-field" required>
                  <option value="">Select impact level</option>
                  <option value="minimal">Minimal impact</option>
                  <option value="mild">Mild impact</option>
                  <option value="moderate">Moderate impact</option>
                  <option value="severe">Severe impact</option>
                  <option value="extreme">Extreme impact</option>
                </select>
              </div>
              
              <div>
                <label class="form-label">Which areas of your life are most affected?</label>
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
                      {{ area.name }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4: Goals -->
          <div v-if="currentStep === 4">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Step 4: What are your goals for therapy?</h2>
            
            <div class="space-y-4">
              <div>
                <label class="form-label">What would you like to achieve through therapy?</label>
                <textarea
                  v-model="form.goals"
                  rows="4"
                  class="input-field"
                  placeholder="Describe your goals and what you hope to accomplish..."
                  required
                ></textarea>
              </div>
              
              <div>
                <label class="form-label">What type of therapy approach interests you most?</label>
                <select v-model="form.preferredApproach" class="input-field">
                  <option value="">No preference</option>
                  <option value="cbt">Cognitive Behavioral Therapy (CBT)</option>
                  <option value="psychodynamic">Psychodynamic Therapy</option>
                  <option value="humanistic">Humanistic Therapy</option>
                  <option value="mindfulness">Mindfulness-Based Therapy</option>
                  <option value="solution_focused">Solution-Focused Therapy</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-between pt-6">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="previousStep"
              class="btn-secondary"
            >
              Previous
            </button>
            <div></div>
            
            <button
              v-if="currentStep < 4"
              type="button"
              @click="nextStep"
              class="btn-primary"
            >
              Next
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
                Analyzing...
              </span>
              <span v-else>Get My Diagnosis</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Progress Bar -->
      <div class="mt-8">
        <div class="flex justify-between text-sm text-gray-600 mb-2">
          <span>Step {{ currentStep }} of 4</span>
          <span>{{ Math.round((currentStep / 4) * 100) }}% Complete</span>
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
import Header from '@/components/Header.vue'

export default {
  name: 'Diagnosis',
  components: {
    Header
  },
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