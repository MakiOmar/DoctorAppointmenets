<template>
  <div id="app" class="min-h-screen bg-gray-50 font-sans">
    <Header />
    <router-view />
  </div>
</template>

<script>
import Header from '@/components/Header.vue'

export default {
  name: 'App',
  components: {
    Header
  }
}
</script> 