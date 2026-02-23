#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Environment configurations
const environments = {
  local: {
    VITE_API_TARGET: 'http://localhost/shrinks',
    VITE_API_BASE_URL: '/api',
    VITE_MAIN_SITE_URL: 'http://localhost/shrinks'
  },
  staging: {
    VITE_API_TARGET: 'http://localhost/shrinks',
    VITE_API_BASE_URL: '/api',
    VITE_MAIN_SITE_URL: 'http://localhost/shrinks'
  }
};

function createEnvFile(env) {
  const envPath = path.join(__dirname, '.env');
  const config = environments[env];
  
  let envContent = `# API Configuration - ${env.toUpperCase()} ENVIRONMENT\n`;
  envContent += `VITE_API_TARGET=${config.VITE_API_TARGET}\n`;
  envContent += `VITE_API_BASE_URL=${config.VITE_API_BASE_URL}\n`;
  envContent += `VITE_MAIN_SITE_URL=${config.VITE_MAIN_SITE_URL}\n`;
  
  try {
    fs.writeFileSync(envPath, envContent);
    console.log(`✅ Created .env file for ${env} environment`);
    console.log(`🎯 API Target: ${config.VITE_API_TARGET}`);
    return true;
  } catch (error) {
    console.error(`❌ Error creating .env file: ${error.message}`);
    return false;
  }
}

function showCurrentEnv() {
  const envPath = path.join(__dirname, '.env');
  
  if (fs.existsSync(envPath)) {
    const content = fs.readFileSync(envPath, 'utf8');
    const targetMatch = content.match(/VITE_API_TARGET=(.+)/);
    
    if (targetMatch) {
      const target = targetMatch[1];
      if (target.includes('localhost')) {
        console.log('🏠 Current Environment: LOCAL DEVELOPMENT');
        console.log(`🎯 API Target: ${target}`);
      } else {
        console.log('🚀 Current Environment: STAGING SERVER');
        console.log(`🎯 API Target: ${target}`);
      }
    } else {
      console.log('❓ Current Environment: UNKNOWN');
    }
  } else {
    console.log('🏠 Current Environment: LOCAL DEVELOPMENT (default)');
    console.log('🎯 API Target: http://localhost/shrinks');
  }
}

function showHelp() {
  console.log(`
🌍 Environment Switcher for Jalsah AI Frontend

Usage:
  node switch-env.js [command]

Commands:
  local     Switch to local development environment
  staging   Switch to staging server environment
  current   Show current environment
  help      Show this help message

Examples:
  node switch-env.js local     # Switch to local development
  node switch-env.js staging   # Switch to staging server
  node switch-env.js current   # Show current environment

After switching:
1. Stop the development server (Ctrl+C)
2. Restart with: npm run dev
3. Clear browser cache
4. Test login to verify environment
`);
}

function main() {
  const command = process.argv[2];
  
  switch (command) {
    case 'local':
      console.log('🔄 Switching to LOCAL DEVELOPMENT environment...');
      if (createEnvFile('local')) {
        console.log('\n📋 Next steps:');
        console.log('1. Stop the development server (Ctrl+C)');
        console.log('2. Restart with: npm run dev');
        console.log('3. Clear browser cache');
        console.log('4. Test login to verify environment');
        console.log('\n🎯 Expected console output:');
        console.log('Proxy target: http://localhost/shrinks');
      }
      break;
      
    case 'staging':
      console.log('🔄 Switching to STAGING SERVER environment...');
      if (createEnvFile('staging')) {
        console.log('\n📋 Next steps:');
        console.log('1. Stop the development server (Ctrl+C)');
        console.log('2. Restart with: npm run dev');
        console.log('3. Clear browser cache');
        console.log('4. Test login to verify environment');
        console.log('\n🎯 Expected console output:');
        console.log('Proxy target: http://localhost/shrinks');
      }
      break;
      
    case 'current':
      showCurrentEnv();
      break;
      
    case 'help':
    case '--help':
    case '-h':
      showHelp();
      break;
      
    default:
      console.log('❌ Unknown command. Use "node switch-env.js help" for usage information.');
      process.exit(1);
  }
}

main();
