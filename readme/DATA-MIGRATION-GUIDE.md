# Data Migration Guide

## Overview

This guide explains how to export your local WordPress data and import it into the online test server. This includes all demo content, settings, diagnoses, and custom data.

## Files

- `export-local-data.php` - Export tool for local WordPress
- `import-local-data.php` - Import tool for online server

## Step-by-Step Process

### Step 1: Export from Local WordPress

1. **Place the export file** in your local WordPress root directory
   ```
   /path/to/your/local/wordpress/export-local-data.php
   ```

2. **Access the export tool** in your browser
   ```
   https://jalsah.app/export-local-data.php
   ```

3. **Choose export type**:
   - **Export All Data** - Complete export (recommended)
   - **Export Users Only** - Just user accounts
   - **Export Content Only** - Posts, pages, terms
   - **Export Medical Data** - Diagnoses, appointments, therapists
   - **Export Settings Only** - WordPress options

4. **Download the JSON file** - It will be named `local-data-export-YYYY-MM-DD-HH-MM-SS.json`

### Step 2: Import to Online Server

1. **Place the import file** in your online WordPress root directory
   ```
   /path/to/your/online/wordpress/import-local-data.php
   ```

2. **Access the import tool** in your browser
   ```
   https://your-online-server.com/import-local-data.php
   ```

3. **Upload the JSON file** you exported from local

4. **Click "Import Data"** - The tool will import all data

5. **Review the results** - You'll see what was imported and what was skipped

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
- Diagnoses and medical content
- Appointments and scheduling
- Therapist profiles
- AI session data

### ✅ Custom Tables
- Jalsah appointments table
- Jalsah AI sessions table
- Cart and order data
- Payment information

### ✅ Taxonomies
- Categories and tags
- Custom taxonomies
- Term meta data
- Hierarchical relationships

## Important Notes

### 🔒 Security
- Both tools require administrator privileges
- Only run on trusted servers
- Remove the files after use

### 🔄 Duplicate Handling
- **Users**: Skipped if email already exists
- **Posts/Pages**: Skipped if title already exists
- **Terms**: Skipped if slug already exists
- **Custom Data**: Skipped if ID already exists

### ⚙️ Settings Protection
- Site URL and home URL are NOT imported
- Blog name and description are NOT imported
- This prevents breaking the online server configuration

### 🚨 After Import
1. **Clear all caches** (WordPress, plugin, server)
2. **Test functionality** thoroughly
3. **Update passwords** for imported users
4. **Verify data integrity**
5. **Remove import/export files** for security

## Troubleshooting

### Export Issues
- **Permission denied**: Make sure you're logged in as admin
- **File not found**: Check file path and WordPress loading
- **Empty export**: Verify you have data to export

### Import Issues
- **Upload failed**: Check file size and server limits
- **Invalid JSON**: Verify the export file is complete
- **Import errors**: Check server logs for details
- **Missing tables**: Ensure custom tables exist on target server

### Data Issues
- **Missing relationships**: Some IDs may need manual adjustment
- **Broken links**: Update internal links after import
- **Media files**: Upload media files separately if needed

## Example Workflow

```bash
# 1. Export from local
# Access: https://jalsah.app/export-local-data.php
# Download: local-data-export-2024-01-15-14-30-00.json

# 2. Upload to online server
# Access: https://staging.jalsah.app/import-local-data.php
# Upload: local-data-export-2024-01-15-14-30-00.json

# 3. Verify import
# Check: Users, content, settings, medical data

# 4. Test functionality
# Test: Login, appointments, AI sessions, etc.

# 5. Clean up
# Remove: export-local-data.php, import-local-data.php
```

## Security Best Practices

1. **Use HTTPS** for online server access
2. **Remove tools** after use
3. **Backup data** before importing
4. **Test on staging** before production
5. **Monitor logs** during import process

## Support

If you encounter issues:
1. Check WordPress error logs
2. Verify file permissions
3. Ensure administrator access
4. Test with smaller data sets first
5. Contact development team if needed
