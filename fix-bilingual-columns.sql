-- Fix Bilingual Columns for snks_diagnoses Table
-- Run this script on the staging database to add missing columns

-- Add bilingual columns to snks_diagnoses table
ALTER TABLE `wpds_snks_diagnoses` 
ADD COLUMN `name_en` VARCHAR(255) AFTER `name`,
ADD COLUMN `name_ar` VARCHAR(255) AFTER `name_en`,
ADD COLUMN `description_en` TEXT AFTER `description`,
ADD COLUMN `description_ar` TEXT AFTER `description_en`;

-- Migrate existing data to English columns
UPDATE `wpds_snks_diagnoses` SET `name_en` = `name` WHERE `name_en` IS NULL OR `name_en` = '';
UPDATE `wpds_snks_diagnoses` SET `description_en` = `description` WHERE `description_en` IS NULL OR `description_en` = '';

-- Add bilingual columns to snks_therapist_diagnoses table (if it exists)
ALTER TABLE `wpds_snks_therapist_diagnoses` 
ADD COLUMN `suitability_message_en` TEXT AFTER `suitability_message`,
ADD COLUMN `suitability_message_ar` TEXT AFTER `suitability_message_en`;

-- Migrate existing suitability messages to English column
UPDATE `wpds_snks_therapist_diagnoses` SET `suitability_message_en` = `suitability_message` WHERE `suitability_message_en` IS NULL OR `suitability_message_en` = '';

-- Verify the changes
SELECT 'snks_diagnoses columns:' as table_name, COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'wpds_snks_diagnoses' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

SELECT 'snks_therapist_diagnoses columns:' as table_name, COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'wpds_snks_therapist_diagnoses' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;
