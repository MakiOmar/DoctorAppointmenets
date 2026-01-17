<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full space-y-8 text-center">
      <div>
        <h1 class="text-6xl text-gray-900 mb-4">404</h1>
        <h2 class="text-2xl text-gray-700 mb-4">
          {{ $t('notFound.title') }}
        </h2>
        <p class="text-gray-500 mb-8">
          {{ $t('notFound.message') }}
        </p>
        <div class="space-y-4">
          <router-link
            to="/"
            class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
          >
            {{ $t('notFound.goHome') }}
          </router-link>
          <button
            @click="$router.go(-1)"
            class="block w-full text-gray-600 hover:text-gray-800 transition-colors"
          >
            {{ $t('notFound.goBack') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Component logic here if needed
</script> 