-- Update Database Schema for Africa University Vehicle Registration System
-- Run this in phpMyAdmin or MySQL command line

USE vehicleregistrationsystem;

-- 1. Add missing fields to applicants table
ALTER TABLE applicants 
ADD COLUMN dateOfBirth DATE NULL AFTER fullName,
ADD COLUMN vehicleRegistrationNumber VARCHAR(50) NULL AFTER licenseDate,
ADD COLUMN vehicleMake VARCHAR(100) NULL AFTER vehicleRegistrationNumber,
ADD COLUMN vehicleModel VARCHAR(100) NULL AFTER vehicleMake,
ADD COLUMN registeredOwner VARCHAR(100) NULL AFTER vehicleModel,
ADD COLUMN vehicleAddress TEXT NULL AFTER registeredOwner,
ADD COLUMN plateNumber VARCHAR(20) NULL AFTER vehicleAddress,
ADD COLUMN applicationStatus ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft' AFTER plateNumber,
ADD COLUMN lastSavedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER applicationStatus;

-- 2. Create authorized_drivers table
CREATE TABLE IF NOT EXISTS authorized_drivers (
    driver_id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    fullName VARCHAR(100) NOT NULL,
    licenseNumber VARCHAR(50) NOT NULL,
    contactInfo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(applicant_id) ON DELETE CASCADE
);

-- 3. Create colleges table for dropdown
CREATE TABLE IF NOT EXISTS colleges (
    college_id INT AUTO_INCREMENT PRIMARY KEY,
    college_name VARCHAR(100) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE
);

-- 4. Insert default colleges
INSERT IGNORE INTO colleges (college_name) VALUES
('College of Engineering and Applied Sciences'),
('College of Business and Management Sciences'),
('College of Social Sciences, Theology, Humanities and Education'),
('College of Health Sciences'),
('College of Agriculture and Natural Resources');

-- 5. Create license_classes table for dropdown
CREATE TABLE IF NOT EXISTS license_classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_code VARCHAR(10) NOT NULL UNIQUE,
    class_description VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- 6. Insert default license classes
INSERT IGNORE INTO license_classes (class_code, class_description) VALUES
('A', 'Motorcycle'),
('B', 'Light Motor Vehicle'),
('C', 'Heavy Motor Vehicle'),
('D', 'Heavy Combination Vehicle'),
('E', 'Heavy Rigid Vehicle');

-- 7. Update existing records to have applicationStatus
UPDATE applicants SET applicationStatus = 'draft' WHERE applicationStatus IS NULL;

-- 8. Verify the structure
DESCRIBE applicants;
DESCRIBE authorized_drivers;
DESCRIBE colleges;
DESCRIBE license_classes;

-- 9. Show sample data
SELECT 'Applicants Table' as table_name, COUNT(*) as record_count FROM applicants
UNION ALL
SELECT 'Authorized Drivers' as table_name, COUNT(*) as record_count FROM authorized_drivers
UNION ALL
SELECT 'Colleges' as table_name, COUNT(*) as record_count FROM colleges
UNION ALL
SELECT 'License Classes' as table_name, COUNT(*) as record_count FROM license_classes;


