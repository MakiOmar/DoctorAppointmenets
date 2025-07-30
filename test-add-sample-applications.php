<?php
/**
 * Test Script: Add Sample Therapist Applications
 * Run this in your WordPress environment to test the system
 */

// Include WordPress
require_once('../../../wp-load.php');

// Check if table exists
global $wpdb;
$table_name = $wpdb->prefix . 'therapist_applications';

// Create table if it doesn't exist
$charset_collate = $wpdb->get_charset_collate();
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) DEFAULT NULL,
    name varchar(255) NOT NULL,
    name_en varchar(255) DEFAULT '',
    email varchar(255) NOT NULL,
    phone varchar(50) NOT NULL,
    whatsapp varchar(50) DEFAULT '',
    doctor_specialty varchar(255) DEFAULT '',
    experience_years int(11) DEFAULT 0,
    education text,
    bio text,
    bio_en text,
    profile_image bigint(20) DEFAULT NULL,
    identity_front bigint(20) DEFAULT NULL,
    identity_back bigint(20) DEFAULT NULL,
    certificates longtext,
    status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY status (status),
    KEY email (email)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

// Sample applications data
$sample_applications = [
    [
        'name' => 'د. أحمد محمد علي',
        'name_en' => 'Dr. Ahmed Mohamed Ali',
        'email' => 'ahmed.ali@test.com',
        'phone' => '+201234567890',
        'whatsapp' => '+201234567890',
        'doctor_specialty' => 'طب نفسي',
        'experience_years' => 8,
        'education' => 'دكتوراه في الطب النفسي - جامعة القاهرة',
        'bio' => 'أخصائي نفسي معتمد مع خبرة 8 سنوات في علاج الاكتئاب والقلق',
        'bio_en' => 'Certified psychologist with 8 years experience in treating depression and anxiety',
        'status' => 'pending'
    ],
    [
        'name' => 'د. فاطمة أحمد حسن',
        'name_en' => 'Dr. Fatima Ahmed Hassan',
        'email' => 'fatima.hassan@test.com',
        'phone' => '+201234567891',
        'whatsapp' => '+201234567891',
        'doctor_specialty' => 'استشارات أسرية',
        'experience_years' => 5,
        'education' => 'ماجستير في الإرشاد الأسري - جامعة عين شمس',
        'bio' => 'مستشارة أسرية متخصصة في حل المشاكل الزوجية والأسرية',
        'bio_en' => 'Family counselor specialized in solving marital and family problems',
        'status' => 'pending'
    ],
    [
        'name' => 'د. محمد سعيد عبدالله',
        'name_en' => 'Dr. Mohamed Saeed Abdullah',
        'email' => 'mohamed.abdullah@test.com',
        'phone' => '+201234567892',
        'whatsapp' => '+201234567892',
        'doctor_specialty' => 'علاج سلوكي معرفي',
        'experience_years' => 12,
        'education' => 'دكتوراه في علم النفس الإكلينيكي - جامعة الإسكندرية',
        'bio' => 'أخصائي في العلاج السلوكي المعرفي مع خبرة 12 سنة',
        'bio_en' => 'Specialist in cognitive behavioral therapy with 12 years experience',
        'status' => 'approved'
    ]
];

// Insert sample applications
$inserted = 0;
foreach ($sample_applications as $app) {
    $result = $wpdb->insert(
        $table_name,
        $app,
        [
            '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ]
    );
    
    if ($result !== false) {
        $inserted++;
        echo "✅ Added: " . $app['name'] . " (" . $app['status'] . ")\n";
    } else {
        echo "❌ Failed to add: " . $app['name'] . "\n";
    }
}

echo "\n🎉 Successfully added $inserted sample applications!\n";
echo "\n📋 Next steps:\n";
echo "1. Go to WordPress Admin → Jalsah AI → Applications & Profiles\n";
echo "2. You should see the sample applications listed\n";
echo "3. Test the different status tabs (All, Pending, Active Profiles)\n";
echo "4. Try the search functionality\n";
echo "5. Test approving a pending application\n";
echo "6. Test viewing and editing application details\n";
?> 