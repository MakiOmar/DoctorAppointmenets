# Admin Data Migration Page

## Overview

The **Data Migration** admin page provides a secure, user-friendly interface for exporting and importing WordPress data between different environments (local, staging, production).

## Access

- **Location**: WordPress Admin → Tools → Data Migration
- **Required Permissions**: Administrator privileges
- **URL**: `/wp-admin/tools.php?page=jalsah-data-migration`

## Features

### 📤 Export Data

Export your current WordPress data to a JSON file with multiple options:

- **All Data** - Complete export (recommended)
- **Users Only** - User accounts, roles, and meta data
- **Content Only** - Posts, pages, and taxonomy terms
- **Medical Data** - Diagnoses, appointments, therapists, AI sessions
- **Settings Only** - WordPress options and configurations

### 📥 Import Data

Import data from a previously exported JSON file:

- **Safe Import** - Existing data with same titles/IDs are skipped
- **Validation** - All data is validated before import
- **Progress Tracking** - Shows import results and statistics

## What Gets Exported/Imported

### ✅ Users
- User accounts and profiles
- User roles and capabilities
- User meta data
- Registration dates

### ✅ Content
- Posts and pages
- Custom post types (diagnoses, therapists, AI sessions)
- Post meta data
- Content relationships

### ✅ Settings
- WordPress options
- Plugin settings
- Theme customizations
- Site configurations

### ✅ Medical Data
- Diagnoses and medical content (from `snks_diagnoses` table)
- Therapist-diagnosis relationships and ratings
- Appointments and scheduling
- Therapist profiles
- AI session data

### ✅ Custom Tables
- Jalsah appointments table
- Jalsah AI sessions table
- Therapist-diagnosis relationships table
- Cart and order data
- Payment information

### ✅ Taxonomies
- Categories and tags
- Custom taxonomies
- Term meta data
- Hierarchical relationships

## Security Features

### 🔒 Admin-Only Access
- Requires administrator privileges
- Secure file upload handling
- Input sanitization and validation

### 🔄 Duplicate Protection
- **Users**: Skipped if email already exists
- **Posts/Pages**: Skipped if title already exists
- **Terms**: Skipped if slug already exists
- **Custom Data**: Skipped if ID already exists

### ⚙️ Settings Protection
- Site URL and home URL are NOT imported
- Blog name and description are NOT imported
- Prevents breaking the target site configuration

## Usage Workflow

### Export from Local to Staging

1. **Access Local WordPress Admin**
   ```
   http://localhost/shrinks/wp-admin/tools.php?page=jalsah-data-migration
   ```

2. **Choose Export Type**
   - Select "All Data" for complete export
   - Click "Export Data"

3. **Download JSON File**
   - File will be named: `jalsah-data-export-all-YYYY-MM-DD-HH-MM-SS.json`

### Import to Staging Server

1. **Access Staging WordPress Admin**
   ```
   https://staging.jalsah.app/wp-admin/tools.php?page=jalsah-data-migration
   ```

2. **Upload Export File**
   - Click "Choose File"
   - Select the JSON file from local export

3. **Import Data**
   - Click "Import Data"
   - Review results and statistics

### After Import

1. **Clear All Caches**
   - WordPress cache
   - Plugin caches
   - Server caches

2. **Test Functionality**
   - User login/logout
   - Appointments and scheduling
   - AI sessions
   - All custom features

3. **Update Passwords**
   - Imported users get random passwords
   - Update passwords for important accounts

4. **Verify Data Integrity**
   - Check all imported content
   - Verify relationships and links

## File Structure

```
admin-data-migration.php          # Main admin page file
├── jalsah_ai_add_data_migration_page()     # Add menu page
├── jalsah_ai_handle_data_migration_actions() # Handle form actions
├── jalsah_ai_export_data()                 # Export function
├── jalsah_ai_import_data()                 # Import function
├── jalsah_ai_process_import()              # Process import data
├── Export functions (users, posts, etc.)
├── Import functions (users, posts, etc.)
└── jalsah_ai_data_migration_page()         # Admin page HTML
```

## Integration

The admin page is automatically loaded by the main plugin file:

```php
// In anony-shrinks.php
require_once SNKS_DIR . 'admin-data-migration.php';
```

## Benefits Over Standalone Files

### ✅ Security
- Admin-only access through WordPress
- Proper permission checks
- Secure file handling

### ✅ User Experience
- Native WordPress admin interface
- Familiar UI/UX patterns
- Clear feedback and error messages

### ✅ Integration
- Uses WordPress settings API
- Proper error handling
- Admin notices and messages

### ✅ Maintenance
- No standalone files to manage
- Automatic updates with plugin
- Better code organization

## Troubleshooting

### Export Issues
- **Permission denied**: Ensure you're logged in as administrator
- **Empty export**: Verify you have data to export
- **File not found**: Check plugin installation

### Import Issues
- **Upload failed**: Check file size and server limits
- **Invalid JSON**: Verify the export file is complete
- **Import errors**: Check server logs for details

### Data Issues
- **Missing relationships**: Some IDs may need manual adjustment
- **Broken links**: Update internal links after import
- **Media files**: Upload media files separately if needed

## Support

For issues or questions:
1. Check WordPress error logs
2. Verify administrator access
3. Test with smaller data sets first
4. Contact development team if needed

## Version History

- **v1.0.0** - Initial release with export/import functionality
- Integrated with main plugin architecture
- Added comprehensive security features
- Included detailed documentation
