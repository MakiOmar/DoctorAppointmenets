// ========================================
// ENVIRONMENT CONFIGURATION
// ========================================
//
// This file centralizes environment-specific configuration for the
// Vite dev server and the built production app.
//
// Behavior:
// - When running `npm run dev` (Vite dev server):
//     - API_TARGET    => https://beforelive.jalsah.app
//     - API_BASE_URL  => /api   (frontend talks to /api, Vite proxies to API_TARGET)
//     - MAIN_SITE_URL => https://beforelive.jalsah.app
//
// - When building for production (`npm run build`, `npm run preview`):
//     - API_TARGET    => https://beforelive.jalsah.app
//     - API_BASE_URL  => https://beforelive.jalsah.app (API requests go to beforelive.jalsah.app; use when SPA is on jalsah.online)
//     - MAIN_SITE_URL => https://beforelive.jalsah.app
//
// The Vite config reads these values to:
// - Configure the dev server proxy target (API_TARGET)
// - Expose VITE_API_TARGET and VITE_API_BASE_URL in the built bundle
// ========================================

const npmCommand = process.env.npm_lifecycle_event || '';
const isDevCommand = npmCommand === 'dev' || process.env.NODE_ENV === 'development';

const API_TARGET = isDevCommand ? 'https://beforelive.jalsah.app' : 'https://beforelive.jalsah.app';
// Production: full origin so SPA (e.g. on jalsah.online) hits beforelive.jalsah.app for API. Dev: /api for proxy.
const API_BASE_URL = isDevCommand ? '/api' : 'https://beforelive.jalsah.app';
const MAIN_SITE_URL = isDevCommand ? 'https://beforelive.jalsah.app' : 'https://beforelive.jalsah.app';

export const ENVIRONMENT_CONFIG = {
  API_TARGET,
  API_BASE_URL,
  MAIN_SITE_URL,
};

// Helper function to get current environment name
export function getCurrentEnvironment() {
  if (ENVIRONMENT_CONFIG.API_TARGET.includes('localhost')) {
    return 'LOCAL DEVELOPMENT';
  } else if (ENVIRONMENT_CONFIG.API_TARGET.includes('beforelive') || ENVIRONMENT_CONFIG.API_TARGET.includes('staging')) {
    return 'STAGING SERVER';
  } else {
    return 'PRODUCTION';
  }
}

// Helper function to check if we're in local development
export function isLocalDevelopment() {
  return ENVIRONMENT_CONFIG.API_TARGET.includes('localhost');
}

// Helper function to check if we're in staging
export function isStaging() {
  return ENVIRONMENT_CONFIG.API_TARGET.includes('beforelive') || ENVIRONMENT_CONFIG.API_TARGET.includes('staging');
}

// Log current environment on import
console.log(`🌍 Environment: ${getCurrentEnvironment()}`);
console.log(`🎯 API Target: ${ENVIRONMENT_CONFIG.API_TARGET}`);
