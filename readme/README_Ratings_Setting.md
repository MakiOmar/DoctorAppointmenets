# Ratings Setting for Jalsah AI Frontend

## Overview
This feature adds a new setting to enable/disable ratings and their filtration in the Jalsah AI frontend. When disabled, all rating-related UI elements are hidden and rating-based filtering is disabled.

## Implementation Details

### 1. WordPress Admin Setting
- **Location**: WordPress Admin → Jalsah AI → General Settings
- **Setting Name**: "Enable Ratings & Reviews"
- **Option Key**: `snks_ai_ratings_enabled`
- **Default Value**: `1` (enabled)
- **Description**: "Enable this to show star ratings, review counts, and allow rating-based filtering on the frontend. When disabled, ratings will be hidden from therapist cards and filtering options."

### 2. Backend Changes

#### Settings Storage
- Added `snks_ai_ratings_enabled` option to WordPress options table
- Default value: `'1'` (enabled)

#### API Endpoints Updated
- **REST API**: `/wp-json/jalsah-ai/v1/ai-settings`
- **AJAX Endpoint**: `get_ai_settings` (registered in AI integration)
- Both endpoints now include `ratings_enabled` boolean field

### 3. Frontend Changes

#### Settings Store (`jalsah-ai-frontend/src/stores/settings.js`)
- Added `ratingsEnabled` reactive state
- Added `isRatingsEnabled` computed getter
- Updated all settings loading/saving functions to handle the new setting
- Added fallback handling for API failures

#### Components Updated

##### TherapistCard Component
- **Files**: 
  - `jalsah-ai-frontend/src/components/TherapistCard.vue`
  - `jalsah-ai-frontend/components/TherapistCard.vue`
- **Changes**: 
  - Star rating display is conditionally shown based on `settingsStore.isRatingsEnabled`
  - Rating text and count are hidden when ratings are disabled
  - Settings store is passed as a prop for better component isolation
  - Added null checks to prevent errors when settings store is not available

##### Therapists View
- **File**: `jalsah-ai-frontend/src/views/Therapists.vue`
- **Changes**:
  - "Highest Rated" filter option is conditionally shown
  - Default sort changes from "rating" to "nearest_appointment" when ratings are disabled
  - Added watcher to handle dynamic changes in ratings setting
  - `getAverageRating` function returns 0 when ratings are disabled
  - Settings store is passed to TherapistCard components as props

##### TherapistDetail View
- **File**: `jalsah-ai-frontend/src/views/TherapistDetail.vue`
- **Changes**:
  - Rating display section is conditionally shown
  - Star rating and review count are hidden when ratings are disabled
  - Added null checks for settings store

### 4. Behavior When Ratings Are Disabled

#### UI Elements Hidden
- Star rating components (`StarRating.vue`)
- Rating text (e.g., "4.5 (12 reviews)")
- "Highest Rated" filter option in therapist listing

#### Functionality Changes
- Rating-based sorting is disabled
- Default sort changes to "nearest_appointment"
- All rating calculations return 0
- No rating data is displayed anywhere in the UI

#### Filter Options Available When Ratings Disabled
- Specialization/Diagnosis filter
- Price range filter (lowest/highest)
- Nearest appointment filter (closest/farthest)
- Sort by: lowest price, highest price, nearest appointment

### 5. Testing

#### To Test the Feature:
1. **Enable Ratings**:
   - Go to WordPress Admin → Jalsah AI → General Settings
   - Check "Enable Ratings & Reviews"
   - Save settings
   - Verify ratings appear in frontend

2. **Disable Ratings**:
   - Go to WordPress Admin → Jalsah AI → General Settings
   - Uncheck "Enable Ratings & Reviews"
   - Save settings
   - Verify ratings are hidden in frontend
   - Verify "Highest Rated" filter option is hidden
   - Verify default sort changes to "nearest_appointment"

#### Expected Behavior:
- **When Enabled**: All rating features work as before
- **When Disabled**: 
  - No rating stars shown
  - No rating text shown
  - No "Highest Rated" filter option
  - Default sort is "nearest_appointment"
  - All other functionality remains intact

### 6. Technical Notes

#### Settings Propagation
- Settings are loaded from WordPress options
- Cached in localStorage for performance
- Automatically refreshed when settings change
- All components reactively update when setting changes

#### Backward Compatibility
- Default value is `true` (enabled)
- Existing installations will continue to show ratings
- No breaking changes to existing functionality

#### Performance Impact
- Minimal performance impact
- Settings are cached in localStorage
- Conditional rendering prevents unnecessary DOM elements
- Rating calculations are skipped when disabled

#### Error Handling
- Graceful fallback when API endpoints fail
- Default values used when settings cannot be loaded
- Null checks prevent component errors
- Console logging for debugging API issues

## Files Modified

### WordPress Backend
- `functions/admin/ai-admin-enhanced.php` - Admin settings form
- `functions/helpers/settings.php` - REST API endpoint
- `functions/ai-integration.php` - AJAX and REST API handlers

### Frontend
- `jalsah-ai-frontend/src/stores/settings.js` - Settings store
- `jalsah-ai-frontend/src/components/TherapistCard.vue` - Main therapist card
- `jalsah-ai-frontend/src/views/Therapists.vue` - Therapist listing page
- `jalsah-ai-frontend/src/views/TherapistDetail.vue` - Therapist detail page
- `jalsah-ai-frontend/components/TherapistCard.vue` - Legacy therapist card

## Troubleshooting

### Common Issues

1. **Vue Error: "Property settingsStore was accessed during render but is not defined"**
   - **Solution**: Settings store is now passed as a prop to components
   - **Fix Applied**: Added null checks and made settingsStore prop optional

2. **API Error: 400 Bad Request on AJAX endpoint**
   - **Solution**: AJAX action was not registered
   - **Fix Applied**: Added `wp_ajax_get_ai_settings` action registration

3. **Settings not loading from API**
   - **Solution**: Added fallback handling and default values
   - **Fix Applied**: Settings store uses defaults when API fails

### Debug Steps
1. Check browser console for API errors
2. Verify WordPress admin settings are saved
3. Check if AJAX endpoint is accessible
4. Verify localStorage has cached settings
5. Check component props are being passed correctly

## Future Enhancements
- Add rating-specific admin settings (e.g., minimum rating threshold)
- Add rating analytics when ratings are enabled
- Consider adding rating moderation features
- Add rating export functionality for admin
