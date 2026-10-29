-- MediCore Hospital Records System — Complete Relational Database Schema
-- Compatible with XAMPP MySQL / MariaDB (phpMyAdmin)

CREATE DATABASE IF NOT EXISTS `medicore_records`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `medicore_records`;

-- 1. Accounts (Admin A-xxxx, Staff S-xxxx, Doctor D-xxxx, Patient P-xxxx)
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Prefix ID: A-xxxx (Admin), S-xxxx (Staff), D-xxxx (Doctor), P-xxxx (Patient)',
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `role` VARCHAR(30) NOT NULL COMMENT 'Admin, Staff, Doctor, Patient',
    `user_role_label` VARCHAR(40) NOT NULL DEFAULT 'Patient' COMMENT 'Administrator, Front desk, Doctor, Patient',
    `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
    `department` VARCHAR(100) NOT NULL DEFAULT '',
    `specialty` VARCHAR(120) NOT NULL DEFAULT '',
    `license_number` VARCHAR(60) NOT NULL DEFAULT '',
    `birthdate` VARCHAR(40) NOT NULL DEFAULT '',
    `last_login` VARCHAR(60) NOT NULL DEFAULT 'Never',
    `created_at` VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Roles & Permissions
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(60) NOT NULL UNIQUE,
    `description` VARCHAR(255) NOT NULL DEFAULT '',
    `user_count` INT NOT NULL DEFAULT 0,
    `permissions_json` TEXT NOT NULL,
    `icon` VARCHAR(40) NOT NULL DEFAULT 'shield'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Patients
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Issued by staff: P-1001, P-1002, etc.',
    `full_name` VARCHAR(150) NOT NULL,
    `birthdate` VARCHAR(40) NOT NULL DEFAULT '—',
    `phone` VARCHAR(40) NOT NULL DEFAULT '—',
    `insurance_no` VARCHAR(60) NOT NULL DEFAULT '—',
    `avatar` VARCHAR(10) NOT NULL DEFAULT 'PT',
    `sex` VARCHAR(30) NOT NULL DEFAULT 'Not specified',
    `blood_type` VARCHAR(30) NOT NULL DEFAULT 'Not specified',
    `address` VARCHAR(255) NOT NULL DEFAULT '—',
    `created_at` VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Departments
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `head_doctor` VARCHAR(150) NOT NULL DEFAULT 'Unassigned',
    `staff_count` VARCHAR(20) NOT NULL DEFAULT '0',
    `location` VARCHAR(100) NOT NULL DEFAULT 'Main Wing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Rooms
CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_number` VARCHAR(40) NOT NULL UNIQUE,
    `ward` VARCHAR(100) NOT NULL DEFAULT 'General Ward',
    `status` VARCHAR(30) NOT NULL DEFAULT 'Available',
    `patient_name` VARCHAR(150) NOT NULL DEFAULT '—'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Medications
CREATE TABLE IF NOT EXISTS `medications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL UNIQUE,
    `category` VARCHAR(80) NOT NULL DEFAULT 'General',
    `strength` VARCHAR(60) NOT NULL DEFAULT '',
    `stock` VARCHAR(60) NOT NULL DEFAULT '0 units'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Appointments
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `patient_id` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Prefix ID: P-xxxx',
    `slot_time` VARCHAR(20) NOT NULL,
    `patient_name` VARCHAR(150) NOT NULL,
    `visit_type` VARCHAR(80) NOT NULL DEFAULT 'Consultation',
    `status` VARCHAR(30) NOT NULL DEFAULT 'Confirmed',
    `complaint` TEXT NOT NULL,
    `doctor_name` VARCHAR(150) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `appt_date` VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Admissions
CREATE TABLE IF NOT EXISTS `admissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `patient_name` VARCHAR(150) NOT NULL,
    `room_number` VARCHAR(40) NOT NULL,
    `ward` VARCHAR(100) NOT NULL,
    `admitted_date` VARCHAR(40) NOT NULL,
    `doctor_name` VARCHAR(150) NOT NULL,
    `reason` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Discharges
CREATE TABLE IF NOT EXISTS `discharges` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `patient_name` VARCHAR(150) NOT NULL,
    `room_number` VARCHAR(40) NOT NULL,
    `ward` VARCHAR(100) NOT NULL,
    `discharged_date` VARCHAR(40) NOT NULL,
    `proof_path` VARCHAR(255) DEFAULT NULL,
    `discharged_ts` BIGINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bills
CREATE TABLE IF NOT EXISTS `bills` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bill_no` VARCHAR(40) NOT NULL DEFAULT '',
    `patient_name` VARCHAR(150) NOT NULL,
    `amount` VARCHAR(40) NOT NULL,
    `paid_amount` VARCHAR(40) NOT NULL DEFAULT '0',
    `status` VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
    `issued_date` VARCHAR(40) NOT NULL,
    `receipt_path` VARCHAR(255) DEFAULT NULL,
    `items_json` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10b. Payments
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `receipt_no` VARCHAR(40) NOT NULL,
    `bill_no` VARCHAR(40) NOT NULL,
    `patient_name` VARCHAR(150) NOT NULL,
    `amount_paid` VARCHAR(40) NOT NULL DEFAULT '0.00',
    `amount_received` VARCHAR(40) NOT NULL DEFAULT '0.00',
    `change_due` VARCHAR(40) NOT NULL DEFAULT '0.00',
    `payment_method` VARCHAR(40) NOT NULL DEFAULT 'Cash',
    `reference_no` VARCHAR(80) NOT NULL DEFAULT '',
    `cashier_name` VARCHAR(150) NOT NULL DEFAULT 'Hospital Cashier',
    `paid_date` VARCHAR(20) NOT NULL,
    `paid_at` VARCHAR(60) NOT NULL,
    `items_json` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Medical Records
CREATE TABLE IF NOT EXISTS `medical_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `record_code` VARCHAR(30) NOT NULL,
    `patient_id` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Prefix ID: P-xxxx',
    `patient_name` VARCHAR(150) NOT NULL,
    `doctor_id` VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Prefix ID: D-xxxx',
    `doctor_name` VARCHAR(150) NOT NULL,
    `visit_date` VARCHAR(40) NOT NULL,
    `visit_type` VARCHAR(80) NOT NULL,
    `complaint` TEXT NOT NULL,
    `notes` TEXT NOT NULL,
    `diagnoses_json` TEXT NOT NULL,
    `prescriptions_json` TEXT NOT NULL,
    `lab_orders_json` TEXT NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'Completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11b. Prescriptions (dedicated relational table)
CREATE TABLE IF NOT EXISTS `prescriptions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rx_code` VARCHAR(30) NOT NULL,
    `record_code` VARCHAR(30) NOT NULL DEFAULT '',
    `patient_id` VARCHAR(20) NOT NULL DEFAULT '',
    `patient_name` VARCHAR(150) NOT NULL,
    `doctor_name` VARCHAR(150) NOT NULL DEFAULT '',
    `medication` VARCHAR(150) NOT NULL,
    `dosage` VARCHAR(100) NOT NULL DEFAULT '',
    `frequency` VARCHAR(100) NOT NULL DEFAULT '',
    `duration` VARCHAR(100) NOT NULL DEFAULT '',
    `prescribed_date` VARCHAR(40) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Lab Results
CREATE TABLE IF NOT EXISTS `lab_results` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `test_code` VARCHAR(30) NOT NULL,
    `record_code` VARCHAR(30) NOT NULL DEFAULT '',
    `patient_id` VARCHAR(20) NOT NULL DEFAULT '',
    `patient_name` VARCHAR(150) NOT NULL,
    `doctor_name` VARCHAR(150) NOT NULL DEFAULT '',
    `test_name` VARCHAR(120) NOT NULL,
    `priority` VARCHAR(30) NOT NULL DEFAULT 'Routine',
    `status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
    `clinical_notes` TEXT DEFAULT NULL,
    `results_json` TEXT DEFAULT NULL,
    `collected_date` VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Archive Records (with full JSON payload for one-click restore)
CREATE TABLE IF NOT EXISTS `archive_records` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `archive_code` VARCHAR(30) NOT NULL UNIQUE,
    `subject_name` VARCHAR(150) NOT NULL,
    `record_type` VARCHAR(60) NOT NULL,
    `archived_date` VARCHAR(40) NOT NULL,
    `archived_by` VARCHAR(150) NOT NULL,
    `payload_json` LONGTEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actor_name` VARCHAR(150) NOT NULL,
    `action` VARCHAR(40) NOT NULL,
    `table_name` VARCHAR(60) NOT NULL,
    `record_id` VARCHAR(60) NOT NULL,
    `logged_at` VARCHAR(60) NOT NULL,
    `actor_role` VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Key-Value Store (permanent flags like admin_id_claimed, notifications)
CREATE TABLE IF NOT EXISTS `app_store` (
    `store_key` VARCHAR(64) PRIMARY KEY,
    `store_value` LONGTEXT NOT NULL,
    `updated_at` VARCHAR(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
