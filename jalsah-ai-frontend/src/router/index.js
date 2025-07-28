import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/Home.vue')
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/Register.vue'),
    meta: { guest: true }
  },
  // Customer routes
  {
    path: '/diagnosis',
    name: 'Diagnosis',
    component: () => import('@/views/Diagnosis.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/diagnosis-results/:diagnosisId?',
    name: 'DiagnosisResults',
    component: () => import('@/views/DiagnosisResults.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/therapists',
    name: 'Therapists',
    component: () => import('@/views/Therapists.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/therapist/:id',
    name: 'TherapistDetail',
    component: () => import('@/views/TherapistDetail.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/booking/:therapistId',
    name: 'Booking',
    component: () => import('@/views/Booking.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/cart',
    name: 'Cart',
    component: () => import('@/views/Cart.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/appointments',
    name: 'Appointments',
    component: () => import('@/views/Appointments.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('@/views/Profile.vue'),
    meta: { requiresAuth: true, roles: ['customer'] }
  },
  // Doctor routes
  {
    path: '/doctor',
    name: 'DoctorDashboard',
    component: () => import('@/views/DoctorDashboard.vue'),
    meta: { requiresAuth: true, roles: ['doctor', 'clinic_manager'] }
  },
  {
    path: '/therapist-register',
    name: 'TherapistRegister',
    component: () => import('@/views/TherapistRegister.vue')
  },
  // Catch-all route for API endpoints that might be accessed as frontend routes
  {
    path: '/api/ai/:pathMatch(.*)*',
    name: 'APIRedirect',
    beforeEnter: (to, from, next) => {
      // Redirect API calls to the appropriate frontend route
      console.log('API route accessed as frontend route:', to.path)
      
      // Extract locale from query parameters if present
      const locale = to.query.locale
      if (locale) {
        localStorage.setItem('locale', locale)
        localStorage.setItem('jalsah_locale', locale)
      }
      
      if (to.path.startsWith('/api/ai/auth')) {
        // Redirect auth-related API calls to login page
        next('/login')
      } else {
        // For other API calls, redirect to home
        next('/')
      }
    }
  },
  // 404 catch-all route
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  

  
  // Check if authentication is required
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {

    next('/login')
    return
  }
  
  // Check if user is already authenticated and trying to access guest pages
  if (to.meta.guest && authStore.isAuthenticated) {
    // Redirect based on user role
    const userRole = authStore.user?.role
    if (userRole === 'doctor' || userRole === 'clinic_manager') {
      next('/doctor')
    } else {
      next('/therapists') // Default customer landing page
    }
    return
  }
  
  // Check role-based access
  if (to.meta.roles && authStore.isAuthenticated) {
    const userRole = authStore.user?.role
    const userRoles = authStore.user?.roles || []
    
    // Check if user has any of the required roles
    const hasRequiredRole = to.meta.roles.some(role => 
      userRoles.includes(role) || userRole === role
    )
    
    if (!hasRequiredRole) {

      // Redirect to appropriate dashboard based on role
      if (userRole === 'doctor' || userRole === 'clinic_manager') {
        next('/doctor')
      } else {
        next('/therapists')
      }
      return
    }
  }
  
  // Allow authenticated users to access the homepage without redirecting
  // This enables users to stay on homepage after login
  next()
})

export default router 