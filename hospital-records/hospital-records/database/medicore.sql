-- ===============================================================
-- MediCore Hospital Records System — Complete Database Schema & Data
-- Compatible with XAMPP MySQL / MariaDB (phpMyAdmin)
-- Database: `medicore_records`
-- ===============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `medicore_records`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `medicore_records`;

-- -------------------------------------------------------------
-- Table structure for `accounts`
-- -------------------------------------------------------------
CREATE TABLE accounts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        user_id VARCHAR(20) NOT NULL UNIQUE,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        role VARCHAR(30) NOT NULL,
        user_role_label VARCHAR(40) NOT NULL DEFAULT 'Patient',
        status VARCHAR(20) NOT NULL DEFAULT 'Active',
        department VARCHAR(100) NOT NULL DEFAULT '',
        specialty VARCHAR(120) NOT NULL DEFAULT '',
        license_number VARCHAR(60) NOT NULL DEFAULT '',
        last_login VARCHAR(60) NOT NULL DEFAULT 'Never',
        created_at VARCHAR(40) NOT NULL
    , birthdate VARCHAR(40) NOT NULL DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `accounts`
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`) VALUES
  (1, 'A-0001', 'admin', 'solonmedea582@gmail.com', '$2y$10$VLLUY/DyAlm78t3phvNqCO5g1vf7vOoFD4xPtsHPTN3B9yTca6Fc6', 'Medea Solon', 'Admin', 'Administrator', 'Active', '', '', '', 'Nov 04, 2026 \302\267 05:53', 'Oct 29, 2026 \302\267 07:35', ''),
  (2, 'D-1001', 'meryll', 'evangelistameryll@gmail.com', '$2y$10$Xkcqej5WDf7uEErflfhpKOg/W/PKRksPxXOMfZ7Msa3HTh7uESth.', 'Meryll Silvano Evangelista', 'Doctor', 'Doctor', 'Active', 'General Medicine', 'Internal Medicine', 12345678, 'Oct 29, 2026 \302\267 11:08', 'Oct 29, 2026 \302\267 09:01', ''),
  (3, 'P-1001', 'emeryll', 'evangelistamerylljoy@gmail.com', '$2y$10$6Vzx2N0F6d6EUUHJEKqOa.Czoqst/QrprDXFJIE2Mf0QqMa667MfO', 'Meryll Joy Evangelista', 'Patient', 'Patient', 'Active', '', '', '', 'Oct 29, 2026 \302\267 11:02', 'Oct 29, 2026 \302\267 09:03', 'Apr 25, 2007'),
  (4, 'S-1001', 'jereme', 'evangelistajereme@gmail.com', '$2y$10$RTCglV4cqujJ6nnyH3F/be6MZVE8ZqCZGdDpate6/q9QSnUjrplEe', 'Jereme Silvano Evangelista', 'Staff', 'Staff', 'Active', 'General Medicine', '', '', 'Nov 04, 2026 \302\267 05:55', 'Oct 29, 2026 \302\267 10:04', ''),
  (5, 'C-1001', 'jae', 'evangelistamerylljae@gmail.com', '$2y$10$uNapGdVaZl6dLOFGMh6dCel1JR4IlwOmdyL83r2FybfTve4hW2N9K', 'Jae Silvano Evangelista', 'Cashier', 'Cashier', 'Active', 'General Medicine', '', '', 'Oct 29, 2026 \302\267 12:51', 'Oct 29, 2026 \302\267 10:49', '');

-- -------------------------------------------------------------
-- Table structure for `roles`
-- -------------------------------------------------------------
CREATE TABLE roles (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(60) NOT NULL UNIQUE,
        description VARCHAR(255) NOT NULL DEFAULT '',
        user_count INT NOT NULL DEFAULT 0,
        permissions_json TEXT NOT NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'shield'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `roles`
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES
  (141, 'Doctor', 'Clinical records and appointments', 1, '["View patients","Edit patients","View medical records","Edit medical records","Manage appointments","Manage admissions","Manage billing","View lab results"]', 'patient'),
  (142, 'Staff', 'Registration, scheduling, admissions, and billing', 1, '["View patients","Edit patients","Manage appointments","Manage admissions","Manage billing"]', 'users'),
  (143, 'Patient', 'Patient portal access for appointments, records, and billing', 1, '["View medical records","Manage appointments","View lab results"]', 'patient'),
  (144, 'Cashier', 'Billing, payment collection, official receipts, and daily reports', 1, '["View patients","Manage billing"]', 'card');

-- -------------------------------------------------------------
-- Table structure for `patients`
-- -------------------------------------------------------------
CREATE TABLE patients (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        patient_id VARCHAR(20) NOT NULL UNIQUE,
        full_name VARCHAR(150) NOT NULL,
        birthdate VARCHAR(40) NOT NULL DEFAULT '—',
        phone VARCHAR(40) NOT NULL DEFAULT '—',
        insurance_no VARCHAR(60) NOT NULL DEFAULT '—',
        avatar VARCHAR(10) NOT NULL DEFAULT 'PT',
        sex VARCHAR(30) NOT NULL DEFAULT 'Not specified',
        blood_type VARCHAR(30) NOT NULL DEFAULT 'Not specified',
        address VARCHAR(255) NOT NULL DEFAULT '—',
        created_at VARCHAR(40) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `patients`
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`) VALUES
  (36, 'P-1001', 'Meryll Joy Evangelista', 'Apr 25, 2007', '\342\200\224', 'HIN-786-755', 'MJ', 'Not specified', 'Not specified', '\342\200\224', '2026-11-04 05:55:15');

-- -------------------------------------------------------------
-- Table structure for `departments`
-- -------------------------------------------------------------
CREATE TABLE departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        head_doctor VARCHAR(150) NOT NULL DEFAULT 'Unassigned',
        staff_count VARCHAR(20) NOT NULL DEFAULT '0',
        location VARCHAR(100) NOT NULL DEFAULT 'Main Wing'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table structure for `rooms`
-- -------------------------------------------------------------
CREATE TABLE rooms (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        room_number VARCHAR(40) NOT NULL UNIQUE,
        ward VARCHAR(100) NOT NULL DEFAULT 'General Ward',
        status VARCHAR(30) NOT NULL DEFAULT 'Available',
        patient_name VARCHAR(150) NOT NULL DEFAULT '—'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `rooms`
INSERT INTO `rooms` (`id`, `room_number`, `ward`, `status`, `patient_name`) VALUES
  (60, 'Room 101', 'General Ward', 'Available', '\342\200\224'),
  (61, 'Room 102', 'General Ward', 'Occupied', 'Meryll Joy Evangelista');

-- -------------------------------------------------------------
-- Table structure for `medications`
-- -------------------------------------------------------------
CREATE TABLE medications (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL UNIQUE,
        category VARCHAR(80) NOT NULL DEFAULT 'General',
        strength VARCHAR(60) NOT NULL DEFAULT '',
        stock VARCHAR(60) NOT NULL DEFAULT '0 units'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table structure for `admissions`
-- -------------------------------------------------------------
CREATE TABLE admissions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        patient_name VARCHAR(150) NOT NULL,
        room_number VARCHAR(40) NOT NULL,
        ward VARCHAR(100) NOT NULL,
        admitted_date VARCHAR(40) NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        reason TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `admissions`
INSERT INTO `admissions` (`id`, `patient_name`, `room_number`, `ward`, `admitted_date`, `doctor_name`, `reason`) VALUES
  (33, 'Meryll Joy Evangelista', 'Room 102', 'General Ward', 'Oct 29, 2026', 'Dr. Meryll Silvano Evangelista', 'Assigned via Rooms');

-- -------------------------------------------------------------
-- Table structure for `discharges`
-- -------------------------------------------------------------
CREATE TABLE discharges (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        patient_name VARCHAR(150) NOT NULL,
        room_number VARCHAR(40) NOT NULL,
        ward VARCHAR(100) NOT NULL,
        discharged_date VARCHAR(40) NOT NULL,
        proof_path VARCHAR(255) DEFAULT NULL,
        discharged_ts BIGINT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table structure for `appointments`
-- -------------------------------------------------------------
CREATE TABLE appointments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        slot_time VARCHAR(20) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        visit_type VARCHAR(80) NOT NULL DEFAULT 'Consultation',
        status VARCHAR(30) NOT NULL DEFAULT 'Confirmed',
        complaint TEXT NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        department VARCHAR(100) NOT NULL,
        appt_date VARCHAR(40) NOT NULL
    , patient_id VARCHAR(20) NOT NULL DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `appointments`
INSERT INTO `appointments` (`id`, `slot_time`, `patient_name`, `visit_type`, `status`, `complaint`, `doctor_name`, `department`, `appt_date`, `patient_id`) VALUES
  (19, '09:00', 'Meryll Joy Evangelista', 'Consultation', 'Completed', 'knsfkm', 'Dr. Meryll Silvano Evangelista', 'General Medicine', 'Oct 30, 2026', 'P-1001');

-- -------------------------------------------------------------
-- Table structure for `medical_records`
-- -------------------------------------------------------------
CREATE TABLE medical_records (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        record_code VARCHAR(30) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        visit_date VARCHAR(40) NOT NULL,
        visit_type VARCHAR(80) NOT NULL,
        complaint TEXT NOT NULL,
        notes TEXT NOT NULL,
        diagnoses_json TEXT NOT NULL,
        prescriptions_json TEXT NOT NULL,
        lab_orders_json TEXT NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Completed'
    , patient_id VARCHAR(20) NOT NULL DEFAULT '', doctor_id VARCHAR(20) NOT NULL DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `medical_records`
INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES
  (40, 'REC-709B8C', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'knsfkm', '', '[]', '[]', '[]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', 'D-1001'),
  (41, 'REC-346859', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'knsfkm', '', '[]', '[["Ibuprofen","10mg","Once daily","30 days"]]', '[]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', 'D-1001'),
  (42, 'REC-347C0C', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'Persistent cough', 'ksmlc', '[]', '[]', '[["Lipid panel","Urgent"]]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', '');

-- -------------------------------------------------------------
-- Table structure for `prescriptions`
-- -------------------------------------------------------------
CREATE TABLE prescriptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        rx_code VARCHAR(30) NOT NULL,
        record_code VARCHAR(30) NOT NULL DEFAULT '',
        patient_id VARCHAR(20) NOT NULL DEFAULT '',
        patient_name VARCHAR(150) NOT NULL,
        doctor_name VARCHAR(150) NOT NULL DEFAULT '',
        medication VARCHAR(150) NOT NULL,
        dosage VARCHAR(100) NOT NULL DEFAULT '',
        frequency VARCHAR(100) NOT NULL DEFAULT '',
        duration VARCHAR(100) NOT NULL DEFAULT '',
        prescribed_date VARCHAR(40) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `prescriptions`
INSERT INTO `prescriptions` (`id`, `rx_code`, `record_code`, `patient_id`, `patient_name`, `doctor_name`, `medication`, `dosage`, `frequency`, `duration`, `prescribed_date`, `status`) VALUES
  (13, 'RX-B015B5', 'REC-346859', 'P-1001', 'Meryll Joy Evangelista', 'Dr. Meryll Silvano Evangelista', 'Ibuprofen', '10mg', 'Once daily', '30 days', 'Oct 29, 2026', 'Active');

-- -------------------------------------------------------------
-- Table structure for `bills`
-- -------------------------------------------------------------
CREATE TABLE bills (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        patient_name VARCHAR(150) NOT NULL,
        amount VARCHAR(40) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
        issued_date VARCHAR(40) NOT NULL,
        receipt_path VARCHAR(255) DEFAULT NULL
    , bill_no VARCHAR(40) NOT NULL DEFAULT '', paid_amount VARCHAR(40) NOT NULL DEFAULT '0', items_json LONGTEXT DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `bills`
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`) VALUES
  (49, 'Meryll Joy Evangelista', '$240.00', 'Unpaid', 'Oct 29, 2026', NULL, 'BILL-1001', '0.00', '[["Outpatient consultation & physician fee",1,"$156.00","$156.00"],["Laboratory & diagnostic services",1,"$84.00","$84.00"]]'),
  (50, 'Meryll Joy Evangelista', '$150.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1002', '150.00', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]'),
  (51, 'Meryll Joy Evangelista', '$150.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1003', '150.00', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]'),
  (52, 'Meryll Joy Evangelista', '$215.00', 'Unpaid', 'Oct 29, 2026', NULL, 'BILL-1004', '0.00', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"],["Lipid panel (Diagnostic Lab Order)",1,"$65.00","$65.00"]]');

-- -------------------------------------------------------------
-- Table structure for `payments`
-- -------------------------------------------------------------
CREATE TABLE payments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        receipt_no VARCHAR(40) NOT NULL,
        bill_no VARCHAR(40) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        amount_paid VARCHAR(40) NOT NULL DEFAULT '0.00',
        amount_received VARCHAR(40) NOT NULL DEFAULT '0.00',
        change_due VARCHAR(40) NOT NULL DEFAULT '0.00',
        payment_method VARCHAR(40) NOT NULL DEFAULT 'Cash',
        reference_no VARCHAR(80) NOT NULL DEFAULT '',
        cashier_name VARCHAR(150) NOT NULL DEFAULT 'Hospital Cashier',
        paid_date VARCHAR(20) NOT NULL,
        paid_at VARCHAR(60) NOT NULL,
        items_json LONGTEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES
  (14, 'OR-1001', 'BILL-1003', 'Meryll Joy Evangelista', '150.00', '150.00', '0.00', 'Cash', '\342\200\224', 'Jae Silvano Evangelista', '2026-10-29', 'Oct 29, 2026 \302\267 13:35', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]'),
  (15, 'OR-1002', 'BILL-1002', 'Meryll Joy Evangelista', '150.00', '150.00', '0.00', 'Cash', '\342\200\224', 'Jae Silvano Evangelista', '2026-10-29', 'Oct 29, 2026 \302\267 14:14', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]');

-- -------------------------------------------------------------
-- Table structure for `lab_results`
-- -------------------------------------------------------------
CREATE TABLE lab_results (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        test_code VARCHAR(30) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        test_name VARCHAR(120) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        collected_date VARCHAR(40) NOT NULL
    , record_code VARCHAR(30) NOT NULL DEFAULT '', patient_id VARCHAR(20) NOT NULL DEFAULT '', doctor_name VARCHAR(150) NOT NULL DEFAULT '', priority VARCHAR(30) NOT NULL DEFAULT 'Routine', clinical_notes TEXT DEFAULT NULL, results_json TEXT DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `lab_results`
INSERT INTO `lab_results` (`id`, `test_code`, `patient_name`, `test_name`, `status`, `collected_date`, `record_code`, `patient_id`, `doctor_name`, `priority`, `clinical_notes`, `results_json`) VALUES
  (16, 'LIP-6196', 'Meryll+Joy+Evangelista', 'Lipid panel', 'Done', 'Oct 29, 2026', '', 'P-1001', '', 'Routine', '', '');

-- -------------------------------------------------------------
-- Table structure for `archive_records`
-- -------------------------------------------------------------
CREATE TABLE archive_records (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        archive_code VARCHAR(30) NOT NULL UNIQUE,
        subject_name VARCHAR(150) NOT NULL,
        record_type VARCHAR(60) NOT NULL,
        archived_date VARCHAR(40) NOT NULL,
        archived_by VARCHAR(150) NOT NULL,
        payload_json LONGTEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table structure for `audit_logs`
-- -------------------------------------------------------------
CREATE TABLE audit_logs (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        actor_name VARCHAR(150) NOT NULL,
        action VARCHAR(40) NOT NULL,
        table_name VARCHAR(60) NOT NULL,
        record_id VARCHAR(60) NOT NULL,
        logged_at VARCHAR(60) NOT NULL,
        actor_role VARCHAR(40) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `audit_logs`
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES
  (307, 'Medea Solon', 'Created', 'accounts', '#A-0001', 'Oct 29, 2026 \302\267 07:35', 'Administrator'),
  (308, 'Medea Solon', 'Created', 'users', '#D-1001', 'Oct 29, 2026 \302\267 09:01', 'Administrator'),
  (309, 'Meryll Joy Evangelista', 'Created', 'accounts', '#P-1001', 'Oct 29, 2026 \302\267 09:03', 'Patient'),
  (310, 'Medea Solon', 'Created', 'rooms', 'Room 102', 'Oct 29, 2026 \302\267 09:35', 'Administrator'),
  (311, 'Medea Solon', 'Created', 'users', '#S-1001', 'Oct 29, 2026 \302\267 10:04', 'Administrator'),
  (312, 'Jereme Silvano Evangelista', 'Created', 'rooms', 'Room 101', 'Oct 29, 2026 \302\267 10:33', 'Front desk'),
  (313, 'Medea Solon', 'Created', 'users', '#C-1001', 'Oct 29, 2026 \302\267 10:49', 'Administrator'),
  (314, 'Meryll Joy Evangelista', 'Created', 'appointments', '#APT-4296', 'Oct 29, 2026 \302\267 11:06', 'Patient'),
  (315, 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-347C0C', 'Oct 29, 2026 \302\267 11:09', 'Doctor'),
  (316, 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-709B8C', 'Oct 29, 2026 \302\267 11:39', 'Doctor'),
  (317, 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-346859', 'Oct 29, 2026 \302\267 11:52', 'Doctor'),
  (318, 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1001', 'Oct 29, 2026 \302\267 13:35', 'Staff'),
  (319, 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1002', 'Oct 29, 2026 \302\267 14:14', 'Staff');

-- -------------------------------------------------------------
-- Table structure for `app_store`
-- -------------------------------------------------------------
CREATE TABLE app_store (
        store_key VARCHAR(64) PRIMARY KEY,
        store_value LONGTEXT NOT NULL,
        updated_at VARCHAR(40) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `app_store`
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES
  ('admin_id_claimed', 1, '2026-10-29 07:35:38'),
  ('roles', '[["Doctor","Clinical records and appointments",0,["View patients","Edit patients","View medical records","Edit medical records","Manage appointments","Manage admissions","Manage billing","View lab results"],"patient"],["Front desk","Registration and scheduling",0,["View patients","Edit patients","Manage appointments","Manage admissions","Manage billing"],"users"],["Patient","Patient portal access for appointments, records, and billing",0,["View medical records","Manage appointments","View lab results"],"patient"]]', '2026-10-29 07:52:14'),
  ('notifications', '{"0":{"title":"Payment receipt issued (OR-1002)","detail":"Official receipt OR-1002 for $150.00 received via Cash for Bill #BILL-1002 (Balance: $0.00).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"1":{"title":"Payment receipt issued (OR-1001)","detail":"Official receipt OR-1001 for $150.00 received via Cash for Bill #BILL-1003 (Balance: $0.00).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"2":{"title":"New visit summary available","detail":"Dr. Dr. Meryll Silvano Evangelista completed your consultation summary and prescribed 1 medication(s).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"3":{"title":"New visit summary available","detail":"Dr. Dr. Meryll Silvano Evangelista completed your consultation summary and prescribed 0 medication(s).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"Admin":[],"Staff":[],"Doctor":[],"Patient":[]}', '2026-11-04 05:55:15'),
  ('archive', '[]', '2026-10-29 07:52:14');

-- -------------------------------------------------------------
-- Table structure for `password_resets`
-- -------------------------------------------------------------
CREATE TABLE password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(150) NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at INT NOT NULL,
        used INT NOT NULL DEFAULT 0,
        created_at INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `password_resets`
INSERT INTO `password_resets` (`id`, `email`, `code_hash`, `expires_at`, `used`, `created_at`) VALUES
  (2, 'solonmedea582@gmail.com', '$2y$10$zxzFQo2j5pxNXooaORHUGO3XyweSScq0Tds89a32GrY3HDtuCTJJq', 1793766494, 1, 1793765594);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
