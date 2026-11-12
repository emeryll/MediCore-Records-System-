-- ==============================================================================
-- DATABASE MIGRATION: Convert all existing MRNs to fixed-width 6-digit format (MRN-XXXXXX)
-- Generated: 2026-11-08
-- Backup created at: database/backups/mysql_backup_before_mrn_migration_20261108_163351.sql
--                    database/backups/medicore_backup_before_mrn_migration_20261108_163351.sqlite
-- ==============================================================================

-- 1. Update patients table
UPDATE patients 
SET mrn = CONCAT('MRN-', LPAD(REGEXP_REPLACE(mrn, '[^0-9]', ''), 6, '0')),
    patient_id = CONCAT('MRN-', LPAD(REGEXP_REPLACE(patient_id, '[^0-9]', ''), 6, '0'))
WHERE (mrn LIKE 'MRN-%' OR mrn LIKE 'P-%' OR patient_id LIKE 'MRN-%' OR patient_id LIKE 'P-%')
  AND NOT (patient_id LIKE 'PROV-%' OR mrn LIKE 'PROV-%');

-- 2. Update patient_account_links table
UPDATE patient_account_links
SET mrn = CONCAT('MRN-', LPAD(REGEXP_REPLACE(mrn, '[^0-9]', ''), 6, '0')),
    patient_id = CONCAT('MRN-', LPAD(REGEXP_REPLACE(patient_id, '[^0-9]', ''), 6, '0'))
WHERE (mrn LIKE 'MRN-%' OR mrn LIKE 'P-%' OR patient_id LIKE 'MRN-%' OR patient_id LIKE 'P-%')
  AND NOT (patient_id LIKE 'PROV-%' OR mrn LIKE 'PROV-%');

-- 3. Update appointments table
UPDATE appointments
SET patient_id = CONCAT('MRN-', LPAD(REGEXP_REPLACE(patient_id, '[^0-9]', ''), 6, '0'))
WHERE (patient_id LIKE 'MRN-%' OR patient_id LIKE 'P-%')
  AND NOT (patient_id LIKE 'PROV-%');

-- 4. Update medical_records table
UPDATE medical_records
SET patient_id = CONCAT('MRN-', LPAD(REGEXP_REPLACE(patient_id, '[^0-9]', ''), 6, '0'))
WHERE (patient_id LIKE 'MRN-%' OR patient_id LIKE 'P-%')
  AND NOT (patient_id LIKE 'PROV-%');

-- 5. Update prescriptions table
UPDATE prescriptions
SET patient_id = CONCAT('MRN-', LPAD(REGEXP_REPLACE(patient_id, '[^0-9]', ''), 6, '0'))
WHERE (patient_id LIKE 'MRN-%' OR patient_id LIKE 'P-%')
  AND NOT (patient_id LIKE 'PROV-%');

-- 6. Update lab_results table
UPDATE lab_results
SET patient_id = CONCAT('MRN-', LPAD(REGEXP_REPLACE(patient_id, '[^0-9]', ''), 6, '0'))
WHERE (patient_id LIKE 'MRN-%' OR patient_id LIKE 'P-%')
  AND NOT (patient_id LIKE 'PROV-%');

-- 7. Update admissions table
UPDATE admissions
SET mrn = CONCAT('MRN-', LPAD(REGEXP_REPLACE(mrn, '[^0-9]', ''), 6, '0'))
WHERE mrn IS NOT NULL AND mrn != '' AND (mrn LIKE 'MRN-%' OR mrn LIKE 'P-%')
  AND NOT (mrn LIKE 'PROV-%');

-- 8. Update discharges table
UPDATE discharges
SET mrn = CONCAT('MRN-', LPAD(REGEXP_REPLACE(mrn, '[^0-9]', ''), 6, '0'))
WHERE mrn IS NOT NULL AND mrn != '' AND (mrn LIKE 'MRN-%' OR mrn LIKE 'P-%')
  AND NOT (mrn LIKE 'PROV-%');
