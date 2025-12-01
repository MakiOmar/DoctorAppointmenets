# Environment Switching - Simple Method

## Quick Switch

To switch environments, just edit **ONE FILE**: `environment.js`

### For Local Development:
```javascript
API_TARGET: 'http://localhost/shrinks', // ← Change this line
```

### For Staging Server:
```javascript
API_TARGET: 'https://beforelive.jalsah.app', // ← Change this line
```

## Steps:

1. **Edit** `environment.js`
2. **Change** the `API_TARGET` value
3. **Restart** the development server (`npm run dev`)
4. **Clear** browser cache
5. **Test** login

## Example:

```javascript
// environment.js
export const ENVIRONMENT_CONFIG = {
  API_TARGET: 'http://localhost/shrinks', // ← LOCAL
  // API_TARGET: 'https://beforelive.jalsah.app', // ← STAGING
};
```

## That's it!

No complex commands, no multiple files, just edit one line and restart the server.
