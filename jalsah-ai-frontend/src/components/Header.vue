<template>
  <header class="sticky top-0 z-50 bg-primary-500 shadow-sm relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-14 sm:h-16">
        <!-- Left: Logo -->
        <div class="flex items-center gap-4">
          <!-- Logo -->
          <router-link to="/" class="flex items-center">
            <img 
              src="/home/header-logo.png" 
              class="h-8 sm:h-10"
            />
          </router-link>
        </div>

        <!-- Right: Cart, Notification, and Hamburger Menu -->
        <div class="flex items-center gap-2 sm:gap-4">
          <!-- Cart Icon with Badge (Only for logged in users) -->
          <router-link 
            v-if="isAuthenticated"
            to="/cart" 
            class="relative p-1.5 sm:p-2 text-white hover:opacity-80 transition-opacity"
          >
            <img 
              src="/home/Layer-26.png" 
              alt="Cart" 
              class="h-[1.4rem]"
            />
            <span 
              v-if="cartItemCount > 0"
              class="absolute -top-1 -right-1 bg-secondary-500 text-white text-xs rounded-full h-4 w-4 sm:h-5 sm:w-5 flex items-center justify-center min-w-[16px] sm:min-w-[20px] px-0.5 sm:px-1"
            >
              {{ cartItemCount > 99 ? '99+' : cartItemCount }}
            </span>
          </router-link>

          <!-- Notification Icon with Badge -->
          <SessionMessagesNotification v-if="isAuthenticated" />

          <!-- Hamburger Menu -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="hamburger-menu-button flex items-center gap-1.5 sm:gap-2 text-secondary-500 hover:opacity-80 transition-opacity p-1.5 sm:p-2"
          >
            <span class="font-medium text-2xl sm:text-[40px]">{{ $t('nav.menu') }}</span>
            <img src="/menu-icon.png" alt="Menu" class="h-5 sm:h-6">
          </button>
        </div>
      </div>

    </div>

    <!-- Backdrop Overlay -->
    <Transition
      enter-active-class="transition-opacity ease-linear duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity ease-linear duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileMenuOpen"
        @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
      ></div>
    </Transition>

    <!-- Side Menu -->
    <Transition
      enter-active-class="transition-transform ease-out duration-300"
      enter-from-class="transform -translate-x-full"
      enter-to-class="transform translate-x-0"
      leave-active-class="transition-transform ease-in duration-250"
      leave-from-class="transform translate-x-0"
      leave-to-class="transform -translate-x-full"
    >
      <div
        v-if="mobileMenuOpen"
        class="side-menu fixed top-0 left-0 h-full w-80 max-w-[85vw] bg-white shadow-2xl z-50 overflow-y-auto"
        :dir="locale"
      >
        <!-- Side Menu Header with Close Button -->
        <div class="sticky top-0 bg-primary-500 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between border-b border-primary-600 z-10">
          <h2 class="text-lg sm:text-xl text-white">{{ $t('nav.menu') }}</h2>
          <button
            @click="mobileMenuOpen = false"
            class="p-1.5 sm:p-2 text-white hover:bg-primary-600 rounded-md transition-colors"
            :aria-label="$t('common.close')"
          >
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Side Menu Content -->
        <div class="px-3 sm:px-4 py-4 sm:py-6">
          <!-- Authenticated User Menu -->
          <template v-if="isAuthenticated">
            <div class="space-y-1">
              <!-- Home -->
              <router-link
                to="/"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>{{ $t('nav.home') }}</span>
              </router-link>
              
              <!-- My Appointments -->
              <router-link
                to="/appointments"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/appointments' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>{{ $t('nav.appointments') }}</span>
              </router-link>
              
              <!-- Therapists -->
              <router-link
                to="/therapists"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/therapists' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span>{{ $t('nav.therapists') }}</span>
              </router-link>
              
              <!-- AI Recommended Therapists -->
              <router-link
                v-if="!loadingDiagnosis"
                :to="hasPreviousDiagnosis ? `/diagnosis-results/${lastDiagnosisId}` : '/diagnosis'"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <span>{{ $t('nav.therapistsSubtitle') }}</span>
              </router-link>
              <div
                v-else
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-500"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
              >
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-500 flex-shrink-0"></div>
                <span>{{ $t('common.loading') }}</span>
              </div>
              
              <!-- Messages -->
              <router-link
                to="/notifications"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/notifications' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span>{{ $t('nav.notifications') }}</span>
              </router-link>
              
              <!-- Profile -->
              <router-link
                to="/profile"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/profile' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>{{ $t('nav.profile') }}</span>
              </router-link>
              
              <!-- Customer Service -->
              <a
                href="http://wa.me/+201097799323"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                <span>{{ $t('nav.customerService') }}</span>
              </a>
              
              <!-- Terms and Conditions -->
              <a
                href="https://jalsah.online/terms.html"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>{{ $t('nav.termsAndConditions') }}</span>
              </a>
              
              <!-- Logout -->
              <button
                @click="logout"
                class="flex items-center gap-2.5 sm:gap-3 w-full text-right px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-red-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>{{ $t('nav.logout') }}</span>
              </button>
            </div>
          </template>

          <!-- Non-Authenticated User Menu -->
          <template v-else>
            <div class="space-y-1">
              <!-- Home -->
              <router-link
                to="/"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>{{ $t('nav.home') }}</span>
              </router-link>
              
              <!-- Therapists -->
              <router-link
                to="/therapists"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="[{ 'bg-gray-50 text-primary-500': $route.path === '/therapists' }, locale === 'ar' ? 'flex-row-reverse' : '']"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span>{{ $t('nav.therapists') }}</span>
              </router-link>
              
              <!-- Login -->
              <router-link
                to="/login"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span>{{ $t('nav.login') }}</span>
              </router-link>
              
              <!-- Register -->
              <router-link
                to="/register"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>{{ $t('nav.register') }}</span>
              </router-link>
              
              <!-- Customer Service -->
              <a
                href="http://wa.me/+201097799323"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2.5 sm:gap-3 px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 hover:bg-gray-50 hover:text-primary-500 rounded-md transition-colors"
                :class="locale === 'ar' ? 'flex-row-reverse' : ''"
                @click="mobileMenuOpen = false"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                <span>{{ $t('nav.customerService') }}</span>
              </a>
            </div>
          </template>
        </div>
      </div>
    </Transition>
  </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useSettingsStore } from '@/stores/settings'
import { useI18n } from 'vue-i18n'
import SessionMessagesNotification from './SessionMessagesNotification.vue'
import api from '@/services/api'

export default {
  name: 'Header',
  components: {
    SessionMessagesNotification
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    const settingsStore = useSettingsStore()
    const { locale } = useI18n()
    
    const mobileMenuOpen = ref(false)
    const lastDiagnosisId = ref(null)
    const loadingDiagnosis = ref(false)

    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const cartItemCount = computed(() => cartStore.itemCount)
    
    const hasPreviousDiagnosis = computed(() => {
      return lastDiagnosisId.value !== null
    })

    const logout = () => {
      mobileMenuOpen.value = false
      authStore.logout(true)
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

    // Prevent body scroll when menu is open
    watch(mobileMenuOpen, (isOpen) => {
      if (isOpen) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    })

    const handleClickOutside = (event) => {
      // Close menu if clicking outside the side menu
      // The backdrop handles closing, so this is mainly for ESC key or other edge cases
      const sideMenu = event.target.closest('[class*="fixed top-0 left-0"]')
      const menuButton = event.target.closest('.hamburger-menu-button')
      if (!sideMenu && !menuButton && mobileMenuOpen.value) {
        mobileMenuOpen.value = false
      }
    }

    const handleEscapeKey = (event) => {
      if (event.key === 'Escape' && mobileMenuOpen.value) {
        mobileMenuOpen.value = false
      }
    }

    onMounted(async () => {
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
      
      await settingsStore.loadSettings()
      
      if (authStore.user) {
        fetchLastDiagnosisId()
      }
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('keydown', handleEscapeKey)
      document.body.style.overflow = '' // Reset overflow on unmount
    })

    return {
      locale,
      mobileMenuOpen,
      isAuthenticated,
      cartItemCount,
      logout,
      lastDiagnosisId,
      hasPreviousDiagnosis,
      loadingDiagnosis
    }
  }
}
</script>