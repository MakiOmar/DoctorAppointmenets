<?php
/**
 * Script to add missing prescription columns to snks_rochtah_bookings table
 * Run this once to update your database schema
 */

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

$table_name = $wpdb->prefix . 'snks_rochtah_bookings';

echo "Adding missing prescription columns to $table_name...\n";

// Check if columns exist first
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
$existing_columns = array();
foreach ($columns as $column) {
    $existing_columns[] = $column->Field;
}

// Add missing columns
$columns_to_add = array(
    'reason_for_referral' => "ADD COLUMN reason_for_referral TEXT AFTER symptoms",
    'prescription_text' => "ADD COLUMN prescription_text TEXT AFTER status",
    'medications' => "ADD COLUMN medications TEXT AFTER prescription_text",
    'dosage_instructions' => "ADD COLUMN dosage_instructions TEXT AFTER medications",
    'doctor_notes' => "ADD COLUMN doctor_notes TEXT AFTER dosage_instructions",
    'prescribed_by' => "ADD COLUMN prescribed_by BIGINT(20) NULL AFTER doctor_notes",
    'prescribed_at' => "ADD COLUMN prescribed_at DATETIME NULL AFTER prescribed_by",
    'prescription_file' => "ADD COLUMN prescription_file VARCHAR(255) AFTER prescribed_at"
);

foreach ($columns_to_add as $column_name => $sql_fragment) {
    if (!in_array($column_name, $existing_columns)) {
        $sql = "ALTER TABLE $table_name $sql_fragment";
        $result = $wpdb->query($sql);
        
        if ($result !== false) {
            echo "✅ Added column: $column_name\n";
        } else {
            echo "❌ Failed to add column: $column_name\n";
            echo "SQL: $sql\n";
            echo "Error: " . $wpdb->last_error . "\n";
        }
    } else {
        echo "⚠️  Column already exists: $column_name\n";
    }
}

// Verify the table structure
echo "\n=== Current Table Structure ===\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
foreach ($columns as $column) {
    echo sprintf("%-20s %-15s %-5s %-5s %-10s %s\n", 
        $column->Field, 
        $column->Type, 
        $column->Null, 
        $column->Key, 
        $column->Default, 
        $column->Extra
    );
}

echo "\n✅ Database update completed!\n";
echo "You can now delete this script: add-prescription-columns.php\n";
?>
