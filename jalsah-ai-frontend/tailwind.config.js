/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#e8ebf0',
          100: '#d1d7e1',
          200: '#a3afc3',
          300: '#7587a5',
          400: '#475f87',
          500: '#112145', // Main primary color
          600: '#0e1a37',
          700: '#0a1429',
          800: '#070d1b',
          900: '#03070d',
        },
        secondary: {
          50: '#f9f6f3',
          100: '#f3ede7',
          200: '#e7dbcf',
          300: '#dbc9b7',
          400: '#cfb79f',
          500: '#c5a482', // Main secondary color
          600: '#9e8368',
          700: '#77624e',
          800: '#4f4134',
          900: '#28211a',
        },
      },
      fontFamily: {
        sans: ['Cairo', 'Inter', 'sans-serif'],
        cairo: ['Cairo', 'sans-serif'],
        inter: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
} 