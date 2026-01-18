<template>
  <div class="doctor-dashboard">
    <div class="container mx-auto px-4 py-8">
      <div class="bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-3xl text-gray-800 mb-6">Doctor Dashboard</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Profile Card -->
          <div class="bg-primary-50 rounded-lg p-6">
            <h2 class="text-xl text-blue-800 mb-4">Profile</h2>
            <div class="space-y-2">
              <p><strong>Name:</strong> {{ user?.first_name }} {{ user?.last_name }}</p>
              <p><strong>Email:</strong> {{ user?.email }}</p>
              <p><strong>Role:</strong> {{ user?.role }}</p>
            </div>
            <button class="mt-4 bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">
              Edit Profile
            </button>
          </div>

          <!-- Appointments Card -->
          <div class="bg-green-50 rounded-lg p-6">
            <h2 class="text-xl text-green-800 mb-4">Today's Appointments</h2>
            <div class="text-center">
              <div class="text-3xl text-green-600">{{ todayAppointments }}</div>
              <p class="text-green-700">Appointments</p>
            </div>
            <button class="mt-4 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
              View All
            </button>
          </div>

          <!-- Earnings Card -->
          <div class="bg-purple-50 rounded-lg p-6">
            <h2 class="text-xl text-purple-800 mb-4">This Month</h2>
            <div class="text-center">
              <div class="text-3xl text-purple-600">{{ formatPrice(monthlyEarnings, 'en') }}</div>
              <p class="text-purple-700">Earnings</p>
            </div>
            <button class="mt-4 bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 w-full">
              View Details
            </button>
          </div>
        </div>

        <!-- Recent Appointments -->
        <div class="mt-8">
          <h2 class="text-2xl text-gray-800 mb-4">Recent Appointments</h2>
          <div class="bg-white border rounded-lg overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
              <p class="text-gray-600">No recent appointments to display</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
          <h2 class="text-2xl text-gray-800 mb-4">Quick Actions</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button class="bg-indigo-600 text-white p-4 rounded-lg hover:bg-indigo-700 transition-colors">
              <div class="text-center">
                <div class="text-2xl mb-2">📅</div>
                <div class="font-semibold">Schedule</div>
              </div>
            </button>
            <button class="bg-teal-600 text-white p-4 rounded-lg hover:bg-teal-700 transition-colors">
              <div class="text-center">
                <div class="text-2xl mb-2">👥</div>
                <div class="font-semibold">Patients</div>
              </div>
            </button>
            <button class="bg-orange-600 text-white p-4 rounded-lg hover:bg-orange-700 transition-colors">
              <div class="text-center">
                <div class="text-2xl mb-2">💰</div>
                <div class="font-semibold">Earnings</div>
              </div>
            </button>
            <button class="bg-red-600 text-white p-4 rounded-lg hover:bg-red-700 transition-colors">
              <div class="text-center">
                <div class="text-2xl mb-2">⚙️</div>
                <div class="font-semibold">Settings</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { formatPrice } from '@/utils/currency'

const authStore = useAuthStore()
const user = computed(() => authStore.user)

// Mock data - replace with actual API calls
const todayAppointments = ref(5)
const monthlyEarnings = ref(2500)
</script>

<style scoped>
.doctor-dashboard {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style> 