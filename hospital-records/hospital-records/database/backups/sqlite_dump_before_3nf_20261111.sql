-- MediCore Records System - Backup before 3NF Normalization
-- Created at: 2026-11-11 03:48:37

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE "accounts" (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id VARCHAR(20) NOT NULL UNIQUE,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    email VARCHAR(150) DEFAULT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    full_name VARCHAR(150) NOT NULL,
                    role VARCHAR(30) NOT NULL,
                    user_role_label VARCHAR(40) NOT NULL DEFAULT 'Patient',
                    status VARCHAR(20) NOT NULL DEFAULT 'Active',
                    department VARCHAR(100) NOT NULL DEFAULT '',
                    specialty VARCHAR(120) NOT NULL DEFAULT '',
                    license_number VARCHAR(60) NOT NULL DEFAULT '',
                    birthdate VARCHAR(40) NOT NULL DEFAULT '',
                    last_login VARCHAR(60) NOT NULL DEFAULT 'Never',
                    created_at VARCHAR(40) NOT NULL,
                    phone VARCHAR(50) NOT NULL DEFAULT '',
                    phone_verified INT NOT NULL DEFAULT 0,
                    address TEXT,
                    country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
                    province VARCHAR(100) NOT NULL DEFAULT '',
                    city VARCHAR(100) NOT NULL DEFAULT '',
                    barangay VARCHAR(100) NOT NULL DEFAULT '',
                    street VARCHAR(255) NOT NULL DEFAULT '',
                    postal_code VARCHAR(30) NOT NULL DEFAULT '',
                    sex VARCHAR(30) NOT NULL DEFAULT 'Not specified',
                    force_password_change INT NOT NULL DEFAULT 0,
                    set_password_token VARCHAR(120) NOT NULL DEFAULT '',
                    set_password_expires_at BIGINT NOT NULL DEFAULT 0
                );

INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `birthdate`, `last_login`, `created_at`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('1', 'A-0001', 'admin', 'solonmedea582@gmail.com', '$2y$10$VXfKt9EABvjzwIHCCd7lVeeS1snNOYGQb/XiFpq4FM.6YHB1IbO46', 'Medea Solon', 'Admin', 'Admin', 'Active', '', '', '', '', 'Nov 06, 2026 · 15:18', 'Oct 29, 2026 · 07:35', '09171234567', '1', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `birthdate`, `last_login`, `created_at`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('2', 'D-1001', 'meryll', 'evangelistameryll@gmail.com', '$2y$10$Xkcqej5WDf7uEErflfhpKOg/W/PKRksPxXOMfZ7Msa3HTh7uESth.', 'Meryll Silvano Evangelista', 'Doctor', 'Doctor', 'Active', 'General Medicine', 'Internal Medicine', '12345678', '', 'Oct 29, 2026 · 11:08', 'Oct 29, 2026 · 09:01', '09181234567', '1', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `birthdate`, `last_login`, `created_at`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('3', 'P-1001', 'emeryll', 'evangelistamerylljoy@gmail.com', '$2y$10$6Vzx2N0F6d6EUUHJEKqOa.Czoqst/QrprDXFJIE2Mf0QqMa667MfO', 'Meryll Joy Evangelista', 'Patient', 'Patient', 'Active', '', '', '', 'Apr 25, 2007', 'Oct 29, 2026 · 11:02', 'Oct 29, 2026 · 09:03', '09211234567', '1', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `birthdate`, `last_login`, `created_at`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('4', 'S-1001', 'jereme', 'evangelistajereme@gmail.com', '$2y$10$RTCglV4cqujJ6nnyH3F/be6MZVE8ZqCZGdDpate6/q9QSnUjrplEe', 'Jereme Silvano Evangelista', 'Staff', 'Staff', 'Active', 'General Medicine', '', '', '', 'Nov 08, 2026 · 08:34', 'Oct 29, 2026 · 10:04', '09191234567', '1', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `birthdate`, `last_login`, `created_at`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('5', 'C-1001', 'jae', 'evangelistamerylljae@gmail.com', '$2y$10$uNapGdVaZl6dLOFGMh6dCel1JR4IlwOmdyL83r2FybfTve4hW2N9K', 'Jae Silvano Evangelista', 'Cashier', 'Cashier', 'Active', 'General Medicine', '', '', '', 'Nov 09, 2026 · 07:33', 'Oct 29, 2026 · 10:49', '09201234567', '1', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `birthdate`, `last_login`, `created_at`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('7', 'P-1003', 'tommy test', NULL, '$2y$10$zz6Ziu3FwUUZ0Zr942fv0.M8shOo7tJW6NP0y5saR.Ps1Lnsjn6Ti', 'Tommy Test', 'Patient', 'Patient', 'Active', '', '', '', 'Aug 15, 2016', 'Nov 08, 2026 · 08:38', 'Nov 08, 2026 · 08:33', '09222222222', '1', 'N/A, Canelar, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', '', '', '', '', '', 'Male', '0', '', '0');

DROP TABLE IF EXISTS `address_history`;
CREATE TABLE address_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_type VARCHAR(20) NOT NULL,
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

DROP TABLE IF EXISTS `admissions`;
CREATE TABLE admissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_name VARCHAR(150) NOT NULL,
        room_number VARCHAR(40) NOT NULL,
        ward VARCHAR(100) NOT NULL,
        admitted_date VARCHAR(40) NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        reason TEXT NOT NULL
    , admission_id VARCHAR(30) NOT NULL DEFAULT '', mrn VARCHAR(30) NOT NULL DEFAULT '', department VARCHAR(100) NOT NULL DEFAULT 'Internal Medicine', bed_label VARCHAR(50) NOT NULL DEFAULT 'Bed A', status VARCHAR(30) NOT NULL DEFAULT 'Admitted', clinical_notes_json LONGTEXT DEFAULT NULL, discharge_order TEXT DEFAULT NULL, final_diagnosis TEXT DEFAULT NULL, billing_clearance VARCHAR(30) NOT NULL DEFAULT 'Pending', clearance_reason TEXT DEFAULT NULL, cleared_by VARCHAR(120) NOT NULL DEFAULT '');

INSERT INTO `admissions` (`id`, `patient_name`, `room_number`, `ward`, `admitted_date`, `doctor_name`, `reason`, `admission_id`, `mrn`, `department`, `bed_label`, `status`, `clinical_notes_json`, `discharge_order`, `final_diagnosis`, `billing_clearance`, `clearance_reason`, `cleared_by`) VALUES ('65', 'Meryll Joy Evangelista', 'Room 102', 'General Ward', 'Oct 29, 2026', 'Dr. Meryll Silvano Evangelista', 'Assigned via Rooms', 'ADM-1036', '', 'Internal Medicine', 'Bed A', 'Admitted', '[]', '', '', 'Pending', '', '');

DROP TABLE IF EXISTS `app_store`;
CREATE TABLE app_store (
        store_key VARCHAR(64) PRIMARY KEY,
        store_value LONGTEXT NOT NULL,
        updated_at VARCHAR(40) NOT NULL
    );

INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('admin_id_claimed', '1', '2026-10-29 07:35:38');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('roles', '[["Doctor","Clinical records and appointments",0,["View patients","Edit patients","View medical records","Edit medical records","Manage appointments","Manage admissions","Manage billing","View lab results"],"patient"],["Front desk","Registration and scheduling",0,["View patients","Edit patients","Manage appointments","Manage admissions","Manage billing"],"users"],["Patient","Patient portal access for appointments, records, and billing",0,["View medical records","Manage appointments","View lab results"],"patient"]]', '2026-10-29 07:52:14');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('notifications', '{"0":{"title":"Payment receipt issued (OR-1002)","detail":"Official receipt OR-1002 for $150.00 received via Cash for Bill #BILL-1002 (Balance: $0.00).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"1":{"title":"Payment receipt issued (OR-1001)","detail":"Official receipt OR-1001 for $150.00 received via Cash for Bill #BILL-1003 (Balance: $0.00).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"2":{"title":"New visit summary available","detail":"Dr. Dr. Meryll Silvano Evangelista completed your consultation summary and prescribed 1 medication(s).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"3":{"title":"New visit summary available","detail":"Dr. Dr. Meryll Silvano Evangelista completed your consultation summary and prescribed 0 medication(s).","time":"Just now","unread":true,"role":"Patient","user_id":"P-1001"},"Admin":[],"Staff":[],"Doctor":[],"Patient":[]}', '2026-11-09 07:35:52');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('archive', '[]', '2026-10-29 07:52:14');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('beds', '[{"bed_id":"Room 101 - Bed A","room_no":"101","bed_code":"Bed A","ward":"General Ward","room_type":"Ward","floor":"1st Floor","rate":1500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 101 - Bed B","room_no":"101","bed_code":"Bed B","ward":"General Ward","room_type":"Ward","floor":"1st Floor","rate":1500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 101 - Bed C","room_no":"101","bed_code":"Bed C","ward":"General Ward","room_type":"Ward","floor":"1st Floor","rate":1500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 102 - Bed A","room_no":"102","bed_code":"Bed A","ward":"General Ward","room_type":"Ward","floor":"1st Floor","rate":1500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 102 - Bed B","room_no":"102","bed_code":"Bed B","ward":"General Ward","room_type":"Ward","floor":"1st Floor","rate":1500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 201 - Bed A","room_no":"201","bed_code":"Bed A","ward":"Pediatrics Ward","room_type":"Semi-Private","floor":"2nd Floor","rate":2500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 201 - Bed B","room_no":"201","bed_code":"Bed B","ward":"Pediatrics Ward","room_type":"Semi-Private","floor":"2nd Floor","rate":2500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 202 - Bed A","room_no":"202","bed_code":"Bed A","ward":"Maternal & OB-GYN Ward","room_type":"Semi-Private","floor":"2nd Floor","rate":2500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 202 - Bed B","room_no":"202","bed_code":"Bed B","ward":"Maternal & OB-GYN Ward","room_type":"Semi-Private","floor":"2nd Floor","rate":2500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 301 - Bed 1","room_no":"301","bed_code":"Bed 1","ward":"Medical-Surgical Private","room_type":"Private","floor":"3rd Floor","rate":4200,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Room 302 - Bed 1","room_no":"302","bed_code":"Bed 1","ward":"Heart & Vascular Unit","room_type":"Private","floor":"3rd Floor","rate":4500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"ICU-01 - Bed 1","room_no":"ICU-01","bed_code":"Bed 1","ward":"Intensive Care Unit (ICU)","room_type":"ICU","floor":"2nd Floor","rate":7500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"ICU-01 - Bed 2","room_no":"ICU-01","bed_code":"Bed 2","ward":"Intensive Care Unit (ICU)","room_type":"ICU","floor":"2nd Floor","rate":7500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true},{"bed_id":"Suite 401 - Bed 1","room_no":"Suite 401","bed_code":"Bed 1","ward":"Executive Wing","room_type":"Suite","floor":"4th Floor","rate":9500,"status":"Vacant","patient":"—","mrn":"","admitted_date":"","doctor":"","maintenance_reason":"","active":true}]', '2026-11-09 07:35:52');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('price_list', '[{"code":"PRC-101","name":"General Outpatient Consultation","category":"Consultation","department":"Internal Medicine","price":600,"status":"Active","id":"PRC-101","active":true},{"code":"PRC-102","name":"Specialist Consultation","category":"Consultation","department":"All Departments","price":850,"status":"Active","id":"PRC-102","active":true},{"code":"PRC-103","name":"Follow-up Consultation","category":"Consultation","department":"All Departments","price":500,"status":"Active","id":"PRC-103","active":true},{"code":"PRC-104","name":"Emergency Room Evaluation","category":"Consultation","department":"Emergency Medicine","price":1200,"status":"Active","id":"PRC-104","active":true},{"code":"LAB-201","name":"Complete Blood Count (CBC)","category":"Laboratory","department":"Clinical Laboratory","price":350,"status":"Active","id":"LAB-201","active":true},{"code":"LAB-202","name":"Urinalysis (Routine)","category":"Laboratory","department":"Clinical Laboratory","price":200,"status":"Active","id":"LAB-202","active":true},{"code":"LAB-203","name":"Fasting Blood Sugar (FBS)","category":"Laboratory","department":"Clinical Laboratory","price":280,"status":"Active","id":"LAB-203","active":true},{"code":"LAB-204","name":"Lipid Panel (Cholesterol\/Triglycerides)","category":"Laboratory","department":"Clinical Laboratory","price":850,"status":"Active","id":"LAB-204","active":true},{"code":"LAB-205","name":"Metabolic Panel (Electrolytes\/Creatinine)","category":"Laboratory","department":"Clinical Laboratory","price":1100,"status":"Active","id":"LAB-205","active":true},{"code":"LAB-206","name":"Liver Function Test (SGPT\/SGOT)","category":"Laboratory","department":"Clinical Laboratory","price":750,"status":"Active","id":"LAB-206","active":true},{"code":"LAB-207","name":"Thyroid Function (TSH \/ FT4)","category":"Laboratory","department":"Clinical Laboratory","price":1450,"status":"Active","id":"LAB-207","active":true},{"code":"RAD-301","name":"Chest X-Ray (PA View)","category":"Laboratory","department":"Radiology & Diagnostic Imaging","price":650,"status":"Active","id":"RAD-301","active":true},{"code":"RAD-302","name":"Electrocardiogram (ECG)","category":"Laboratory","department":"Cardiology","price":600,"status":"Active","id":"RAD-302","active":true},{"code":"RAD-303","name":"2D Echocardiography with Doppler","category":"Laboratory","department":"Cardiology","price":3500,"status":"Active","id":"RAD-303","active":true},{"code":"RM-501","name":"General Ward Bed (Per Day)","category":"Room Rate","department":"Inpatient","price":1500,"status":"Active","id":"RM-501","active":true},{"code":"RM-502","name":"Semi-Private Room (Per Day)","category":"Room Rate","department":"Inpatient","price":2500,"status":"Active","id":"RM-502","active":true},{"code":"RM-503","name":"Private Room (Per Day)","category":"Room Rate","department":"Inpatient","price":4200,"status":"Active","id":"RM-503","active":true},{"code":"RM-504","name":"Intensive Care Unit - ICU (Per Day)","category":"Room Rate","department":"Inpatient","price":7500,"status":"Active","id":"RM-504","active":true},{"code":"RM-505","name":"Executive Suite (Per Day)","category":"Room Rate","department":"Inpatient","price":9500,"status":"Active","id":"RM-505","active":true}]', '2026-11-09 07:35:52');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('doctor_schedules', '[]', '2026-11-09 07:35:52');

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slot_time VARCHAR(20) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        visit_type VARCHAR(80) NOT NULL DEFAULT 'Consultation',
        status VARCHAR(30) NOT NULL DEFAULT 'Confirmed',
        complaint TEXT NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        department VARCHAR(100) NOT NULL,
        appt_date VARCHAR(40) NOT NULL
    , patient_id VARCHAR(20) NOT NULL DEFAULT '');

INSERT INTO `appointments` (`id`, `slot_time`, `patient_name`, `visit_type`, `status`, `complaint`, `doctor_name`, `department`, `appt_date`, `patient_id`) VALUES ('51', '09:00', 'Meryll Joy Evangelista', 'Consultation', 'Completed', 'knsfkm', 'Dr. Meryll Silvano Evangelista', 'General Medicine', 'Oct 30, 2026', 'P-1001');

DROP TABLE IF EXISTS `archive_records`;
CREATE TABLE archive_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        archive_code VARCHAR(30) NOT NULL UNIQUE,
        subject_name VARCHAR(150) NOT NULL,
        record_type VARCHAR(60) NOT NULL,
        archived_date VARCHAR(40) NOT NULL,
        archived_by VARCHAR(150) NOT NULL,
        payload_json LONGTEXT DEFAULT NULL
    );

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE audit_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        actor_name VARCHAR(150) NOT NULL,
        action VARCHAR(40) NOT NULL,
        table_name VARCHAR(60) NOT NULL,
        record_id VARCHAR(60) NOT NULL,
        logged_at VARCHAR(60) NOT NULL,
        actor_role VARCHAR(40) NOT NULL
    );

INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('870', 'Medea Solon', 'Created', 'accounts', '#A-0001', 'Oct 29, 2026 · 07:35', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('871', 'Medea Solon', 'Created', 'users', '#D-1001', 'Oct 29, 2026 · 09:01', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('872', 'Meryll Joy Evangelista', 'Created', 'accounts', '#P-1001', 'Oct 29, 2026 · 09:03', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('873', 'Medea Solon', 'Created', 'rooms', 'Room 102', 'Oct 29, 2026 · 09:35', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('874', 'Medea Solon', 'Created', 'users', '#S-1001', 'Oct 29, 2026 · 10:04', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('875', 'Jereme Silvano Evangelista', 'Created', 'rooms', 'Room 101', 'Oct 29, 2026 · 10:33', 'Front desk');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('876', 'Medea Solon', 'Created', 'users', '#C-1001', 'Oct 29, 2026 · 10:49', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('877', 'Meryll Joy Evangelista', 'Created', 'appointments', '#APT-4296', 'Oct 29, 2026 · 11:06', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('878', 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-347C0C', 'Oct 29, 2026 · 11:09', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('879', 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-709B8C', 'Oct 29, 2026 · 11:39', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('880', 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-346859', 'Oct 29, 2026 · 11:52', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('881', 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1001', 'Oct 29, 2026 · 13:35', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('882', 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1002', 'Oct 29, 2026 · 14:14', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('883', 'Medea Solon', 'Sign in', 'accounts', '#A-0001', 'Nov 06, 2026 · 02:22', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('884', 'Medea Solon', 'Sign in', 'accounts', '#A-0001', 'Nov 06, 2026 · 15:18', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('885', 'Jereme Silvano Evangelista', 'Registered patient', 'patients', 'MRN-1003', 'Nov 08, 2026 · 08:27', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('886', 'Tommy Test acting as Patient', 'Activated account', 'accounts', '#P-1003', 'Nov 08, 2026 · 08:33', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('887', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 08, 2026 · 08:34', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('888', 'Jereme Silvano Evangelista', 'Registered patient', 'patients', 'MRN-1004', 'Nov 08, 2026 · 08:36', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('889', 'Medea Solon acting as Admin', 'Linked patient profile', 'patient_account_links', '#MRN-1004', 'Nov 08, 2026 · 08:38', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('890', 'Tommy Test acting as Patient', 'Sign in', 'accounts', '#P-1003', 'Nov 08, 2026 · 08:38', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('891', 'Jae acting as Cashier', 'Blocked conflict-of-interest attempt: Cashier attempted to collect own bill', 'bills', '#BILL-1001', 'Nov 08, 2026 · 09:34', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('892', 'Jae Silvano Evangelista acting as Cashier', 'Sign in', 'accounts', '#C-1001', 'Nov 09, 2026 · 07:33', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('893', 'Jae Silvano Evangelista acting as Cashier', 'Collected payment (Cash)', 'payments', 'OR-1003', 'Nov 09, 2026 · 07:35', 'Cashier');

DROP TABLE IF EXISTS `bills`;
CREATE TABLE bills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_name VARCHAR(150) NOT NULL,
        amount VARCHAR(40) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
        issued_date VARCHAR(40) NOT NULL,
        receipt_path VARCHAR(255) DEFAULT NULL
    , bill_no VARCHAR(40) NOT NULL DEFAULT '', paid_amount VARCHAR(40) NOT NULL DEFAULT '0', items_json LONGTEXT DEFAULT NULL, patient_address VARCHAR(255) DEFAULT NULL);

INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`, `patient_address`) VALUES ('177', 'Meryll Joy Evangelista', '₱240.00', 'Unpaid', 'Oct 29, 2026', NULL, 'BILL-1001', '0.00', '[["Outpatient consultation & physician fee",1,"$156.00","$156.00"],["Laboratory & diagnostic services",1,"$84.00","$84.00"]]', '—');
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`, `patient_address`) VALUES ('178', 'Meryll Joy Evangelista', '₱150.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1002', '150.00', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]', '—');
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`, `patient_address`) VALUES ('179', 'Meryll Joy Evangelista', '₱150.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1003', '150.00', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]', '—');
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`, `patient_address`) VALUES ('180', 'Meryll Joy Evangelista', '₱215.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1004', '215.00', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"],["Lipid panel (Diagnostic Lab Order)",1,"$65.00","$65.00"]]', '—');

DROP TABLE IF EXISTS `departments`;
CREATE TABLE departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        head_doctor VARCHAR(150) NOT NULL DEFAULT 'Unassigned',
        staff_count VARCHAR(20) NOT NULL DEFAULT '0',
        location VARCHAR(100) NOT NULL DEFAULT 'Main Wing'
    );

INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('262', 'Internal Medicine', 'Dr. Rafael Lorenzo', '6', '2nd Floor · Main Medical Wing');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('263', 'Cardiology', 'Dr. Elena Santos', '4', '2nd Floor · Heart & Vascular Center');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('264', 'Pediatrics & Neonatology', 'Dr. Camille Navarro', '5', '3rd Floor · Maternal & Child Wing');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('265', 'Obstetrics & Gynecology', 'Dr. Patricia Mendoza', '5', '3rd Floor · Maternal & Child Wing');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('266', 'General Surgery', 'Dr. Marco Villanueva', '4', '2nd Floor · Surgical Pavilion');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('267', 'Orthopedics', 'Dr. Antonio Reyes', '3', '2nd Floor · Bone & Joint Center');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('268', 'Emergency Medicine', 'Dr. Gabriel Cruz', '8', 'Ground Floor · ER Complex');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('269', 'Nephrology & Hemodialysis', 'Dr. Sofia Lim', '4', 'Ground Floor · Dialysis Center');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('270', 'Radiology & Diagnostic Imaging', 'Dr. Victor Alvarez', '3', 'Ground Floor · Diagnostic Wing');

DROP TABLE IF EXISTS `discharges`;
CREATE TABLE discharges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_name VARCHAR(150) NOT NULL,
        room_number VARCHAR(40) NOT NULL,
        ward VARCHAR(100) NOT NULL,
        discharged_date VARCHAR(40) NOT NULL,
        proof_path VARCHAR(255) DEFAULT NULL,
        discharged_ts BIGINT NOT NULL DEFAULT 0
    , mrn VARCHAR(30) NOT NULL DEFAULT '', bed_label VARCHAR(50) NOT NULL DEFAULT 'Bed A', doctor_name VARCHAR(150) NOT NULL DEFAULT '', admitted_date VARCHAR(40) NOT NULL DEFAULT '', discharge_type VARCHAR(80) NOT NULL DEFAULT 'Recovered / Improved', final_diagnosis TEXT DEFAULT NULL, length_of_stay INT NOT NULL DEFAULT 1);

DROP TABLE IF EXISTS `lab_results`;
CREATE TABLE lab_results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        test_code VARCHAR(30) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        test_name VARCHAR(120) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        collected_date VARCHAR(40) NOT NULL
    , record_code VARCHAR(30) NOT NULL DEFAULT '', patient_id VARCHAR(20) NOT NULL DEFAULT '', doctor_name VARCHAR(150) NOT NULL DEFAULT '', priority VARCHAR(30) NOT NULL DEFAULT 'Routine', clinical_notes TEXT DEFAULT NULL, results_json TEXT DEFAULT NULL);

INSERT INTO `lab_results` (`id`, `test_code`, `patient_name`, `test_name`, `status`, `collected_date`, `record_code`, `patient_id`, `doctor_name`, `priority`, `clinical_notes`, `results_json`) VALUES ('48', 'LIP-6196', 'Meryll+Joy+Evangelista', 'Lipid panel', 'Done', 'Oct 29, 2026', '', 'P-1001', '', 'Routine', '', '');

DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE medical_records (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    , patient_id VARCHAR(20) NOT NULL DEFAULT '', doctor_id VARCHAR(20) NOT NULL DEFAULT '');

INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES ('136', 'REC-709B8C', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'knsfkm', '', '[]', '[]', '[]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', 'D-1001');
INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES ('137', 'REC-346859', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'knsfkm', '', '[]', '[["Ibuprofen","10mg","Once daily","30 days"]]', '[]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', 'D-1001');
INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES ('138', 'REC-347C0C', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'Persistent cough', 'ksmlc', '[]', '[]', '[["Lipid panel","Urgent"]]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', '');

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email VARCHAR(150) NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at INT NOT NULL,
        used INT NOT NULL DEFAULT 0,
        created_at INT NOT NULL
    );

INSERT INTO `password_resets` (`id`, `email`, `code_hash`, `expires_at`, `used`, `created_at`) VALUES ('2', 'solonmedea582@gmail.com', '$2y$10$zxzFQo2j5pxNXooaORHUGO3XyweSScq0Tds89a32GrY3HDtuCTJJq', '1793766494', '1', '1793765594');

DROP TABLE IF EXISTS `patient_account_links`;
CREATE TABLE patient_account_links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_uid VARCHAR(30) NOT NULL,
        patient_id VARCHAR(30) NOT NULL,
        mrn VARCHAR(30) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        relationship_label VARCHAR(60) NOT NULL DEFAULT 'Self',
        is_primary TINYINT(1) NOT NULL DEFAULT 0,
        linked_at VARCHAR(40) NOT NULL,
        linked_by VARCHAR(60) NOT NULL DEFAULT 'Self-Service'
    , unlinked_at VARCHAR(40) DEFAULT NULL);

INSERT INTO `patient_account_links` (`id`, `account_uid`, `patient_id`, `mrn`, `patient_name`, `relationship_label`, `is_primary`, `linked_at`, `linked_by`, `unlinked_at`) VALUES ('3', 'P-1003', 'MRN-1003', 'MRN-1003', 'Tommy Test', 'Me', '1', '2026-11-08 08:33:15', 'Initial Account Activation', NULL);
INSERT INTO `patient_account_links` (`id`, `account_uid`, `patient_id`, `mrn`, `patient_name`, `relationship_label`, `is_primary`, `linked_at`, `linked_by`, `unlinked_at`) VALUES ('4', 'P-1003', 'MRN-1004', 'MRN-1004', 'Mary Jane Test', 'Dependent', '0', '2026-11-08 08:38:00', 'Activation Link Self-Service', NULL);
INSERT INTO `patient_account_links` (`id`, `account_uid`, `patient_id`, `mrn`, `patient_name`, `relationship_label`, `is_primary`, `linked_at`, `linked_by`, `unlinked_at`) VALUES ('5', 'C-1001', 'MRN-1001', 'MRN-1001', 'Meryll Joy Evangelista', 'Self', '1', '2026-11-08 09:34:27', 'Test', NULL);

DROP TABLE IF EXISTS `patients`;
CREATE TABLE "patients" (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_id VARCHAR(20) NOT NULL UNIQUE,
        full_name VARCHAR(150) NOT NULL,
        birthdate VARCHAR(40) NOT NULL DEFAULT '—',
        phone VARCHAR(40) DEFAULT NULL,
        insurance_no VARCHAR(60) NOT NULL DEFAULT '—',
        avatar VARCHAR(10) NOT NULL DEFAULT 'PT',
        sex VARCHAR(30) NOT NULL DEFAULT 'Not specified',
        blood_type VARCHAR(30) NOT NULL DEFAULT 'Not specified',
        address VARCHAR(255) NOT NULL DEFAULT '—',
        country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
        province VARCHAR(100) NOT NULL DEFAULT '',
        city VARCHAR(100) NOT NULL DEFAULT '',
        barangay VARCHAR(100) NOT NULL DEFAULT '',
        street VARCHAR(150) NOT NULL DEFAULT '',
        postal_code VARCHAR(20) NOT NULL DEFAULT '',
        phone_verified INT NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'Active',
        created_at VARCHAR(40) NOT NULL,
        no_phone TINYINT(1) NOT NULL DEFAULT 0,
        contact_name VARCHAR(150) DEFAULT NULL,
        contact_relationship VARCHAR(80) DEFAULT NULL,
        contact_phone VARCHAR(40) DEFAULT NULL,
        email VARCHAR(150) NOT NULL DEFAULT '',
        mrn VARCHAR(30) NOT NULL DEFAULT '',
        registration_status VARCHAR(30) NOT NULL DEFAULT 'Registered',
        first_name VARCHAR(80) NOT NULL DEFAULT '',
        middle_name VARCHAR(80) NOT NULL DEFAULT '',
        last_name VARCHAR(80) NOT NULL DEFAULT '',
        id_checked INT NOT NULL DEFAULT 0,
        consent_given INT NOT NULL DEFAULT 0,
        created_by VARCHAR(120) NOT NULL DEFAULT 'Staff',
        upgraded_by VARCHAR(120) NOT NULL DEFAULT '',
        upgraded_at VARCHAR(50) NOT NULL DEFAULT '',
        contact_first_name VARCHAR(80) NULL DEFAULT NULL,
        contact_middle_name VARCHAR(80) NULL DEFAULT NULL,
        contact_last_name VARCHAR(80) NULL DEFAULT NULL,
        contact_relationship_other VARCHAR(80) NULL DEFAULT NULL
    );

INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `created_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('133', 'MRN-1001', 'Meryll Joy Evangelista', 'Apr 25, 2007', NULL, 'HIN-786-755', 'MJ', 'Not specified', 'Not specified', '—', 'Philippines', '', '', '', '', '', '0', 'Active', '2026-11-04 14:20:13', '0', NULL, NULL, NULL, '', 'MRN-1001', 'Registered', 'Meryll', 'Joy', 'Evangelista', '0', '0', 'Staff', '', '', NULL, NULL, NULL, NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `created_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('134', 'MRN-1002', 'Albardeen Jumlani Kamlon', 'Mar 03, 2007', '09111111111', '—', 'AJ', 'Male', 'Not specified', 'Bulahan Drive, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Baliwasan', 'Bulahan Drive', '7000', '1', 'Active', '2026-11-04 16:16:00', '0', NULL, NULL, NULL, '', 'MRN-1002', 'Registered', 'Albardeen', 'Jumlani', 'Kamlon', '1', '1', 'Staff', '', '', NULL, NULL, NULL, NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `created_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('135', 'MRN-1003', 'Tommy Test', 'Aug 15, 2016', '09222222222', '—', 'TT', 'Male', 'Not specified', 'N/A, Canelar, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Canelar', 'N/A', '7000', '1', 'Active', '2026-11-08 08:27:39', '0', NULL, NULL, NULL, '', 'MRN-1003', 'Registered', 'Tommy', '', 'Test', '1', '0', 'Staff', '', '', NULL, NULL, NULL, NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `created_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('136', 'MRN-1004', 'Mary Jane Test', 'May 17, 1981', '09222222222', '—', 'MJ', 'Female', 'Not specified', 'N/A, Canelar, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Canelar', 'N/A', '7000', '1', 'Active', '2026-11-08 08:36:02', '0', NULL, NULL, NULL, '', 'MRN-1004', 'Registered', 'Mary Jane', 'Jane', 'Test', '1', '0', 'Staff', '', '', NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `payments`;
CREATE TABLE payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    );

INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('79', 'OR-1001', 'BILL-1003', 'Meryll Joy Evangelista', '150.00', '150.00', '0.00', 'Cash', '—', 'Jae Silvano Evangelista', '2026-10-29', 'Oct 29, 2026 · 13:35', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]');
INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('80', 'OR-1002', 'BILL-1002', 'Meryll Joy Evangelista', '150.00', '150.00', '0.00', 'Cash', '—', 'Jae Silvano Evangelista', '2026-10-29', 'Oct 29, 2026 · 14:14', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"]]');
INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('81', 'OR-1003', 'BILL-1004', 'Meryll Joy Evangelista', '215.00', '215.00', '0.00', 'Cash', '—', 'Jae Silvano Evangelista', '2026-11-09', 'Nov 09, 2026 · 07:35', '[["Doctor Consultation Fee (Consultation)",1,"$150.00","$150.00"],["Lipid panel (Diagnostic Lab Order)",1,"$65.00","$65.00"]]');

DROP TABLE IF EXISTS `phone_otps`;
CREATE TABLE phone_otps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone VARCHAR(50) NOT NULL,
    patient_id VARCHAR(20) NOT NULL DEFAULT '',
    code_hash VARCHAR(255) NOT NULL,
    plain_code VARCHAR(10) NOT NULL DEFAULT '',
    purpose VARCHAR(50) NOT NULL DEFAULT 'verification',
    expires_at INT NOT NULL,
    used INT NOT NULL DEFAULT 0,
    created_at INT NOT NULL
);

INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('1', '09222222222', 'MRN-1003', '$2y$10$EdtlzeMg4fZNz5joKbKi5.mgd33Bm79rzgwY50VWCntVXHoyIRjsS', '652190', 'verification', '1794122115', '1', '1794121515');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('2', '09222222222', 'MRN-1003', '$2y$10$uuHUDH1/014QFBbMNjD3m.02H17FaNyyz2pmpYCT/GEOkGftEc8e2', '608253', 'verification', '1794123443', '1', '1794122843');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('3', '09222222222', 'MRN-1003', '$2y$10$vzixsbkJWDTi4e/Cl.wh6Ottces2XM7UsMfZrcEtprieXqisTa1.K', '284770', 'account_creation', '1794123497', '1', '1794122897');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('4', '09222222222', 'MRN-1004', '$2y$10$TXGn2uco1DeCVU0elWzyy.ierUSfvTcp.0k1xAZwk9xk19Vgg4dH6', '515040', 'verification', '1794123948', '1', '1794123348');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('5', '09222222222', 'MRN-1003', '$2y$10$wiERXRdHiGxNRRG9TI/GI.RKNKzIPRV9XJcC6R9Kje4N9Pj5lNNha', '768452', 'account_creation', '1794124020', '1', '1794123420');

DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE prescriptions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    );

INSERT INTO `prescriptions` (`id`, `rx_code`, `record_code`, `patient_id`, `patient_name`, `doctor_name`, `medication`, `dosage`, `frequency`, `duration`, `prescribed_date`, `status`) VALUES ('45', 'RX-B015B5', 'REC-346859', 'P-1001', 'Meryll Joy Evangelista', 'Dr. Meryll Silvano Evangelista', 'Ibuprofen', '10mg', 'Once daily', '30 days', 'Oct 29, 2026', 'Active');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role_name VARCHAR(60) NOT NULL UNIQUE,
        description VARCHAR(255) NOT NULL DEFAULT '',
        user_count INT NOT NULL DEFAULT 0,
        permissions_json TEXT NOT NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'shield'
    );

INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('269', 'Doctor', 'Clinical records and appointments', '1', '["View patients","Edit patients","View medical records","Edit medical records","Manage appointments","Manage admissions","Manage billing","View lab results"]', 'patient');
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('270', 'Staff', 'Registration, scheduling, admissions, and billing', '1', '["View patients","Edit patients","Manage appointments","Manage admissions","Manage billing"]', 'users');
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('271', 'Patient', 'Patient portal access for appointments, records, and billing', '4', '["View medical records","Manage appointments","View lab results"]', 'patient');
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('272', 'Cashier', 'Billing, payment collection, official receipts, and daily reports', '1', '["View patients","Manage billing"]', 'card');

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_number VARCHAR(40) NOT NULL UNIQUE,
        ward VARCHAR(100) NOT NULL DEFAULT 'General Ward',
        status VARCHAR(30) NOT NULL DEFAULT 'Available',
        patient_name VARCHAR(150) NOT NULL DEFAULT '—'
    );

INSERT INTO `rooms` (`id`, `room_number`, `ward`, `status`, `patient_name`) VALUES ('124', 'Room 101', 'General Ward', 'Available', '—');
INSERT INTO `rooms` (`id`, `room_number`, `ward`, `status`, `patient_name`) VALUES ('125', 'Room 102', 'General Ward', 'Occupied', 'Meryll Joy Evangelista');

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id VARCHAR(30) NOT NULL,
    role VARCHAR(30) NOT NULL,
    created_at VARCHAR(40) NOT NULL,
    CONSTRAINT uk_user_role UNIQUE (user_id, role)
);

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('1', 'A-0001', 'Admin', 'Oct 29, 2026 · 07:35');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('2', 'D-1001', 'Doctor', 'Oct 29, 2026 · 09:01');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('3', 'P-1001', 'Patient', 'Oct 29, 2026 · 09:03');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('4', 'S-1001', 'Staff', 'Oct 29, 2026 · 10:04');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('5', 'C-1001', 'Cashier', 'Oct 29, 2026 · 10:49');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('6', 'P-1003', 'Patient', 'Nov 08, 2026 · 08:33');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('14', 'C-1001', 'Patient', '2026-11-08 09:34:27');

