<template>
  <div class="flex text-yellow-400">
    <!-- Debug: Rating = {{ rating }} -->
    <svg 
      v-for="i in 5" 
      :key="i" 
      :class="[size, getStarClass(i, rating)]"
      viewBox="0 0 20 20"
    >
      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292c.3.921-.755 1.688-1.54 1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
    </svg>
  </div>
</template>

<script>
export default {
  name: 'StarRating',
  props: {
    rating: {
      type: Number,
      default: 0
    },
    size: {
      type: String,
      default: 'w-4 h-4'
    }
  },
  methods: {
    getStarClass(i, rating) {
      console.log(`Star ${i}, Rating: ${rating}, Type: ${typeof rating}`)
      const fullRating = Math.floor(rating)
      const partialRating = rating - fullRating
      
      if (i <= fullRating) {
        return 'fill-current' // Full yellow star
      } else if (i === fullRating + 1 && partialRating > 0) {
        return 'fill-current' // Partial star (we'll handle this differently)
      } else {
        return 'fill-gray-300' // Empty gray star
      }
    }
  }
}
</script> 