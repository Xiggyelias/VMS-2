-- Fix for applicants table - Add missing registration_date column
-- Run this in phpMyAdmin or MySQL command line if the column is missing

-- Check if column exists first
SELECT COUNT(*) as column_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'vehicleregistrationsystem' 
AND TABLE_NAME = 'applicants' 
AND COLUMN_NAME = 'registration_date';

-- Add the column if it doesn't exist
ALTER TABLE applicants 
ADD COLUMN registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
AFTER registrantType;

-- Verify the column was added
DESCRIBE applicants;

-- Optional: Update existing records with a default date
UPDATE applicants 
SET registration_date = CURRENT_TIMESTAMP 
WHERE registration_date IS NULL;
