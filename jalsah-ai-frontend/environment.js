// ========================================
// ENVIRONMENT CONFIGURATION
// ========================================
// 
// To switch environments, just change the value below:
// 
// LOCAL DEVELOPMENT:
//   API_TARGET: 'http://localhost/shrinks'
// 
// STAGING SERVER:
//   API_TARGET: 'https://beforelive.jalsah.app'
// 
// ========================================

export const ENVIRONMENT_CONFIG = {
  // Change this line to switch environments
  API_TARGET: 'https://beforelive.jalsah.app', // ← CHANGE THIS LINE
  
  // Other settings (usually don't need to change)
  API_BASE_URL: 'https://beforelive.jalsah.app', // ← FIXED: Use full URL for production
  MAIN_SITE_URL: 'https://beforelive.jalsah.app' // This will auto-update based on API_TARGET
};

// Auto-update MAIN_SITE_URL based on API_TARGET
ENVIRONMENT_CONFIG.MAIN_SITE_URL = ENVIRONMENT_CONFIG.API_TARGET;

// Helper function to get current environment name
export function getCurrentEnvironment() {
  if (ENVIRONMENT_CONFIG.API_TARGET.includes('localhost')) {
    return 'LOCAL DEVELOPMENT';
  } else if (ENVIRONMENT_CONFIG.API_TARGET.includes('beforelive') || ENVIRONMENT_CONFIG.API_TARGET.includes('staging')) {
    return 'STAGING SERVER';
  } else {
    return 'UNKNOWN';
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
