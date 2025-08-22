# 💰 Profit Transfer System Implementation Plan

## 🎯 **Overview**
This document outlines the implementation plan for the Profit Transfer System for Jalsah AI sessions. The system will handle profit sharing between the platform and therapists, ensuring proper financial tracking and transparent earnings for AI session bookings.

---

## 📊 **Current System Analysis**

### **Existing Infrastructure:**
- ✅ WooCommerce orders for AI sessions
- ✅ Session completion tracking via `snks_sessions_actions` table
- ✅ AI session identification via `from_jalsah_ai` meta flag
- ✅ Therapist-user relationships in WordPress
- ✅ Session attendance confirmation system

### **Missing Components:**
- ❌ Profit percentage configuration per therapist
- ❌ Session completion profit calculation triggers
- ❌ Transaction logging for AI sessions
- ❌ Therapist earnings dashboard
- ❌ Profit transfer execution system

---

## 🏗️ **System Architecture**

### **1. Database Schema**

#### **New Table: `snks_ai_profit_settings`**
```sql
CREATE TABLE snks_ai_profit_settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    therapist_id INT(11) NOT NULL,
    first_session_percentage DECIMAL(5,2) DEFAULT 70.00,
    subsequent_session_percentage DECIMAL(5,2) DEFAULT 75.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_therapist (therapist_id),
    FOREIGN KEY (therapist_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

#### **Enhanced Table: `snks_sessions_actions`**
```sql
-- Add only the session type column to track first vs subsequent sessions
ALTER TABLE snks_sessions_actions 
ADD COLUMN ai_session_type ENUM('first', 'subsequent') DEFAULT 'first';
```

**Note:** We don't need to add profit tracking columns because:
- **Profit amount** → Stored in `snks_booking_transactions.amount`
- **Transfer date** → Stored in `snks_booking_transactions.transaction_time`
- **Transfer status** → Tracked via `snks_booking_transactions.processed_for_withdrawal`

**AI Sessions Don't Need Attendance Setting:**
- AI sessions are **automatically completed** when session ends
- **No manual attendance confirmation** required
- **Direct profit calculation** and transfer on session completion

#### **Integration with Existing `snks_booking_transactions` Table**
The system will **integrate with the existing transaction system** instead of creating a separate table:

**Existing Table Structure:**
```sql
-- Existing snks_booking_transactions table (already exists)
-- Columns: id, user_id, timetable_id, transaction_type, amount, transaction_time, processed_for_withdrawal
```

**AI Session Integration:**
- **Use existing `snks_booking_transactions` table** for AI session profit transfers
- **Add AI session metadata** to distinguish from regular sessions
- **Leverage existing withdrawal system** for AI session earnings

### **2. File Structure**

#### **New Files to Create:**
```
functions/
├── admin/
│   ├── profit-settings.php          # Admin profit settings page
│   └── therapist-earnings.php       # Therapist earnings dashboard
├── helpers/
│   └── profit-calculator.php        # Profit calculation logic
└── actions/
    └── profit-transfer.php          # Profit transfer execution
```

#### **Files to Modify:**
```
functions/
├── admin/
│   └── ai-admin-enhanced.php        # Add profit settings menu
├── ai-integration.php               # Add profit calculation hooks
└── actions/
    └── consulting-checkout.php      # Add profit tracking on order creation
```

---

## 🔧 **Implementation Plan**

### **Phase 1: Database & Core Infrastructure**

#### **1.1 Database Setup**
- Create `snks_ai_profit_settings` table
- Enhance `snks_sessions_actions` table (add only `ai_session_type` column)
- Add default profit settings for existing therapists

#### **1.2 Core Helper Functions**
**File**: `functions/helpers/profit-calculator.php`

**Functions to implement:**
```php
// Get therapist profit settings
function snks_get_therapist_profit_settings($therapist_id)

// Calculate profit for a session
function snks_calculate_session_profit($session_amount, $therapist_id, $patient_id)

// Determine if session is first or subsequent
function snks_is_first_session($therapist_id, $patient_id)

// Add AI session transaction to existing system
function snks_add_ai_session_transaction($therapist_id, $session_data, $profit_amount)

// Execute profit transfer using existing system
function snks_execute_ai_profit_transfer($session_id)
```

### **Phase 2: Admin Interface**

#### **2.1 Profit Settings Page**
**File**: `functions/admin/profit-settings.php`

**Features:**
- Global default profit percentages
- Individual therapist profit settings
- Bulk update functionality
- Profit settings import/export

**UI Components:**
```php
function snks_profit_settings_page() {
    // Global settings section
    // Individual therapist settings table
    // Bulk operations
    // Settings validation
}
```

#### **2.2 Therapist Earnings Dashboard**
**File**: `functions/admin/therapist-earnings.php`

**Features:**
- Earnings overview per therapist
- Transaction history
- Profit statistics
- Export functionality

**UI Components:**
```php
function snks_therapist_earnings_page() {
    // Earnings summary
    // Transaction list
    // Filtering options
    // Export buttons
}
```

### **Phase 3: Integration & Triggers**

#### **3.1 Session Completion Triggers**
**Integration Points:**
- Session end via Jitsi (automatic completion)
- Manual session completion by admin (if needed)

**Hook Implementation:**
```php
// Hook into session completion
add_action('snks_session_completed', 'snks_trigger_ai_profit_calculation', 10, 2);

function snks_trigger_ai_profit_calculation($session_id, $session_data) {
    // Check if it's an AI session
    if (snks_is_ai_session($session_id)) {
        // Calculate profit automatically
        $profit_amount = snks_calculate_ai_session_profit($session_data);
        
        // Add transaction to existing system
        snks_add_ai_session_transaction($session_data['therapist_id'], $session_data, $profit_amount);
        
        // Update session actions table
        snks_update_ai_session_profit_status($session_id, $profit_amount);
    }
}
```

#### **3.2 Integration with Existing Transaction System**
**File**: `functions/helpers/profit-calculator.php`

**Integration Points:**
- **Use existing `snks_add_transaction()` function** for AI sessions
- **Leverage existing withdrawal system** for AI session earnings
- **Add AI session metadata** to distinguish from regular sessions
- **Use existing `processed_for_withdrawal` logic** for AI sessions

### **Phase 4: Transaction Processing**

#### **4.1 Profit Transfer Execution**
**File**: `functions/helpers/profit-calculator.php`

**Process Flow:**
1. AI session completion detected (automatic)
2. Profit calculation triggered (automatic)
3. **Add transaction to existing `snks_booking_transactions` table**
4. **Use existing withdrawal system** for processing
5. **Leverage existing balance management** (temp_wallet/wallet)
6. **Use existing transaction logging** system

#### **4.2 Integration with Existing Systems**
- **Use existing `snks_add_transaction()` function** with AI session metadata
- **Leverage existing withdrawal processing** (daily/weekly/monthly)
- **Use existing balance calculation** (`get_available_balance()`)
- **Integrate with existing withdrawal methods** (bank, meza, wallet)

---

## 📋 **Data Flow**

### **1. Session Booking Flow**
```
Patient Books Session → WooCommerce Order Created → Session Metadata Stored → Profit Calculation Prepared
```

### **2. Session Completion Flow**
```
AI Session Completed → Profit Calculated → Add to snks_booking_transactions → Use Existing Withdrawal System → Balance Updated
```

### **3. Profit Calculation Logic**
```
Session Amount × Profit Percentage = Profit Amount
First Session: 70% (default)
Subsequent Sessions: 75% (default)
Individual therapist settings override defaults
```

### **4. Integration with Existing Transaction System**
```
AI Session → snks_add_transaction() → snks_booking_transactions → Existing Withdrawal Processing → Therapist Balance
```

---

## 🎛️ **Admin Configuration**

### **1. Global Settings**
- Default first session percentage
- Default subsequent session percentage
- Transfer delay settings
- Notification preferences

### **2. Individual Therapist Settings**
- Custom profit percentages per therapist
- Active/inactive status
- Special arrangements
- Bulk import/export

### **3. Transaction Management**
- View all transactions
- Filter by date, therapist, status
- Manual transfer execution
- Transaction history export

---

## 🔍 **Monitoring & Reporting**

### **1. Transaction Logs**
- All profit transfers logged
- Success/failure tracking
- Error details and retry attempts
- Audit trail for compliance

### **2. Earnings Reports**
- Daily/weekly/monthly summaries
- Per-therapist earnings
- Platform revenue tracking
- Profit margin analysis

### **3. Dashboard Analytics**
- Real-time earnings display
- Transaction status overview
- Performance metrics
- Alert system for issues

---

## 🚀 **Implementation Steps**

### **Step 1: Database Setup**
1. Create profit settings table
2. Enhance sessions actions table (add ai_session_type column)
3. Add default profit settings for existing therapists

### **Step 2: Core Functions**
1. Implement profit calculation logic
2. Create transaction management functions
3. Add helper utilities
4. Set up hooks and triggers

### **Step 3: Admin Interface**
1. Create profit settings page
2. Build earnings dashboard
3. Add menu integration
4. Implement bulk operations

### **Step 4: Integration**
1. Hook into session completion
2. Integrate with order creation
3. Add profit tracking
4. Test end-to-end flow

### **Step 5: Testing & Deployment**
1. Unit testing
2. Integration testing
3. User acceptance testing
4. Production deployment

---

## 🔒 **Security & Compliance**

### **1. Data Protection**
- Encrypt sensitive financial data
- Secure transaction logging
- Access control for admin functions
- Audit trail maintenance

### **2. Financial Compliance**
- Accurate profit calculations
- Transparent transaction records
- Tax reporting support
- Regulatory compliance

### **3. Error Handling**
- Graceful failure handling
- Retry mechanisms
- Error notifications
- Data integrity checks

---

## 📊 **Expected Outcomes**

### **1. For Therapists**
- **Seamless integration** with existing earnings system
- **Same withdrawal process** as regular sessions
- **Unified transaction history** in existing interface
- **Existing withdrawal methods** (bank, meza, wallet)

### **2. For Platform**
- **No duplicate systems** - leverage existing infrastructure
- **Consistent financial tracking** across all session types
- **Unified withdrawal processing** for all earnings
- **Reduced maintenance** - single transaction system

### **3. For Administrators**
- **Familiar interface** - same transaction management
- **Unified reporting** - AI and regular sessions together
- **Existing withdrawal tools** work for AI sessions
- **Consistent user experience** across all features

---

## 🎯 **Success Metrics**

### **1. Technical Metrics**
- 99.9% transaction success rate
- < 5 second profit calculation time
- Zero data loss incidents
- 100% audit trail accuracy

### **2. Business Metrics**
- Reduced manual processing time
- Increased therapist satisfaction
- Improved financial transparency
- Enhanced platform reliability

---

## 📝 **Next Steps**

1. **Review and approve** this integration approach
2. **Begin Phase 1** - Create profit settings table and enhance sessions_actions
3. **Implement core functions** - Profit calculation and existing system integration
4. **Build admin interface** - Profit settings configuration page
5. **Integrate with existing transaction system** - Use snks_add_transaction() for AI sessions
6. **Test end-to-end flow** - From session completion to withdrawal processing
7. **Deploy with existing withdrawal system** - No separate processing needed

## 🔄 **Key Integration Points**

### **Existing Functions to Leverage:**
- `snks_add_transaction()` - Add AI session transactions
- `get_available_balance()` - Calculate AI session earnings
- `process_user_withdrawal()` - Process AI session withdrawals
- `snks_log_transaction()` - Log AI session transactions

### **Existing Tables to Use:**
- `snks_booking_transactions` - Store AI session transactions
- `snks_sessions_actions` - Track AI session profit status
- `wp_users` - Therapist profit settings

### **Existing Admin Pages:**
- Withdrawal Transactions page - Will show AI session transactions
- User transactions shortcode - Will display AI session earnings

---

*This document will be updated as implementation progresses and requirements evolve.*
