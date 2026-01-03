# 🔄 Therapist Management Refactoring Plan

## 🎯 **Objective**
Consolidate therapist application management and profile settings into a single, unified Jalsah AI admin interface to eliminate data duplication and provide a consistent workflow.

---

## 📊 **Current State Analysis**

### **Problem Areas:**
1. **Dual Management Systems**
   - `therapist_app` post type (basic applications)
   - Jalsah AI admin page (enhanced profiles)
   - Data duplication and sync issues

2. **Inconsistent Workflows**
   - Applications approved in post type
   - AI settings managed separately
   - No unified approval process

3. **User Experience Issues**
   - Admins need to navigate between different interfaces
   - Confusion about where to manage what
   - Inconsistent data sources

---

## 🎯 **Target State**

### **Single Unified Interface:**
- **Jalsah AI Admin** becomes the central hub
- **List View** with approve/edit buttons
- **Integrated Workflow** from application to active profile

### **Data Flow:**
```
Application Submission → Pending Review → Approval → Profile Management → AI Settings
```

---

## 🚀 **Implementation Plan**

### **Phase 1: Enhanced Jalsah AI Admin Interface**

#### **1.1 New Therapist Applications Page**
**File**: `functions/admin/ai-admin-enhanced.php`

**Features:**
- List all therapist applications (pending + approved)
- Status indicators (Pending, Approved, Rejected)
- Bulk actions (Approve, Reject, Edit)
- Search and filter capabilities
- Application details view

**UI Components:**
```php
// New page structure
function snks_enhanced_ai_applications_page() {
    // List view with status columns
    // Approve/Reject buttons
    // Edit application functionality
    // Bulk operations
}
```

#### **1.2 Enhanced Therapist Profiles Page**
**File**: `functions/admin/ai-admin-enhanced.php`

**Features:**
- List all approved therapists
- Quick status toggles (AI visibility, active status)
- Edit profile button
- AI settings management
- Diagnosis assignments

**UI Components:**
```php
// Enhanced existing page
function snks_enhanced_ai_therapists_page() {
    // List view instead of dropdown
    // Quick action buttons
    // Status indicators
    // Edit modal/form
}
```

### **Phase 2: Data Migration & Consolidation**

#### **2.1 Application Data Structure**
**Unified Fields:**
```php
// Application/Profile Fields
$application_fields = [
    // Basic Information
    'name', 'name_en', 'email', 'phone', 'whatsapp',
    'doctor_specialty', 'profile_image', 'identity_front', 
    'identity_back', 'certificates',
    
    // AI Settings
    'show_on_ai_site', 'ai_display_name_en', 'ai_display_name_ar',
    'ai_bio_en', 'ai_bio_ar', 'public_short_bio_en', 'public_short_bio_ar',
    'secretary_phone', 'ai_first_session_percentage', 'ai_followup_session_percentage',
    
    // Status & Meta
    'application_status', 'approval_date', 'jalsah_ai_name'
];
```

#### **2.2 Database Schema Updates**
**New Table Structure:**
```sql
-- Enhanced therapist_applications table
CREATE TABLE wp_therapist_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL, -- NULL for pending, user ID for approved
    application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    name VARCHAR(255),
    name_en VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    whatsapp VARCHAR(50),
    doctor_specialty VARCHAR(255),
    profile_image INT,
    identity_front INT,
    identity_back INT,
    certificates TEXT, -- JSON array of attachment IDs
    
    -- AI Settings
    show_on_ai_site BOOLEAN DEFAULT FALSE,
    ai_display_name_en VARCHAR(255),
    ai_display_name_ar VARCHAR(255),
    ai_bio_en TEXT,
    ai_bio_ar TEXT,
    public_short_bio_en TEXT,
    public_short_bio_ar TEXT,
    secretary_phone VARCHAR(50),
    ai_first_session_percentage DECIMAL(5,2) DEFAULT 15.00,
    ai_followup_session_percentage DECIMAL(5,2) DEFAULT 10.00,
    
    -- Meta
    jalsah_ai_name VARCHAR(255),
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Phase 3: Frontend Integration**

#### **3.1 API Endpoint Updates**
**File**: `functions/ajax/therapist-details.php`

**Changes:**
- Fetch data from unified application table
- Support both pending and approved applications
- Include AI settings in response

#### **3.2 Frontend Component Updates**
**File**: `jalsah-ai-frontend/src/components/TherapistCard.vue`

**Changes:**
- Handle unified data structure
- Display appropriate information based on status
- Support for both application and profile data

### **Phase 4: Migration Scripts**

#### **4.1 Data Migration**
**Script**: `functions/admin/migration-scripts.php`

**Tasks:**
1. Migrate existing `therapist_app` posts to new table
2. Migrate user meta AI settings to application table
3. Update user relationships
4. Preserve all existing data

#### **4.2 Backward Compatibility**
**File**: `functions/helpers/admin.php`

**Tasks:**
1. Maintain existing post type for transition period
2. Sync data between old and new systems
3. Gradual deprecation of old system

---

## 📋 **Detailed Implementation Steps**

### **Step 1: Create Enhanced Admin Interface**

#### **1.1 Applications List Page**
```php
function snks_enhanced_ai_applications_page() {
    // Get applications with status
    $applications = get_posts([
        'post_type' => 'therapist_app',
        'post_status' => ['pending', 'publish'],
        'numberposts' => -1
    ]);
    
    // Display list with actions
    echo '<div class="wrap">';
    echo '<h1>Therapist Applications</h1>';
    
    // List table with approve/reject/edit buttons
    // Status indicators
    // Bulk actions
}
```

#### **1.2 Enhanced Therapists Page**
```php
function snks_enhanced_ai_therapists_page() {
    // Get approved therapists
    $therapists = get_users(['role' => 'doctor']);
    
    // Display list instead of dropdown
    // Quick action buttons
    // Status toggles
    // Edit functionality
}
```

### **Step 2: Update Data Flow**

#### **2.1 Approval Process**
```php
function snks_approve_therapist_application($application_id) {
    // 1. Get application data
    // 2. Create/update user
    // 3. Set user meta from application
    // 4. Update application status
    // 5. Send notification
    // 6. Redirect to profile management
}
```

#### **2.2 Profile Management**
```php
function snks_update_therapist_profile($user_id, $data) {
    // 1. Update user meta
    // 2. Update application record
    // 3. Handle AI settings
    // 4. Update diagnosis assignments
}
```

### **Step 3: API Integration**

#### **3.1 Unified Data Endpoint**
```php
function snks_get_therapist_details_rest($request) {
    $therapist_id = intval($request['id']);
    
    // Get from application table first
    $application = get_application_by_user_id($therapist_id);
    
    if ($application) {
        return [
            'success' => true,
            'data' => [
                'id' => $therapist_id,
                'name' => $application->name,
                'name_en' => $application->name_en,
                'email' => $application->email,
                'phone' => $application->phone,
                'whatsapp' => $application->whatsapp,
                'specialty' => $application->doctor_specialty,
                'certificates' => json_decode($application->certificates, true),
                'ai_settings' => [
                    'show_on_ai_site' => $application->show_on_ai_site,
                    'ai_display_name_en' => $application->ai_display_name_en,
                    'ai_display_name_ar' => $application->ai_display_name_ar,
                    'ai_bio_en' => $application->ai_bio_en,
                    'ai_bio_ar' => $application->ai_bio_ar,
                    'public_short_bio_en' => $application->public_short_bio_en,
                    'public_short_bio_ar' => $application->public_short_bio_ar,
                    'secretary_phone' => $application->secretary_phone,
                    'ai_first_session_percentage' => $application->ai_first_session_percentage,
                    'ai_followup_session_percentage' => $application->ai_followup_session_percentage,
                ]
            ]
        ];
    }
    
    return new WP_Error('not_found', 'Therapist not found');
}
```

---

## 🔄 **Migration Strategy**

### **Phase 1: Parallel Systems (Week 1-2)**
- Implement new interface alongside existing
- Data sync between old and new systems
- Testing and validation

### **Phase 2: Gradual Migration (Week 3-4)**
- Move new applications to new system
- Migrate existing data
- Update workflows

### **Phase 3: Full Migration (Week 5)**
- Deprecate old post type
- Remove old admin pages
- Clean up legacy code

---

## ✅ **Benefits of This Approach**

### **1. Unified Management**
- Single interface for all therapist operations
- Consistent data structure
- Streamlined workflows

### **2. Better User Experience**
- Clear status indicators
- Quick action buttons
- Intuitive navigation

### **3. Data Integrity**
- Single source of truth
- No data duplication
- Consistent validation

### **4. Scalability**
- Easy to add new fields
- Flexible status management
- Better performance

---

## 🚨 **Risk Mitigation**

### **1. Data Loss Prevention**
- Comprehensive backup before migration
- Rollback procedures
- Data validation scripts

### **2. Backward Compatibility**
- Maintain existing APIs during transition
- Gradual deprecation
- Clear migration timeline

### **3. Testing Strategy**
- Unit tests for new functions
- Integration tests for workflows
- User acceptance testing

---

## 📅 **Timeline Estimate**

- **Week 1**: Enhanced admin interface development
- **Week 2**: Data migration scripts and testing
- **Week 3**: API updates and frontend integration
- **Week 4**: Migration execution and validation
- **Week 5**: Cleanup and optimization

**Total Estimated Time**: 5 weeks
**Complexity**: High (requires careful planning and testing)
**Impact**: High (improves admin experience significantly) 