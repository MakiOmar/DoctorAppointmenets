import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve, join, dirname } from 'path'
import { ENVIRONMENT_CONFIG } from './environment.js'
import { writeFileSync, copyFileSync, existsSync, mkdirSync, readdirSync, statSync } from 'fs'

export default defineConfig({
  plugins: [
    vue(),
    // Plugin to generate server configuration files
    {
      name: 'generate-server-config',
      writeBundle() {
        // Generate .htaccess for Apache
        const htaccess = `Options -MultiViews
RewriteEngine On

# Handle Angular and Vue.js client-side routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]

# Optional: Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Optional: Set cache headers for static assets
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>`

        // Generate web.config for IIS
        const webConfig = `<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="Handle History Mode and hash fallback" stopProcessing="true">
          <match url="(.*)" />
          <conditions logicalGrouping="MatchAll">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="/" />
        </rule>
      </rules>
    </rewrite>
  </system.webServer>
</configuration>`

        writeFileSync('dist/.htaccess', htaccess)
        writeFileSync('dist/web.config', webConfig)
        
        // Copy countries JSON file to dist folder
        try {
          copyFileSync('countries-codes-and-flags.json', 'dist/countries-codes-and-flags.json')
          console.log('✅ Generated .htaccess and web.config for SPA routing')
          console.log('✅ Copied countries-codes-and-flags.json to dist folder')
        } catch (error) {
          console.warn('⚠️ Could not copy countries-codes-and-flags.json:', error.message)
          console.log('✅ Generated .htaccess and web.config for SPA routing')
        }

        // Recursive copy function for directories
        const copyRecursiveSync = (src, dest) => {
          const exists = existsSync(src)
          const stats = exists && statSync(src)
          const isDirectory = exists && stats.isDirectory()
          
          if (isDirectory) {
            if (!existsSync(dest)) {
              mkdirSync(dest, { recursive: true })
            }
            readdirSync(src).forEach(childItemName => {
              copyRecursiveSync(join(src, childItemName), join(dest, childItemName))
            })
          } else {
            const destDir = dirname(dest)
            if (!existsSync(destDir)) {
              mkdirSync(destDir, { recursive: true })
            }
            copyFileSync(src, dest)
          }
        }

        // Copy fonts folder recursively to dist folder
        try {
          const fontsSource = 'public/fonts'
          const fontsDest = 'dist/fonts'
          
          if (existsSync(fontsSource)) {
            copyRecursiveSync(fontsSource, fontsDest)
            console.log('✅ Copied fonts folder to dist folder')
          } else {
            console.warn('⚠️ Fonts folder not found at:', fontsSource)
          }
        } catch (error) {
          console.error('❌ Error copying fonts folder:', error.message)
        }

        // Copy home folder recursively to dist folder
        try {
          const homeSource = 'public/home'
          const homeDest = 'dist/home'
          
          if (existsSync(homeSource)) {
            copyRecursiveSync(homeSource, homeDest)
            console.log('✅ Copied home folder to dist folder')
          } else {
            console.warn('⚠️ Home folder not found at:', homeSource)
          }
        } catch (error) {
          console.error('❌ Error copying home folder:', error.message)
        }
      }
    }
  ],
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