# Doctor Appointments Plugin - AI Profit Transfer System

## 🎯 Overview

The AI Profit Transfer System is a comprehensive solution for managing therapist earnings from AI sessions. It provides automated profit calculation, transaction processing, and withdrawal management with full Arabic/English internationalization support.

## 🚀 Features

### Core Functionality
- **Automated Profit Calculation**: First session (70%) and subsequent sessions (75%) profit distribution
- **Transaction Processing**: Real-time processing of AI session transactions
- **Withdrawal Management**: Support for wallet, bank transfer, and Meza payment methods
- **Earnings Tracking**: Comprehensive reporting and analytics for therapist earnings
- **Admin Interface**: User-friendly WordPress admin interface with tabbed navigation

### Technical Features
- **Database Integration**: Seamless integration with existing appointment system
- **Internationalization**: Complete Arabic/English translation support
- **Security**: Proper capability checks and nonce verification
- **Responsive Design**: Mobile-friendly admin interface
- **Error Handling**: Comprehensive error handling and user feedback

## 📋 System Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- Administrator access for setup

## 🛠️ Installation & Setup

### 1. Database Setup
The system automatically creates required database tables on plugin activation:

```sql
-- AI Profit Settings Table
wpds_snks_ai_profit_settings
- id (Primary Key)
- therapist_id (Foreign Key to wp_users)
- first_session_percentage (Default: 70.00)
- subsequent_session_percentage (Default: 75.00)
- is_active (Boolean)
- created_at, updated_at (Timestamps)

-- Enhanced Sessions Actions Table
wpds_snks_sessions_actions
- ai_session_type (first/subsequent)
- therapist_id (Foreign Key)
- patient_id (Foreign Key)

-- Enhanced Booking Transactions Table
wpds_snks_booking_transactions
- ai_session_id (Foreign Key)
- ai_session_type
- ai_patient_id
- ai_order_id
```

### 2. Admin Menu Structure
```
Jalsah AI Management
├── Dashboard
├── Profit Settings
├── Therapist Earnings
└── Transaction Processing
```

### 3. Internationalization Setup
- Text Domain: `anony-turn`
- Translation Files: `languages/anony-turn-ar.po`
- Automatic loading on plugin initialization

## 🧪 Testing

### Run Complete System Test
Access the comprehensive test script to validate all functionality:

```
http://your-site.com/wp-content/plugins/DoctorAppointmenets/test-ai-profit-system-complete.php
```

### Test Coverage
1. **Database Schema Validation**
   - Table existence verification
   - Column structure validation
   - Foreign key constraint checks

2. **Function Availability**
   - Core function existence
   - Admin page accessibility
   - CSS styling verification

3. **Internationalization**
   - Text domain loading
   - Translation file validation
   - Sample translation testing

4. **Security**
   - Capability checks
   - Nonce verification
   - Access control validation

5. **Data Validation**
   - Sample data presence
   - Transaction processing
   - Earnings calculation

## 📊 Usage Guide

### For Administrators

#### 1. Profit Settings Management
- Navigate to: **Jalsah AI Management → Profit Settings**
- Configure default profit percentages (70% first session, 75% subsequent)
- Set individual therapist profit settings
- View quick statistics and system overview

#### 2. Therapist Earnings Monitoring
- Navigate to: **Jalsah AI Management → Therapist Earnings**
- Filter earnings by date range and therapist
- View detailed transaction history
- Export earnings data to CSV
- Monitor profit distribution

#### 3. Transaction Processing
- Navigate to: **Jalsah AI Management → Transaction Processing**
- Process pending AI session transactions
- Manage therapist withdrawal requests
- View recent transaction history
- Handle payment method selection

### For Therapists
- Earnings automatically calculated and tracked
- Withdrawal requests processed through admin
- Transaction history available in admin interface

## 🔧 Configuration

### Default Settings
```php
// Default profit percentages
$default_first_session_percentage = 70.00;
$default_subsequent_session_percentage = 75.00;

// Currency display
$currency_symbol = 'ج.م'; // EGP
```

### Customization Options
- Modify profit percentages per therapist
- Add new payment methods
- Customize admin interface styling
- Extend translation support

## 🌐 Internationalization

### Supported Languages
- **English**: Default language
- **Arabic**: Complete translation support

### Translation Files
- Source: `languages/anony-turn-ar.po`
- Compiled: `languages/anony-turn-ar.mo` (auto-generated)

### Adding New Languages
1. Create new `.po` file in `languages/` directory
2. Translate all strings using gettext format
3. Compile to `.mo` file
4. Update text domain loading in main plugin file

## 🔒 Security Features

### Access Control
- Administrator capability required (`manage_options`)
- Nonce verification for all forms
- Input sanitization and validation
- SQL injection prevention

### Data Protection
- Encrypted sensitive data storage
- Secure transaction processing
- Audit trail for all operations

## 📈 Performance Optimization

### Database Optimization
- Indexed foreign key relationships
- Optimized queries for large datasets
- Efficient pagination implementation

### Caching Strategy
- WordPress transients for statistics
- Object caching for frequently accessed data
- CSS/JS minification for admin interface

## 🐛 Troubleshooting

### Common Issues

#### 1. Database Tables Missing
**Symptoms**: Admin pages show errors or missing data
**Solution**: Deactivate and reactivate the plugin to trigger database creation

#### 2. Translation Not Working
**Symptoms**: Arabic text not displaying
**Solution**: 
- Verify `.po` file exists in `languages/` directory
- Check text domain loading in main plugin file
- Clear WordPress cache

#### 3. Admin Pages Not Loading
**Symptoms**: 404 errors on admin pages
**Solution**:
- Verify plugin activation
- Check WordPress permalink settings
- Clear browser cache

#### 4. CSS Styling Issues
**Symptoms**: Admin interface not styled properly
**Solution**:
- Verify `snks_load_ai_admin_styles()` function exists
- Check for CSS conflicts with other plugins
- Clear WordPress cache

### Debug Mode
Enable WordPress debug mode for detailed error information:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 API Reference

### Core Functions

#### Database Functions
```php
snks_create_ai_tables()                    // Create database tables
snks_get_profit_settings_statistics()      // Get system statistics
snks_get_therapist_earnings($therapist_id, $start_date, $end_date)  // Get earnings
```

#### Transaction Functions
```php
snks_process_ai_session_transaction($session_id)     // Process session
snks_process_therapist_withdrawal($therapist_id, $amount, $method)  // Process withdrawal
snks_get_recent_ai_transactions($limit)              // Get recent transactions
```

#### Admin Functions
```php
snks_load_ai_admin_styles()               // Load admin CSS
snks_profit_settings_page()               // Render profit settings page
snks_therapist_earnings_page()            // Render earnings page
snks_ai_transaction_processing_page()     // Render transaction page
```

### Hooks and Filters
```php
// Actions
do_action('snks_ai_session_processed', $session_id, $profit_data);
do_action('snks_withdrawal_processed', $therapist_id, $amount, $method);

// Filters
apply_filters('snks_profit_percentage', $percentage, $session_type, $therapist_id);
apply_filters('snks_currency_symbol', 'ج.م');
```

## 🚀 Deployment Checklist

### Pre-Deployment
- [ ] Run complete system test
- [ ] Verify database schema
- [ ] Test all admin pages
- [ ] Validate translations
- [ ] Check security measures
- [ ] Test transaction processing
- [ ] Verify earnings calculations

### Production Deployment
- [ ] Backup existing database
- [ ] Deploy plugin files
- [ ] Activate plugin
- [ ] Verify database tables created
- [ ] Test admin interface
- [ ] Configure initial settings
- [ ] Monitor error logs

### Post-Deployment
- [ ] Monitor system performance
- [ ] Track transaction processing
- [ ] Verify earnings calculations
- [ ] Check user feedback
- [ ] Update documentation as needed

## 📞 Support

### Documentation
- This README file
- Inline code comments
- WordPress admin help tabs

### Testing
- Comprehensive test script included
- Manual testing procedures documented
- Error handling guidelines

### Maintenance
- Regular database optimization
- Translation file updates
- Security patch monitoring

## 📄 License

This plugin is part of the Doctor Appointments system and follows the same licensing terms.

---

**Version**: 1.0.0  
**Last Updated**: August 2025  
**Compatibility**: WordPress 5.0+, PHP 7.4+  
**Status**: Production Ready ✅
