-- ==============================================================================
-- DATABASE MIGRATION: Add address_history table and bills.patient_address
-- Generated: 2026-11-10
-- Backup created at: database/backups/mysql_backup_before_address_history_20261110.sql
--                    database/backups/medicore_backup_before_address_history_20261110.sqlite
-- ==============================================================================

-- 1. Create address_history table
CREATE TABLE IF NOT EXISTS address_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_type VARCHAR(20) NOT NULL, -- 'employee' or 'patient'
    owner_id VARCHAR(40) NOT NULL,
    old_country VARCHAR(100) DEFAULT '',
    old_province VARCHAR(100) DEFAULT '',
    old_city VARCHAR(100) DEFAULT '',
    old_barangay VARCHAR(100) DEFAULT '',
    old_street VARCHAR(255) DEFAULT '',
    old_postal_code VARCHAR(30) DEFAULT '',
    old_address VARCHAR(255) DEFAULT '',
    new_country VARCHAR(100) DEFAULT '',
    new_province VARCHAR(100) DEFAULT '',
    new_city VARCHAR(100) DEFAULT '',
    new_barangay VARCHAR(100) DEFAULT '',
    new_street VARCHAR(255) DEFAULT '',
    new_postal_code VARCHAR(30) DEFAULT '',
    new_address VARCHAR(255) DEFAULT '',
    changed_by_user_id VARCHAR(40) NOT NULL,
    active_role VARCHAR(30) NOT NULL,
    reason TEXT DEFAULT NULL,
    changed_at VARCHAR(40) NOT NULL
);

-- 2. Add address snapshot column to bills
ALTER TABLE bills ADD COLUMN patient_address VARCHAR(255) DEFAULT NULL;

-- 3. Snapshot address for existing bills from matching patient records
UPDATE bills b
JOIN patients p ON (UPPER(p.full_name) = UPPER(b.patient_name))
SET b.patient_address = p.address
WHERE b.patient_address IS NULL OR b.patient_address = '';
