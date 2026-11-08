-- ====================================================================
-- Migration: Patient Registration without Phone Number & Emergency Contact
-- Database: MySQL (medicore_records) & SQLite compatible
-- ====================================================================

-- 1. Make the patient's phone column nullable
ALTER TABLE patients MODIFY COLUMN phone VARCHAR(40) NULL DEFAULT NULL;

-- 2. Add boolean column no_phone (default false / 0)
ALTER TABLE patients ADD COLUMN no_phone TINYINT(1) NOT NULL DEFAULT 0;

-- 3. Add contact person columns (nullable)
ALTER TABLE patients ADD COLUMN contact_name VARCHAR(150) NULL DEFAULT NULL;
ALTER TABLE patients ADD COLUMN contact_relationship VARCHAR(80) NULL DEFAULT NULL;
ALTER TABLE patients ADD COLUMN contact_phone VARCHAR(40) NULL DEFAULT NULL;

-- 4. Data Migration: Ensure existing records have valid defaults
UPDATE patients SET no_phone = 0 WHERE no_phone IS NULL;
UPDATE patients SET phone = NULL WHERE (phone = '' OR phone = '—') AND no_phone = 1;
