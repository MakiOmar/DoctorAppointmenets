import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: process.env.VITE_API_TARGET || 'http://localhost/shrinks',
        changeOrigin: true,
        secure: false,
        configure: (proxy, options) => {
          proxy.on('proxyReq', (proxyReq, req, res) => {
            // Remove trailing slash from API requests
            if (proxyReq.path.endsWith('/') && proxyReq.path !== '/') {
              proxyReq.path = proxyReq.path.slice(0, -1);
            }
          });
        },
      },
      '/wp-admin': {
        target: process.env.VITE_API_TARGET || 'http://localhost/shrinks',
        changeOrigin: true,
        secure: false,
      },
      '/wp-json': {
        target: process.env.VITE_API_TARGET || 'http://localhost/shrinks',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
  },
}) 