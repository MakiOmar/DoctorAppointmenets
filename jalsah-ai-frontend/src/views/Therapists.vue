<template>
  <div>
    <Header />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          Find Your Perfect Therapist
        </h1>
        <p class="text-lg text-gray-600">
          Browse our qualified therapists and find the one that's right for you.
        </p>
      </div>

      <!-- Filters -->
      <div class="card mb-8">
        <div class="grid md:grid-cols-4 gap-4">
          <div>
            <label class="form-label">Specialization</label>
            <select v-model="filters.specialization" class="input-field">
              <option value="">All specializations</option>
              <option value="anxiety">Anxiety Disorders</option>
              <option value="depression">Depression</option>
              <option value="stress">Stress Management</option>
              <option value="relationships">Relationship Issues</option>
              <option value="trauma">Trauma and PTSD</option>
              <option value="addiction">Addiction</option>
              <option value="eating">Eating Disorders</option>
              <option value="sleep">Sleep Disorders</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">Price Range</label>
            <select v-model="filters.priceRange" class="input-field">
              <option value="">Any price</option>
              <option value="0-50">$0 - $50</option>
              <option value="50-100">$50 - $100</option>
              <option value="100-150">$100 - $150</option>
              <option value="150+">$150+</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">Availability</label>
            <select v-model="filters.availability" class="input-field">
              <option value="">Any time</option>
              <option value="morning">Morning</option>
              <option value="afternoon">Afternoon</option>
              <option value="evening">Evening</option>
              <option value="weekend">Weekend</option>
            </select>
          </div>
          
          <div>
            <label class="form-label">Sort By</label>
            <select v-model="filters.sortBy" class="input-field">
              <option value="rating">Highest Rated</option>
              <option value="price_low">Lowest Price</option>
              <option value="price_high">Highest Price</option>
              <option value="availability">Most Available</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">Loading therapists...</p>
      </div>

      <!-- Therapists Grid -->
      <div v-else-if="filteredTherapists.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="therapist in filteredTherapists" 
          :key="therapist.id"
          class="card hover:shadow-lg transition-shadow cursor-pointer"
          @click="viewTherapist(therapist.id)"
        >
          <!-- Therapist Image -->
          <div class="relative mb-4">
            <img 
              :src="therapist.photo || '/default-therapist.jpg'" 
              :alt="therapist.name"
              class="w-full h-48 object-cover rounded-lg"
            />
            <div class="absolute top-2 right-2 bg-primary-600 text-white px-2 py-1 rounded-full text-sm font-medium">
              {{ therapist.price?.others || 'Contact' }}
            </div>
          </div>

          <!-- Therapist Info -->
          <div class="space-y-3">
            <h3 class="text-xl font-semibold text-gray-900">{{ therapist.name }}</h3>
            
            <div class="flex items-center space-x-2">
              <div class="flex text-yellow-400">
                <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= getAverageRating(therapist) ? 'fill-current' : 'fill-gray-300'" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
              <span class="text-sm text-gray-600">{{ getAverageRating(therapist).toFixed(1) }} ({{ therapist.diagnoses?.length || 0 }} reviews)</span>
            </div>

            <p class="text-gray-600 text-sm line-clamp-2">
              {{ therapist.bio || 'Experienced therapist specializing in mental health and well-being.' }}
            </p>

            <!-- Specializations -->
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="diagnosis in therapist.diagnoses?.slice(0, 3)" 
                :key="diagnosis.id"
                class="bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded-full"
              >
                {{ diagnosis.name }}
              </span>
              <span v-if="therapist.diagnoses?.length > 3" class="text-xs text-gray-500">
                +{{ therapist.diagnoses.length - 3 }} more
              </span>
            </div>

            <!-- Availability -->
            <div class="flex items-center text-sm text-gray-600">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>Next available: {{ therapist.earliest_slot || 'Contact for availability' }}</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2 pt-2">
              <button 
                @click.stop="bookAppointment(therapist.id)"
                class="flex-1 btn-primary text-sm py-2"
              >
                Book Session
              </button>
              <button 
                @click.stop="viewTherapist(therapist.id)"
                class="btn-outline text-sm py-2"
              >
                View Profile
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No therapists found</h3>
        <p class="text-gray-600">Try adjusting your filters or check back later for new therapists.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/services/api'
import Header from '@/components/Header.vue'

export default {
  name: 'Therapists',
  components: {
    Header
  },
  setup() {
    const router = useRouter()
    const toast = useToast()
    
    const loading = ref(true)
    const therapists = ref([])
    
    const filters = reactive({
      specialization: '',
      priceRange: '',
      availability: '',
      sortBy: 'rating'
    })

    const filteredTherapists = computed(() => {
      let filtered = [...therapists.value]

      // Filter by specialization
      if (filters.specialization) {
        filtered = filtered.filter(therapist => 
          therapist.diagnoses?.some(d => 
            d.name.toLowerCase().includes(filters.specialization.toLowerCase())
          )
        )
      }

      // Filter by price range
      if (filters.priceRange) {
        const [min, max] = filters.priceRange.split('-').map(Number)
        filtered = filtered.filter(therapist => {
          const price = therapist.price?.others || 0
          if (max) {
            return price >= min && price <= max
          } else {
            return price >= min
          }
        })
      }

      // Sort therapists
      filtered.sort((a, b) => {
        switch (filters.sortBy) {
          case 'rating':
            return getAverageRating(b) - getAverageRating(a)
          case 'price_low':
            return (a.price?.others || 0) - (b.price?.others || 0)
          case 'price_high':
            return (b.price?.others || 0) - (a.price?.others || 0)
          default:
            return 0
        }
      })

      return filtered
    })

    const getAverageRating = (therapist) => {
      if (!therapist.diagnoses || therapist.diagnoses.length === 0) return 0
      const total = therapist.diagnoses.reduce((sum, d) => sum + (d.rating || 0), 0)
      return total / therapist.diagnoses.length
    }

    const loadTherapists = async () => {
      loading.value = true
      try {
        const response = await api.get('/api/ai/therapists')
        therapists.value = response.data.data || []
      } catch (error) {
        toast.error('Failed to load therapists')
        console.error('Error loading therapists:', error)
      } finally {
        loading.value = false
      }
    }

    const viewTherapist = (therapistId) => {
      router.push(`/therapist/${therapistId}`)
    }

    const bookAppointment = (therapistId) => {
      router.push(`/booking/${therapistId}`)
    }

    onMounted(() => {
      loadTherapists()
    })

    return {
      loading,
      therapists,
      filters,
      filteredTherapists,
      getAverageRating,
      viewTherapist,
      bookAppointment
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style> 