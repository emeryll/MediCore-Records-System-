-- ===================================================================
-- MediCore Records System: 3NF Normalization Migration Script
-- Database: SQLite & MySQL compatible
-- Description: Migrates 14 tables to Third Normal Form (3NF)
--              Preserves all existing data through safe backfills
-- ===================================================================

-- -------------------------------------------------------------------
-- 1. ADMISSIONS: Backfill missing MRNs & Doctor IDs, drop redundant cols
-- -------------------------------------------------------------------
UPDATE admissions 
SET mrn = (SELECT patient_id FROM patients WHERE patients.first_name || ' ' || (CASE WHEN patients.middle_name IS NOT NULL AND patients.middle_name != '' THEN patients.middle_name || ' ' ELSE '' END) || patients.last_name = admissions.patient_name LIMIT 1)
WHERE mrn IS NULL OR mrn = '';

ALTER TABLE admissions ADD COLUMN doctor_id VARCHAR(30);

UPDATE admissions 
SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = admissions.doctor_name OR ('Dr. ' || accounts.full_name) = admissions.doctor_name) LIMIT 1);

ALTER TABLE admissions DROP COLUMN patient_name;
ALTER TABLE admissions DROP COLUMN ward;
ALTER TABLE admissions DROP COLUMN doctor_name;

-- -------------------------------------------------------------------
-- 2. APPOINTMENTS: Backfill Doctor ID, drop redundant patient & doctor text
-- -------------------------------------------------------------------
ALTER TABLE appointments ADD COLUMN doctor_id VARCHAR(30);

UPDATE appointments 
SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = appointments.doctor_name OR ('Dr. ' || accounts.full_name) = appointments.doctor_name) LIMIT 1);

ALTER TABLE appointments DROP COLUMN patient_name;
ALTER TABLE appointments DROP COLUMN doctor_name;

-- -------------------------------------------------------------------
-- 3. BILLS: Link to Patient ID, drop redundant patient name
-- NOTE: patient_address is retained as an intentional historical snapshot.
-- -------------------------------------------------------------------
ALTER TABLE bills ADD COLUMN patient_id VARCHAR(30);

UPDATE bills 
SET patient_id = (SELECT patient_id FROM patients WHERE patients.first_name || ' ' || (CASE WHEN patients.middle_name IS NOT NULL AND patients.middle_name != '' THEN patients.middle_name || ' ' ELSE '' END) || patients.last_name = bills.patient_name LIMIT 1);

ALTER TABLE bills DROP COLUMN patient_name;

-- -------------------------------------------------------------------
-- 4. DEPARTMENTS: Add Head Doctor ID, drop staff_count & head_doctor text
-- -------------------------------------------------------------------
ALTER TABLE departments ADD COLUMN head_doctor_id VARCHAR(30);

UPDATE departments 
SET head_doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = departments.head_doctor OR ('Dr. ' || accounts.full_name) = departments.head_doctor) LIMIT 1);

ALTER TABLE departments DROP COLUMN head_doctor;
ALTER TABLE departments DROP COLUMN staff_count;

-- -------------------------------------------------------------------
-- 5. DISCHARGES: Add Doctor ID, drop redundant columns & derived length of stay
-- -------------------------------------------------------------------
ALTER TABLE discharges ADD COLUMN doctor_id VARCHAR(30);

UPDATE discharges 
SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = discharges.doctor_name OR ('Dr. ' || accounts.full_name) = discharges.doctor_name) LIMIT 1);

ALTER TABLE discharges DROP COLUMN patient_name;
ALTER TABLE discharges DROP COLUMN ward;
ALTER TABLE discharges DROP COLUMN doctor_name;
ALTER TABLE discharges DROP COLUMN length_of_stay;

-- -------------------------------------------------------------------
-- 6. LAB_RESULTS: Add Doctor ID, drop redundant patient & doctor text
-- -------------------------------------------------------------------
ALTER TABLE lab_results ADD COLUMN doctor_id VARCHAR(30);

UPDATE lab_results 
SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = lab_results.doctor_name OR ('Dr. ' || accounts.full_name) = lab_results.doctor_name) LIMIT 1);

ALTER TABLE lab_results DROP COLUMN patient_name;
ALTER TABLE lab_results DROP COLUMN doctor_name;

-- -------------------------------------------------------------------
-- 7. MEDICAL_RECORDS: Ensure Doctor ID populated, drop redundant names
-- -------------------------------------------------------------------
UPDATE medical_records 
SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = medical_records.doctor_name OR ('Dr. ' || accounts.full_name) = medical_records.doctor_name) LIMIT 1)
WHERE doctor_id IS NULL OR doctor_id = '';

ALTER TABLE medical_records DROP COLUMN patient_name;
ALTER TABLE medical_records DROP COLUMN doctor_name;

-- -------------------------------------------------------------------
-- 8. PATIENT_ACCOUNT_LINKS: Drop redundant patient name & MRN
-- -------------------------------------------------------------------
ALTER TABLE patient_account_links DROP COLUMN patient_name;
ALTER TABLE patient_account_links DROP COLUMN mrn;

-- -------------------------------------------------------------------
-- 9. PAYMENTS: Link Cashier ID, drop redundant patient name & derived change
-- -------------------------------------------------------------------
ALTER TABLE payments ADD COLUMN cashier_id VARCHAR(30);

UPDATE payments 
SET cashier_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Cashier' AND accounts.full_name = payments.cashier_name LIMIT 1);

ALTER TABLE payments DROP COLUMN patient_name;
ALTER TABLE payments DROP COLUMN change_due;
ALTER TABLE payments DROP COLUMN cashier_name;

-- -------------------------------------------------------------------
-- 10. PRESCRIPTIONS: Add Doctor ID, drop redundant names
-- -------------------------------------------------------------------
ALTER TABLE prescriptions ADD COLUMN doctor_id VARCHAR(30);

UPDATE prescriptions 
SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = prescriptions.doctor_name OR ('Dr. ' || accounts.full_name) = prescriptions.doctor_name) LIMIT 1);

ALTER TABLE prescriptions DROP COLUMN patient_name;
ALTER TABLE prescriptions DROP COLUMN doctor_name;

-- -------------------------------------------------------------------
-- 11. ROLES: Drop calculated user count
-- -------------------------------------------------------------------
ALTER TABLE roles DROP COLUMN user_count;

-- -------------------------------------------------------------------
-- 12. ROOMS: Drop redundant patient name occupant snapshot
-- -------------------------------------------------------------------
ALTER TABLE rooms DROP COLUMN patient_name;

-- -------------------------------------------------------------------
-- 13. ACCOUNTS: Drop redundant role description & derived full address
-- -------------------------------------------------------------------
ALTER TABLE accounts DROP COLUMN user_role_label;
ALTER TABLE accounts DROP COLUMN address;

-- -------------------------------------------------------------------
-- 14. PATIENTS: Drop derived full name, address, and contact name
-- -------------------------------------------------------------------
ALTER TABLE patients DROP COLUMN full_name;
ALTER TABLE patients DROP COLUMN address;
ALTER TABLE patients DROP COLUMN contact_name;
