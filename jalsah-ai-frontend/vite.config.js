import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import { ENVIRONMENT_CONFIG } from './environment.js'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  define: {
    'import.meta.env.VITE_API_TARGET': JSON.stringify(ENVIRONMENT_CONFIG.API_TARGET),
    'import.meta.env.VITE_API_BASE_URL': JSON.stringify(ENVIRONMENT_CONFIG.API_BASE_URL),
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: ENVIRONMENT_CONFIG.API_TARGET,
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
        target: ENVIRONMENT_CONFIG.API_TARGET,
        changeOrigin: true,
        secure: false,
      },
      '/wp-admin/admin-ajax.php': {
        target: ENVIRONMENT_CONFIG.API_TARGET,
        changeOrigin: true,
        secure: false,
      },
      '/wp-json': {
        target: ENVIRONMENT_CONFIG.API_TARGET,
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