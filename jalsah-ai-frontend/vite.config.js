import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import { ENVIRONMENT_CONFIG } from './environment.js'
import { writeFileSync } from 'fs'

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
        console.log('✅ Generated .htaccess and web.config for SPA routing')
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