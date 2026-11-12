-- Database Backup before MRN 6-digit Migration
-- Timestamp: 2026-11-08 16:33:51

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` varchar(30) NOT NULL,
  `user_role_label` varchar(40) NOT NULL DEFAULT 'Patient',
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `department` varchar(100) NOT NULL DEFAULT '',
  `specialty` varchar(120) NOT NULL DEFAULT '',
  `license_number` varchar(60) NOT NULL DEFAULT '',
  `last_login` varchar(60) NOT NULL DEFAULT 'Never',
  `created_at` varchar(40) NOT NULL,
  `birthdate` varchar(40) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `phone_verified` int(11) NOT NULL DEFAULT 0,
  `address` text DEFAULT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Philippines',
  `province` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `barangay` varchar(100) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `postal_code` varchar(30) NOT NULL DEFAULT '',
  `sex` varchar(30) NOT NULL DEFAULT 'Not specified',
  `force_password_change` int(11) NOT NULL DEFAULT 0,
  `set_password_token` varchar(120) NOT NULL DEFAULT '',
  `set_password_expires_at` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('1', 'A-0001', 'admin', 'solonmedea582@gmail.com', '$2y$10$kFvVu4zvw4DsraDDbqQiH.b9udFcHQQXT/GIbKOBa7fbtNEU2BKt2', 'Medea Solon', 'Admin', 'Admin', 'Active', '', '', '', 'Nov 08, 2026 · 15:14', 'Oct 29, 2026 302267 07:35', '', '09171234567', '1', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('2', 'D-1001', 'meryll', 'evangelistameryll@gmail.com', '$2y$10$Xkcqej5WDf7uEErflfhpKOg/W/PKRksPxXOMfZ7Msa3HTh7uESth.', 'Meryll Silvano Evangelista', 'Doctor', 'Doctor', 'Active', 'Internal Medicine', 'Internal Medicine', '12345678', 'Nov 07, 2026 · 17:09', 'Oct 29, 2026 · 09:01', '2002-04-25', '0933333333', '0', 'N/A, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Baliwasan', 'N/A', '7000', 'Female', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('3', 'P-1001', 'emeryll', 'evangelistamerylljoy@gmail.com', '$2y$10$6Vzx2N0F6d6EUUHJEKqOa.Czoqst/QrprDXFJIE2Mf0QqMa667MfO', 'Meryll Joy Evangelista', 'Patient', 'Patient', 'Active', '', '', '', 'Nov 07, 2026 · 17:50', 'Oct 29, 2026 · 09:03', 'Apr 25, 2007', '', '0', NULL, 'Philippines', '', '', '', '', '', 'Not specified', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('4', 'S-1001', 'jereme', 'evangelistajereme@gmail.com', '$2y$10$RTCglV4cqujJ6nnyH3F/be6MZVE8ZqCZGdDpate6/q9QSnUjrplEe', 'Jereme Silvano Evangelista', 'Staff', 'Staff', 'Active', '', '', '', 'Nov 08, 2026 · 16:23', 'Oct 29, 2026 · 10:04', '2002-05-29', '09704008922', '0', 'N/A, Canelar, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Canelar', 'N/A', '7000', 'Female', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('5', 'C-1001', 'jae', 'evangelistamerylljae@gmail.com', '$2y$10$uNapGdVaZl6dLOFGMh6dCel1JR4IlwOmdyL83r2FybfTve4hW2N9K', 'Jae Silvano Evangelista', 'Cashier', 'Cashier', 'Active', '', '', '', 'Nov 08, 2026 · 16:24', 'Oct 29, 2026 · 10:49', '2003-07-11', '09518474940', '0', 'N/A, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Baliwasan', 'N/A', '7000', 'Female', '0', '', '0');
INSERT INTO `accounts` (`id`, `user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `user_role_label`, `status`, `department`, `specialty`, `license_number`, `last_login`, `created_at`, `birthdate`, `phone`, `phone_verified`, `address`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `sex`, `force_password_change`, `set_password_token`, `set_password_expires_at`) VALUES ('7', 'P-1002', 'albardeen jumlani kamlon', NULL, '$2y$10$aKL8la9Stls.mlcW9ywa0OgS1r48D5KAjxTOz9wZ2e5IzOE.vnKbW', 'Albardeen Jumlani Kamlon', 'Patient', 'Patient', 'Active', '', '', '', 'Never', 'Nov 08, 2026 · 06:24', 'Mar 03, 2007', '09111111111', '1', 'Bulahan Drive, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', 'Philippines', '', '', '', '', '', 'Male', '0', '', '0');

DROP TABLE IF EXISTS `admissions`;
CREATE TABLE `admissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(150) NOT NULL,
  `room_number` varchar(40) NOT NULL,
  `ward` varchar(100) NOT NULL,
  `admitted_date` varchar(40) NOT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `reason` text NOT NULL,
  `admission_id` varchar(30) NOT NULL DEFAULT '',
  `mrn` varchar(30) NOT NULL DEFAULT '',
  `department` varchar(100) NOT NULL DEFAULT 'Internal Medicine',
  `bed_label` varchar(50) NOT NULL DEFAULT 'Bed A',
  `status` varchar(30) NOT NULL DEFAULT 'Admitted',
  `clinical_notes_json` longtext DEFAULT NULL,
  `discharge_order` text DEFAULT NULL,
  `final_diagnosis` text DEFAULT NULL,
  `billing_clearance` varchar(30) NOT NULL DEFAULT 'Pending',
  `clearance_reason` text DEFAULT NULL,
  `cleared_by` varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admissions` (`id`, `patient_name`, `room_number`, `ward`, `admitted_date`, `doctor_name`, `reason`, `admission_id`, `mrn`, `department`, `bed_label`, `status`, `clinical_notes_json`, `discharge_order`, `final_diagnosis`, `billing_clearance`, `clearance_reason`, `cleared_by`) VALUES ('168', 'Meryll Joy Evangelista', 'Room 102', 'General Ward', 'Oct 29, 2026', 'Dr. Meryll Silvano Evangelista', 'Assigned via Rooms', 'ADM-1068', '', 'Internal Medicine', 'Bed A', 'Admitted', '[{\"author\":\"Dr. Meryll Silvano Evangelista\",\"time\":\"Nov 07, 2026 \\u00b7 17:18\",\"note\":\"Patient afebrile, lungs clear to auscultation bilaterally. Vital signs stable. Regular diet tolerated well. Continue current IV therapy.\"}]', '', '', 'Pending', '', '');

DROP TABLE IF EXISTS `app_store`;
CREATE TABLE `app_store` (
  `store_key` varchar(64) NOT NULL,
  `store_value` longtext NOT NULL,
  `updated_at` varchar(40) NOT NULL,
  PRIMARY KEY (`store_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('admin_id_claimed', '1', '2026-10-29 07:35:38');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('archive', '[]', '2026-10-29 07:52:14');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('beds', '[{\"bed_id\":\"RM-101-A\",\"room_no\":\"101\",\"bed_code\":\"Bed A\",\"ward\":\"General Ward\",\"room_type\":\"Ward\",\"floor\":\"1st Floor\",\"rate\":1500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-101-B\",\"room_no\":\"101\",\"bed_code\":\"Bed B\",\"ward\":\"General Ward\",\"room_type\":\"Ward\",\"floor\":\"1st Floor\",\"rate\":1500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-101-C\",\"room_no\":\"101\",\"bed_code\":\"Bed C\",\"ward\":\"General Ward\",\"room_type\":\"Ward\",\"floor\":\"1st Floor\",\"rate\":1500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-102-A\",\"room_no\":\"102\",\"bed_code\":\"Bed A\",\"ward\":\"General Ward\",\"room_type\":\"Ward\",\"floor\":\"1st Floor\",\"rate\":1500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-102-B\",\"room_no\":\"102\",\"bed_code\":\"Bed B\",\"ward\":\"General Ward\",\"room_type\":\"Ward\",\"floor\":\"1st Floor\",\"rate\":1500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-201-A\",\"room_no\":\"201\",\"bed_code\":\"Bed A\",\"ward\":\"Pediatrics Ward\",\"room_type\":\"Semi-Private\",\"floor\":\"2nd Floor\",\"rate\":2500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-201-B\",\"room_no\":\"201\",\"bed_code\":\"Bed B\",\"ward\":\"Pediatrics Ward\",\"room_type\":\"Semi-Private\",\"floor\":\"2nd Floor\",\"rate\":2500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-202-A\",\"room_no\":\"202\",\"bed_code\":\"Bed A\",\"ward\":\"Maternity Ward\",\"room_type\":\"Semi-Private\",\"floor\":\"2nd Floor\",\"rate\":2500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-202-B\",\"room_no\":\"202\",\"bed_code\":\"Bed B\",\"ward\":\"Maternity Ward\",\"room_type\":\"Semi-Private\",\"floor\":\"2nd Floor\",\"rate\":2500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-301-A\",\"room_no\":\"301\",\"bed_code\":\"Bed A\",\"ward\":\"Medical Ward\",\"room_type\":\"Private\",\"floor\":\"3rd Floor\",\"rate\":4200,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"RM-302-A\",\"room_no\":\"302\",\"bed_code\":\"Bed A\",\"ward\":\"Cardiology Ward\",\"room_type\":\"Private\",\"floor\":\"3rd Floor\",\"rate\":4500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"ICU-01-A\",\"room_no\":\"ICU-1\",\"bed_code\":\"Bed A\",\"ward\":\"ICU\",\"room_type\":\"ICU\",\"floor\":\"2nd Floor\",\"rate\":7500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"ICU-01-B\",\"room_no\":\"ICU-1\",\"bed_code\":\"Bed B\",\"ward\":\"ICU\",\"room_type\":\"ICU\",\"floor\":\"2nd Floor\",\"rate\":7500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true},{\"bed_id\":\"STE-401-A\",\"room_no\":\"Suite 401\",\"bed_code\":\"Bed A\",\"ward\":\"Executive Wing\",\"room_type\":\"Suite\",\"floor\":\"4th Floor\",\"rate\":9500,\"status\":\"Vacant\",\"patient\":\"\",\"mrn\":\"\",\"admitted_date\":\"\",\"doctor\":\"\",\"maintenance_reason\":\"\",\"active\":true}]', '2026-11-08 16:24:23');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('doctor_schedules', '[]', '2026-11-08 16:24:23');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('notifications', '{\"0\":{\"title\":\"Payment receipt issued (OR-1003)\",\"detail\":\"Official receipt OR-1003 for $215.00 received via Cash for Bill #BILL-1004 (Balance: $0.00).\",\"time\":\"Just now\",\"unread\":true,\"role\":\"Patient\",\"user_id\":\"P-1001\"},\"1\":{\"title\":\"Payment receipt issued (OR-1002)\",\"detail\":\"Official receipt OR-1002 for $150.00 received via Cash for Bill #BILL-1002 (Balance: $0.00).\",\"time\":\"Just now\",\"unread\":true,\"role\":\"Patient\",\"user_id\":\"P-1001\"},\"2\":{\"title\":\"Payment receipt issued (OR-1001)\",\"detail\":\"Official receipt OR-1001 for $150.00 received via Cash for Bill #BILL-1003 (Balance: $0.00).\",\"time\":\"Just now\",\"unread\":true,\"role\":\"Patient\",\"user_id\":\"P-1001\"},\"3\":{\"title\":\"New visit summary available\",\"detail\":\"Dr. Dr. Meryll Silvano Evangelista completed your consultation summary and prescribed 1 medication(s).\",\"time\":\"Just now\",\"unread\":true,\"role\":\"Patient\",\"user_id\":\"P-1001\"},\"4\":{\"title\":\"New visit summary available\",\"detail\":\"Dr. Dr. Meryll Silvano Evangelista completed your consultation summary and prescribed 0 medication(s).\",\"time\":\"Just now\",\"unread\":true,\"role\":\"Patient\",\"user_id\":\"P-1001\"},\"Admin\":[],\"Staff\":[],\"Doctor\":[],\"Patient\":[]}', '2026-11-08 16:24:23');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('price_list', '[{\"code\":\"PRC-101\",\"name\":\"General Outpatient Consultation\",\"category\":\"Consultation\",\"department\":\"Internal Medicine\",\"price\":600,\"status\":\"Active\",\"id\":\"PRC-101\",\"active\":true},{\"code\":\"PRC-102\",\"name\":\"Specialist Consultation\",\"category\":\"Consultation\",\"department\":\"All Departments\",\"price\":850,\"status\":\"Active\",\"id\":\"PRC-102\",\"active\":true},{\"code\":\"PRC-103\",\"name\":\"Follow-up Consultation\",\"category\":\"Consultation\",\"department\":\"All Departments\",\"price\":500,\"status\":\"Active\",\"id\":\"PRC-103\",\"active\":true},{\"code\":\"PRC-104\",\"name\":\"Emergency Room Evaluation\",\"category\":\"Consultation\",\"department\":\"Emergency Medicine\",\"price\":1200,\"status\":\"Active\",\"id\":\"PRC-104\",\"active\":true},{\"code\":\"LAB-201\",\"name\":\"Complete Blood Count (CBC)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":350,\"status\":\"Active\",\"id\":\"LAB-201\",\"active\":true},{\"code\":\"LAB-202\",\"name\":\"Urinalysis (Routine)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":200,\"status\":\"Active\",\"id\":\"LAB-202\",\"active\":true},{\"code\":\"LAB-203\",\"name\":\"Fasting Blood Sugar (FBS)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":280,\"status\":\"Active\",\"id\":\"LAB-203\",\"active\":true},{\"code\":\"LAB-204\",\"name\":\"Lipid Panel (Cholesterol\\/Triglycerides)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":850,\"status\":\"Active\",\"id\":\"LAB-204\",\"active\":true},{\"code\":\"LAB-205\",\"name\":\"Metabolic Panel (Electrolytes\\/Creatinine)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":1100,\"status\":\"Active\",\"id\":\"LAB-205\",\"active\":true},{\"code\":\"LAB-206\",\"name\":\"Liver Function Test (SGPT\\/SGOT)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":750,\"status\":\"Active\",\"id\":\"LAB-206\",\"active\":true},{\"code\":\"LAB-207\",\"name\":\"Thyroid Function (TSH \\/ FT4)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Clinical Laboratory\",\"price\":1450,\"status\":\"Active\",\"id\":\"LAB-207\",\"active\":true},{\"code\":\"RAD-301\",\"name\":\"Chest X-Ray (PA View)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Radiology & Diagnostic Imaging\",\"price\":650,\"status\":\"Active\",\"id\":\"RAD-301\",\"active\":true},{\"code\":\"RAD-302\",\"name\":\"Electrocardiogram (ECG)\",\"category\":\"Lab & Diagnostics\",\"department\":\"Cardiology\",\"price\":600,\"status\":\"Active\",\"id\":\"RAD-302\",\"active\":true},{\"code\":\"RAD-303\",\"name\":\"2D Echocardiography with Doppler\",\"category\":\"Lab & Diagnostics\",\"department\":\"Cardiology\",\"price\":3500,\"status\":\"Active\",\"id\":\"RAD-303\",\"active\":true},{\"code\":\"RM-501\",\"name\":\"General Ward Bed (Per Day)\",\"category\":\"Room Rate\",\"department\":\"Inpatient\",\"price\":1500,\"status\":\"Active\",\"id\":\"RM-501\",\"active\":true},{\"code\":\"RM-502\",\"name\":\"Semi-Private Room (Per Day)\",\"category\":\"Room Rate\",\"department\":\"Inpatient\",\"price\":2500,\"status\":\"Active\",\"id\":\"RM-502\",\"active\":true},{\"code\":\"RM-503\",\"name\":\"Private Room (Per Day)\",\"category\":\"Room Rate\",\"department\":\"Inpatient\",\"price\":4200,\"status\":\"Active\",\"id\":\"RM-503\",\"active\":true},{\"code\":\"RM-504\",\"name\":\"Intensive Care Unit - ICU (Per Day)\",\"category\":\"Room Rate\",\"department\":\"Inpatient\",\"price\":7500,\"status\":\"Active\",\"id\":\"RM-504\",\"active\":true},{\"code\":\"RM-505\",\"name\":\"Executive Suite (Per Day)\",\"category\":\"Room Rate\",\"department\":\"Inpatient\",\"price\":9500,\"status\":\"Active\",\"id\":\"RM-505\",\"active\":true}]', '2026-11-08 16:24:23');
INSERT INTO `app_store` (`store_key`, `store_value`, `updated_at`) VALUES ('roles', '[[\"Doctor\",\"Clinical records and appointments\",0,[\"View patients\",\"Edit patients\",\"View medical records\",\"Edit medical records\",\"Manage appointments\",\"Manage admissions\",\"Manage billing\",\"View lab results\"],\"patient\"],[\"Front desk\",\"Registration and scheduling\",0,[\"View patients\",\"Edit patients\",\"Manage appointments\",\"Manage admissions\",\"Manage billing\"],\"users\"],[\"Patient\",\"Patient portal access for appointments, records, and billing\",0,[\"View medical records\",\"Manage appointments\",\"View lab results\"],\"patient\"]]', '2026-10-29 07:52:14');

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slot_time` varchar(20) NOT NULL,
  `patient_name` varchar(150) NOT NULL,
  `visit_type` varchar(80) NOT NULL DEFAULT 'Consultation',
  `status` varchar(30) NOT NULL DEFAULT 'Confirmed',
  `complaint` text NOT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `department` varchar(100) NOT NULL,
  `appt_date` varchar(40) NOT NULL,
  `patient_id` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `appointments` (`id`, `slot_time`, `patient_name`, `visit_type`, `status`, `complaint`, `doctor_name`, `department`, `appt_date`, `patient_id`) VALUES ('347', '08:00', 'Roxanna Agriche', 'Consultation', 'Scheduled', 'heartbreak', 'Dr. Meryll Silvano Evangelista', 'General Medicine', 'Nov 13, 2026', 'MRN-1003');
INSERT INTO `appointments` (`id`, `slot_time`, `patient_name`, `visit_type`, `status`, `complaint`, `doctor_name`, `department`, `appt_date`, `patient_id`) VALUES ('348', '09:00', 'Albardeen Jumlani Kamlon', 'Consultation', 'Confirmed', 'date', 'Dr. Meryll Silvano Evangelista', 'General Medicine', 'Nov 06, 2026', 'P-1002');
INSERT INTO `appointments` (`id`, `slot_time`, `patient_name`, `visit_type`, `status`, `complaint`, `doctor_name`, `department`, `appt_date`, `patient_id`) VALUES ('349', '09:00', 'Meryll Joy Evangelista', 'Consultation', 'Completed', 'knsfkm', 'Dr. Meryll Silvano Evangelista', 'General Medicine', 'Oct 30, 2026', 'P-1001');

DROP TABLE IF EXISTS `archive_records`;
CREATE TABLE `archive_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `archive_code` varchar(30) NOT NULL,
  `subject_name` varchar(150) NOT NULL,
  `record_type` varchar(60) NOT NULL,
  `archived_date` varchar(40) NOT NULL,
  `archived_by` varchar(150) NOT NULL,
  `payload_json` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `archive_code` (`archive_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `archive_records` (`id`, `archive_code`, `subject_name`, `record_type`, `archived_date`, `archived_by`, `payload_json`) VALUES ('2', '#ARC-1001', 'Medea Solon (P-1002)', 'Patient', 'Nov 04, 2026', 'Jereme Silvano Evangelista', '{\"kind\":\"patient\",\"data\":{\"id\":47,\"patient_id\":\"P-1002\",\"full_name\":\"Medea Solon\",\"birthdate\":\"Apr 25, 2007\",\"phone\":\"09111111111\",\"insurance_no\":\"HIN-000-00\",\"avatar\":\"MS\",\"sex\":\"Female\",\"blood_type\":\"Not specified\",\"address\":\"fszfdz\",\"created_at\":\"2026-11-04 15:20:38\",\"country\":\"Philippines\",\"province\":\"\",\"city\":\"\",\"barangay\":\"\",\"street\":\"\",\"postal_code\":\"\",\"phone_verified\":0,\"status\":\"Active\"},\"account\":null}');

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `actor_name` varchar(150) NOT NULL,
  `action` varchar(40) NOT NULL,
  `table_name` varchar(60) NOT NULL,
  `record_id` varchar(60) NOT NULL,
  `logged_at` varchar(60) NOT NULL,
  `actor_role` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5456 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5390', 'Medea Solon', 'Created', 'accounts', '#A-0001', 'Oct 29, 2026 302267 07:35', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5391', 'Medea Solon', 'Created', 'users', '#D-1001', 'Oct 29, 2026 302267 09:01', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5392', 'Meryll Joy Evangelista', 'Created', 'accounts', '#P-1001', 'Oct 29, 2026 302267 09:03', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5393', 'Medea Solon', 'Created', 'rooms', 'Room 102', 'Oct 29, 2026 302267 09:35', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5394', 'Medea Solon', 'Created', 'users', '#S-1001', 'Oct 29, 2026 302267 10:04', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5395', 'Jereme Silvano Evangelista', 'Created', 'rooms', 'Room 101', 'Oct 29, 2026 302267 10:33', 'Front desk');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5396', 'Medea Solon', 'Created', 'users', '#C-1001', 'Oct 29, 2026 302267 10:49', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5397', 'Meryll Joy Evangelista', 'Created', 'appointments', '#APT-4296', 'Oct 29, 2026 302267 11:06', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5398', 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-347C0C', 'Oct 29, 2026 302267 11:09', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5399', 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-709B8C', 'Oct 29, 2026 302267 11:39', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5400', 'Dr. Meryll Silvano Evangelista', 'Created', 'medical_records', '#REC-346859', 'Oct 29, 2026 302267 11:52', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5401', 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1001', 'Oct 29, 2026 302267 13:35', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5402', 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1002', 'Oct 29, 2026 302267 14:14', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5403', 'Jereme Silvano Evangelista', 'Created', 'patients', '#P-1002', 'Nov 04, 2026 · 11:15', 'Front desk');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5404', 'Jereme Silvano Evangelista', 'Deleted', 'patients', '#P-1002', 'Nov 04, 2026 · 15:20', 'Front desk');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5405', 'Jereme Silvano Evangelista', 'Created', 'patients', '#P-1002', 'Nov 04, 2026 · 16:16', 'Front desk');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5406', 'Jae Silvano Evangelista', 'Collected', 'payments', 'OR-1003', 'Nov 04, 2026 · 16:29', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5407', 'Medea Solon', 'Sign in', 'accounts', '#A-0001', 'Nov 04, 2026 · 17:59', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5408', 'Administrator', 'Sign in', 'accounts', '#C-1001', 'Nov 04, 2026 · 18:02', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5409', 'Administrator', 'Sign in', 'accounts', '#S-1001', 'Nov 05, 2026 · 04:48', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5410', 'Administrator', 'Sign in', 'accounts', '#S-1001', 'Nov 05, 2026 · 05:14', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5411', 'Jereme Silvano Evangelista', 'Created', 'appointments', '#APT-2197', 'Nov 05, 2026 · 05:27', 'Front desk');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5412', 'Administrator', 'Sign in', 'accounts', '#P-1001', 'Nov 05, 2026 · 07:34', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5413', 'Administrator', 'Sign in', 'accounts', '#S-1001', 'Nov 05, 2026 · 08:04', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5414', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 06, 2026 · 16:56', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5415', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 07, 2026 · 02:11', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5416', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 07, 2026 · 02:16', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5417', 'Meryll Silvano Evangelista acting as Doctor', 'Sign in', 'accounts', '#D-1001', 'Nov 07, 2026 · 02:38', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5418', 'Dr. Meryll Silvano Evangelista', 'Added progress note', 'admissions', 'Meryll Joy Evangelista', 'Nov 07, 2026 · 02:42', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5419', 'Jae Silvano Evangelista acting as Cashier', 'Sign in', 'accounts', '#C-1001', 'Nov 07, 2026 · 02:52', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5420', 'Medea Solon', 'Sign in', 'accounts', '#A-0001', 'Nov 07, 2026 · 15:58', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5421', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 07, 2026 · 16:18', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5422', 'Jereme Silvano Evangelista', 'Quick add provisional', 'patients', 'PROV-1001', 'Nov 07, 2026 · 16:57', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5423', 'Jereme Silvano Evangelista', 'Booked', 'appointments', '#APT-4500', 'Nov 07, 2026 · 16:58', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5424', 'Meryll Silvano Evangelista acting as Doctor', 'Sign in', 'accounts', '#D-1001', 'Nov 07, 2026 · 17:09', 'Doctor');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5425', 'Jae Silvano Evangelista acting as Cashier', 'Sign in', 'accounts', '#C-1001', 'Nov 07, 2026 · 17:28', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5426', 'Meryll Joy Evangelista acting as Patient', 'Sign in', 'accounts', '#P-1001', 'Nov 07, 2026 · 17:50', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5427', 'Jae Silvano Evangelista', 'Collected payment (Cash)', 'payments', 'OR-1004', 'Nov 07, 2026 · 18:15', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5428', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 07, 2026 · 18:18', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5429', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 07, 2026 · 18:22', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5430', 'Medea Solon', 'Sign in', 'accounts', '#A-0001', 'Nov 07, 2026 · 18:47', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5431', 'Medea Solon', 'Created Staff account (Pending activatio', 'users', '#S-1002', 'Nov 07, 2026 · 18:55', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5432', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 08, 2026 · 03:45', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5433', 'Medea Solon', 'Sign in', 'accounts', '#A-0001', 'Nov 08, 2026 · 03:46', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5434', 'Medea Solon', 'Edited Staff account [phone, birthdate, ', 'users', 'evangelistajereme@gmail.com', 'Nov 08, 2026 · 03:50', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5435', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 08, 2026 · 04:33', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5436', 'Jereme Silvano Evangelista', 'Registered patient', 'patients', 'MRN-10000', 'Nov 08, 2026 · 04:59', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5437', 'Albardeen Jumlani Kamlon acting as Patient', 'Activated account', 'accounts', '#P-1002', 'Nov 08, 2026 · 06:24', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5438', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 08, 2026 · 13:09', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5439', 'Medea Solon acting as Admin', 'Sign in', 'accounts', '#A-0001', 'Nov 08, 2026 · 13:13', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5440', 'Medea Solon acting as Admin', 'Edited Cashier account [phone, birthdate', 'users', 'evangelistamerylljae@gmail.com', 'Nov 08, 2026 · 13:15', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5441', 'Jae Silvano Evangelista acting as Cashier', 'Sign in', 'accounts', '#C-1001', 'Nov 08, 2026 · 13:16', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5442', 'Medea Solon acting as Admin', 'Sign in', 'accounts', '#A-0001', 'Nov 08, 2026 · 13:17', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5443', 'Medea Solon acting as Admin', 'Sign in', 'accounts', '#A-0001', 'Nov 08, 2026 · 15:14', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5444', 'Medea Solon acting as Admin', 'Edited Doctor account [phone, birthdate,', 'users', 'evangelistameryll@gmail.com', 'Nov 08, 2026 · 15:17', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5445', 'Medea Solon acting as Admin', 'Edited Staff account [province, city, ba', 'users', 'evangelistajereme@gmail.com', 'Nov 08, 2026 · 15:17', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5446', 'Medea Solon acting as Admin', 'Edited Cashier account [province, city, ', 'users', 'evangelistamerylljae@gmail.com', 'Nov 08, 2026 · 15:18', 'Administrator');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5447', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 08, 2026 · 15:18', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5448', 'Jereme Silvano Evangelista acting as Staff', 'Registered patient', 'patients', 'MRN-10001', 'Nov 08, 2026 · 16:07', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5449', 'Jae Silvano Evangelista acting as Patient', 'Linked patient profile', 'patient_account_links', '#MRN-10001', 'Nov 08, 2026 · 16:12', 'Patient');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5450', 'Jae Silvano Evangelista acting as Cashier', 'Sign in', 'accounts', '#C-1001', 'Nov 08, 2026 · 16:12', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5451', 'Jae Silvano Evangelista acting as Cashier', 'Requested patient record link OTP', 'patient_account_links', 'MRN-1001', 'Nov 08, 2026 · 16:22', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5452', 'Jae Silvano Evangelista acting as Cashier', 'Resent patient record link OTP', 'patient_account_links', 'MRN-1001', 'Nov 08, 2026 · 16:22', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5453', 'Jae Silvano Evangelista acting as Cashier', 'Linked patient record (Self)', 'patient_account_links', 'MRN-1001', 'Nov 08, 2026 · 16:22', 'Cashier');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5454', 'Jereme Silvano Evangelista acting as Staff', 'Sign in', 'accounts', '#S-1001', 'Nov 08, 2026 · 16:23', 'Staff');
INSERT INTO `audit_logs` (`id`, `actor_name`, `action`, `table_name`, `record_id`, `logged_at`, `actor_role`) VALUES ('5455', 'Jae Silvano Evangelista acting as Cashier', 'Sign in', 'accounts', '#C-1001', 'Nov 08, 2026 · 16:24', 'Cashier');

DROP TABLE IF EXISTS `bills`;
CREATE TABLE `bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(150) NOT NULL,
  `amount` varchar(40) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Unpaid',
  `issued_date` varchar(40) NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `bill_no` varchar(40) NOT NULL DEFAULT '',
  `paid_amount` varchar(40) NOT NULL DEFAULT '0',
  `items_json` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=593 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`) VALUES ('589', 'Meryll Joy Evangelista', '₱240.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1001', '240.00', '[[\"Outpatient consultation & physician fee\",1,\"$156.00\",\"$156.00\"],[\"Laboratory & diagnostic services\",1,\"$84.00\",\"$84.00\"]]');
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`) VALUES ('590', 'Meryll Joy Evangelista', '₱150.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1002', '150.00', '[[\"Doctor Consultation Fee (Consultation)\",1,\"$150.00\",\"$150.00\"]]');
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`) VALUES ('591', 'Meryll Joy Evangelista', '₱150.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1003', '150.00', '[[\"Doctor Consultation Fee (Consultation)\",1,\"$150.00\",\"$150.00\"]]');
INSERT INTO `bills` (`id`, `patient_name`, `amount`, `status`, `issued_date`, `receipt_path`, `bill_no`, `paid_amount`, `items_json`) VALUES ('592', 'Meryll Joy Evangelista', '₱215.00', 'Paid', 'Oct 29, 2026', NULL, 'BILL-1004', '215.00', '[[\"Doctor Consultation Fee (Consultation)\",1,\"$150.00\",\"$150.00\"],[\"Lipid panel (Diagnostic Lab Order)\",1,\"$65.00\",\"$65.00\"]]');

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `head_doctor` varchar(150) NOT NULL DEFAULT 'Unassigned',
  `staff_count` varchar(20) NOT NULL DEFAULT '0',
  `location` varchar(100) NOT NULL DEFAULT 'Main Wing',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=910 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('901', 'Internal Medicine', 'Dr. Rafael Lorenzo', '6', '2nd Floor · Main Medical Wing');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('902', 'Cardiology', 'Dr. Elena Santos', '4', '2nd Floor · Heart & Vascular Center');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('903', 'Pediatrics & Neonatology', 'Dr. Camille Navarro', '5', '3rd Floor · Maternal & Child Wing');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('904', 'Obstetrics & Gynecology', 'Dr. Patricia Mendoza', '5', '3rd Floor · Maternal & Child Wing');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('905', 'General Surgery', 'Dr. Marco Villanueva', '4', '2nd Floor · Surgical Pavilion');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('906', 'Orthopedics', 'Dr. Antonio Reyes', '3', '2nd Floor · Bone & Joint Center');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('907', 'Emergency Medicine', 'Dr. Gabriel Cruz', '8', 'Ground Floor · ER Complex');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('908', 'Nephrology & Hemodialysis', 'Dr. Sofia Lim', '4', 'Ground Floor · Dialysis Center');
INSERT INTO `departments` (`id`, `name`, `head_doctor`, `staff_count`, `location`) VALUES ('909', 'Radiology & Diagnostic Imaging', 'Dr. Victor Alvarez', '3', 'Ground Floor · Diagnostic Wing');

DROP TABLE IF EXISTS `discharges`;
CREATE TABLE `discharges` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_name` varchar(150) NOT NULL,
  `room_number` varchar(40) NOT NULL,
  `ward` varchar(100) NOT NULL,
  `discharged_date` varchar(40) NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `discharged_ts` bigint(20) NOT NULL DEFAULT 0,
  `mrn` varchar(30) NOT NULL DEFAULT '',
  `bed_label` varchar(50) NOT NULL DEFAULT 'Bed A',
  `doctor_name` varchar(150) NOT NULL DEFAULT '',
  `admitted_date` varchar(40) NOT NULL DEFAULT '',
  `discharge_type` varchar(80) NOT NULL DEFAULT 'Recovered / Improved',
  `final_diagnosis` text DEFAULT NULL,
  `length_of_stay` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `lab_results`;
CREATE TABLE `lab_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `test_code` varchar(30) NOT NULL,
  `patient_name` varchar(150) NOT NULL,
  `test_name` varchar(120) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `collected_date` varchar(40) NOT NULL,
  `record_code` varchar(30) NOT NULL DEFAULT '',
  `patient_id` varchar(20) NOT NULL DEFAULT '',
  `doctor_name` varchar(150) NOT NULL DEFAULT '',
  `priority` varchar(30) NOT NULL DEFAULT 'Routine',
  `clinical_notes` text DEFAULT NULL,
  `results_json` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lab_results` (`id`, `test_code`, `patient_name`, `test_name`, `status`, `collected_date`, `record_code`, `patient_id`, `doctor_name`, `priority`, `clinical_notes`, `results_json`) VALUES ('151', 'LIP-6196', 'Meryll+Joy+Evangelista', 'Lipid panel', 'Done', 'Oct 29, 2026', '', 'P-1001', '', 'Routine', '', '');

DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE `medical_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `record_code` varchar(30) NOT NULL,
  `patient_name` varchar(150) NOT NULL,
  `visit_date` varchar(40) NOT NULL,
  `visit_type` varchar(80) NOT NULL,
  `complaint` text NOT NULL,
  `notes` text NOT NULL,
  `diagnoses_json` text NOT NULL,
  `prescriptions_json` text NOT NULL,
  `lab_orders_json` text NOT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Completed',
  `patient_id` varchar(20) NOT NULL DEFAULT '',
  `doctor_id` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=448 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES ('445', 'REC-709B8C', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'knsfkm', '', '[]', '[]', '[]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', 'D-1001');
INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES ('446', 'REC-346859', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'knsfkm', '', '[]', '[[\"Ibuprofen\",\"10mg\",\"Once daily\",\"30 days\"]]', '[]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', 'D-1001');
INSERT INTO `medical_records` (`id`, `record_code`, `patient_name`, `visit_date`, `visit_type`, `complaint`, `notes`, `diagnoses_json`, `prescriptions_json`, `lab_orders_json`, `doctor_name`, `status`, `patient_id`, `doctor_id`) VALUES ('447', 'REC-347C0C', 'Meryll Joy Evangelista', 'Oct 29, 2026', 'Consultation', 'Persistent cough', 'ksmlc', '[]', '[]', '[[\"Lipid panel\",\"Urgent\"]]', 'Dr. Meryll Silvano Evangelista', 'Completed', 'P-1001', '');

DROP TABLE IF EXISTS `medications`;
CREATE TABLE `medications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `category` varchar(80) NOT NULL DEFAULT 'General',
  `strength` varchar(60) NOT NULL DEFAULT '',
  `stock` varchar(60) NOT NULL DEFAULT '0 units',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` int(11) NOT NULL,
  `used` int(11) NOT NULL DEFAULT 0,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `password_resets` (`id`, `email`, `code_hash`, `expires_at`, `used`, `created_at`) VALUES ('2', 'solonmedea582@gmail.com', '$2y$10$zxzFQo2j5pxNXooaORHUGO3XyweSScq0Tds89a32GrY3HDtuCTJJq', '1793766494', '1', '1793765594');

DROP TABLE IF EXISTS `patient_account_links`;
CREATE TABLE `patient_account_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_uid` varchar(30) NOT NULL,
  `patient_id` varchar(30) NOT NULL,
  `mrn` varchar(30) NOT NULL,
  `patient_name` varchar(150) NOT NULL,
  `relationship_label` varchar(60) NOT NULL DEFAULT 'Self',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `linked_at` varchar(40) NOT NULL,
  `linked_by` varchar(60) NOT NULL DEFAULT 'Self-Service',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_acc_patient` (`account_uid`,`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `patient_account_links` (`id`, `account_uid`, `patient_id`, `mrn`, `patient_name`, `relationship_label`, `is_primary`, `linked_at`, `linked_by`) VALUES ('2', 'C-1001', 'MRN-10001', 'MRN-10001', 'Meryll Jae Evangelista', 'Dependent', '0', '2026-11-08 16:12:04', 'Activation Link Self-Service');
INSERT INTO `patient_account_links` (`id`, `account_uid`, `patient_id`, `mrn`, `patient_name`, `relationship_label`, `is_primary`, `linked_at`, `linked_by`) VALUES ('4', 'C-1001', 'MRN-1001', 'MRN-1001', 'Meryll Joy Evangelista', 'Self', '1', '2026-11-08 16:22:55', 'Profile Patient Link');

DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `birthdate` varchar(40) NOT NULL DEFAULT '—',
  `phone` varchar(40) DEFAULT NULL,
  `insurance_no` varchar(60) NOT NULL DEFAULT '—',
  `avatar` varchar(10) NOT NULL DEFAULT 'PT',
  `sex` varchar(30) NOT NULL DEFAULT 'Not specified',
  `blood_type` varchar(30) NOT NULL DEFAULT 'Not specified',
  `address` varchar(255) NOT NULL DEFAULT '—',
  `created_at` varchar(40) NOT NULL,
  `country` varchar(100) NOT NULL DEFAULT 'Philippines',
  `province` varchar(100) NOT NULL DEFAULT '',
  `city` varchar(100) NOT NULL DEFAULT '',
  `barangay` varchar(100) NOT NULL DEFAULT '',
  `street` varchar(150) NOT NULL DEFAULT '',
  `postal_code` varchar(20) NOT NULL DEFAULT '',
  `phone_verified` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `email` varchar(150) NOT NULL DEFAULT '',
  `mrn` varchar(30) NOT NULL DEFAULT '',
  `registration_status` varchar(30) NOT NULL DEFAULT 'Registered',
  `first_name` varchar(80) NOT NULL DEFAULT '',
  `middle_name` varchar(80) NOT NULL DEFAULT '',
  `last_name` varchar(80) NOT NULL DEFAULT '',
  `id_checked` int(11) NOT NULL DEFAULT 0,
  `consent_given` int(11) NOT NULL DEFAULT 0,
  `created_by` varchar(120) NOT NULL DEFAULT 'Staff',
  `upgraded_by` varchar(120) NOT NULL DEFAULT '',
  `upgraded_at` varchar(50) NOT NULL DEFAULT '',
  `no_phone` tinyint(1) NOT NULL DEFAULT 0,
  `contact_name` varchar(150) DEFAULT NULL,
  `contact_relationship` varchar(80) DEFAULT NULL,
  `contact_phone` varchar(40) DEFAULT NULL,
  `contact_first_name` varchar(80) DEFAULT NULL,
  `contact_middle_name` varchar(80) DEFAULT NULL,
  `contact_last_name` varchar(80) DEFAULT NULL,
  `contact_relationship_other` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB AUTO_INCREMENT=593 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('587', 'MRN-1001', 'Meryll Joy Evangelista', 'Apr 25, 2007', '09171234567', 'HIN-786-755', 'MJ', 'Female', 'O+', 'Veterans Ave. Ext., Zamboanga City', '2026-11-04 05:55:15', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Tetuan', 'Veterans Ave', '7000', '1', 'Active', '', 'MRN-1001', 'Registered', 'Meryll', 'Joy', 'Evangelista', '0', '0', 'Staff', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('588', 'MRN-1002', 'Albardeen Jumlani Kamlon', 'Mar 03, 2007', '09111111111', '—', 'AJ', 'Male', 'Not specified', 'Bulahan Drive, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', '2026-11-04 16:16:00', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Baliwasan', 'Bulahan Drive', '7000', '1', 'Active', '', 'MRN-1002', 'Registered', 'Albardeen', 'Jumlani', 'Kamlon', '1', '1', 'Staff', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('589', 'MRN-1003', 'Roxanna Agriche', 'Apr 25, 2007', '09606668371', '—', 'RA', 'Female', 'Not specified', 'N/A, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', '2026-11-07 18:31:38', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Baliwasan', 'N/A', '7000', '1', 'Active', '', 'MRN-1003', 'Registered', 'Roxanna', '', 'Agriche', '0', '0', 'Staff', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('590', 'MRN-9999', 'Test OtherContact', '1990-01-01', NULL, 'HIN-99', 'TO', 'Male', 'O+', 'Tetuan, Zamboanga City', '2026-11-08 04:57:50', 'Philippines', '', '', '', '', '', '0', 'Active', '', 'MRN-9999', 'Registered', 'Test', '', 'OtherContact', '0', '0', 'Staff', '', '', '1', 'Maria Clara Santos', 'Other (Neighbor)', '09170001122', 'Maria', 'Clara', 'Santos', 'Neighbor');
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('591', 'MRN-10000', 'Jaymhie Evangelista', 'Oct 30, 2003', NULL, '—', 'JE', 'Female', 'Not specified', 'N/A, North Poblacion, Naga, Zamboanga Sibugay, Philippines, 7004', '2026-11-08 04:59:38', 'Philippines', 'Zamboanga Sibugay', 'Naga', 'North Poblacion', 'N/A', '7004', '0', 'Active', '', 'MRN-10000', 'Registered', 'Jaymhie', '', 'Evangelista', '1', '0', 'Staff', '', '', '1', 'Maribeth Silvano Evangelista', 'Parent', '09700495377', 'Maribeth', 'Silvano', 'Evangelista', NULL);
INSERT INTO `patients` (`id`, `patient_id`, `full_name`, `birthdate`, `phone`, `insurance_no`, `avatar`, `sex`, `blood_type`, `address`, `created_at`, `country`, `province`, `city`, `barangay`, `street`, `postal_code`, `phone_verified`, `status`, `email`, `mrn`, `registration_status`, `first_name`, `middle_name`, `last_name`, `id_checked`, `consent_given`, `created_by`, `upgraded_by`, `upgraded_at`, `no_phone`, `contact_name`, `contact_relationship`, `contact_phone`, `contact_first_name`, `contact_middle_name`, `contact_last_name`, `contact_relationship_other`) VALUES ('592', 'MRN-10001', 'Meryll Jae Evangelista', 'Jul 11, 2003', '09518474940', '—', 'MJ', 'Female', 'Not specified', 'N/A, Baliwasan, Zamboanga City, Zamboanga del Sur, Philippines, 7000', '2026-11-08 16:07:34', 'Philippines', 'Zamboanga del Sur', 'Zamboanga City', 'Baliwasan', 'N/A', '7000', '1', 'Active', '', 'MRN-10001', 'Registered', 'Meryll Jae', 'Jae', 'Evangelista', '1', '0', 'Staff', '', '', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(40) NOT NULL,
  `bill_no` varchar(40) NOT NULL,
  `patient_name` varchar(150) NOT NULL,
  `amount_paid` varchar(40) NOT NULL DEFAULT '0.00',
  `amount_received` varchar(40) NOT NULL DEFAULT '0.00',
  `change_due` varchar(40) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(40) NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(80) NOT NULL DEFAULT '',
  `cashier_name` varchar(150) NOT NULL DEFAULT 'Hospital Cashier',
  `paid_date` varchar(20) NOT NULL,
  `paid_at` varchar(60) NOT NULL,
  `items_json` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=491 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('487', 'OR-1001', 'BILL-1003', 'Meryll Joy Evangelista', '150.00', '150.00', '0.00', 'Cash', '342200224', 'Jae Silvano Evangelista', '2026-10-29', 'Oct 29, 2026 302267 13:35', '[[\"Doctor Consultation Fee (Consultation)\",1,\"$150.00\",\"$150.00\"]]');
INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('488', 'OR-1002', 'BILL-1002', 'Meryll Joy Evangelista', '150.00', '150.00', '0.00', 'Cash', '342200224', 'Jae Silvano Evangelista', '2026-10-29', 'Oct 29, 2026 302267 14:14', '[[\"Doctor Consultation Fee (Consultation)\",1,\"$150.00\",\"$150.00\"]]');
INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('489', 'OR-1003', 'BILL-1004', 'Meryll Joy Evangelista', '215.00', '215.00', '0.00', 'Cash', '—', 'Jae Silvano Evangelista', '2026-11-04', 'Nov 04, 2026 · 16:29', '[[\"Doctor Consultation Fee (Consultation)\",1,\"$150.00\",\"$150.00\"],[\"Lipid panel (Diagnostic Lab Order)\",1,\"$65.00\",\"$65.00\"]]');
INSERT INTO `payments` (`id`, `receipt_no`, `bill_no`, `patient_name`, `amount_paid`, `amount_received`, `change_due`, `payment_method`, `reference_no`, `cashier_name`, `paid_date`, `paid_at`, `items_json`) VALUES ('490', 'OR-1004', 'BILL-1001', 'Meryll Joy Evangelista', '240.00', '240.00', '0.00', 'Cash', '—', 'Jae Silvano Evangelista', '2026-11-07', 'Nov 07, 2026 · 18:15', '[[\"Outpatient consultation & physician fee\",1,\"$156.00\",\"$156.00\"],[\"Laboratory & diagnostic services\",1,\"$84.00\",\"$84.00\"]]');

DROP TABLE IF EXISTS `phone_otps`;
CREATE TABLE `phone_otps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(150) NOT NULL,
  `patient_id` varchar(20) NOT NULL DEFAULT '',
  `code_hash` varchar(255) NOT NULL,
  `plain_code` varchar(10) NOT NULL DEFAULT '',
  `purpose` varchar(50) NOT NULL DEFAULT 'verification',
  `expires_at` int(11) NOT NULL,
  `used` int(11) NOT NULL DEFAULT 0,
  `created_at` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('1', '+639178889999', 'P-TEST', '$2y$10$ooRSlL8i.ikNz0zmZ5rdteRB.asqwpUfkN5b67ZMu1Y87beSUsLT2', '191056', 'verification', '1793790728', '1', '1793790128');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('4', '09111111111', 'P-1002', '$2y$10$zrdt0dlvmR.rkIIKAMftweWt9N2jc22yyyvoIHrpOF693ounEZqIy', '275896', 'verification', '1793805936', '1', '1793805336');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('6', '09191234567', 'S-1001', '$2y$10$da.DfyrbKJzbFUXANn/swOu4HD0pEJhTZ8W7z0dmrfsu5RA/DDUYq', '116824', 'password_reset', '1793808242', '1', '1793807642');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('7', '09111111111', 'P-1003', '$2y$10$YnBfoDndI4bQ4wz12ubos./O1pdDkOyDCz.TwPuZCFTvPwEgmiOTq', '449425', 'verification', '1793851183', '1', '1793850583');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('12', '09606668371', 'MRN-1003', '$2y$10$QOUhtutPC73JI4LmWaKC6.ciyjE/ZKeyUqoGSGFI8JrAiMhY7MqUy', '969895', 'verification', '1794072948', '1', '1794072348');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('13', '09606668371', 'MRN-1004', '$2y$10$4ZmP9bqvfWiwzlU38UnWxO3VWs3LZeGh9mucHJQwSYXC69H8RBBmm', '295372', 'verification', '1794073445', '1', '1794072845');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('14', '09171112233', 'MRN-NEW', '$2y$10$yf.1AZ1rs.Ga7SBuu.hj6.MXUNBC7hecZoDllAhgvBp0HVOC8X5Ki', '906557', 'verification', '1794107652', '1', '1794107052');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('15', '09171112233', 'MRN-NEW', '$2y$10$hdRklm/b72rOEkvjP4ApOeStCKwDAyk3QopRT/IungB.pQON1Jjxy', '806762', 'verification', '1794107683', '1', '1794107083');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('16', '09171112233', 'MRN-NEW', '$2y$10$rA2N/a7q7597HM3YYkJmMeHd5W7COb/e/acZ3J3vJ79iu2hpLm6H6', '666321', 'verification', '1794107732', '1', '1794107132');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('17', '09171112233', 'MRN-NEW', '$2y$10$LewHjM8lfCagWaFFtDwXaO6b.D92k9/3vqmAQm44coi9mrH8oWwN2', '484180', 'verification', '1794107749', '1', '1794107149');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('18', '09171112233', 'MRN-NEW', '$2y$10$7usy5sB/.p3pHr.tCkY3GeaJzzWes5we85eXyBrdXFI49aAdmARUi', '452966', 'verification', '1794107759', '1', '1794107159');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('19', '09171112233', 'MRN-NEW', '$2y$10$uFnW93onhVQe1wQmtUpjxOZnXgYv4WqXOXBSykOImWvoDD0TA5TYu', '207612', 'verification', '1794107776', '1', '1794107176');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('20', '09171112233', 'MRN-NEW', '$2y$10$Jb.WmnzsizdCd1wIDN60TuRC.iCrSVOpQKJ3wC0NVAhBWtlyvy1gi', '757254', 'verification', '1794107784', '1', '1794107184');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('21', '09111111111', 'P-1002', '$2y$10$fdpseccvxIDU/mUfLj4UDud8ZMdcHJvjZLQbP5nFAuIIq5Ylo4fCq', '651239', 'account_creation', '1794112416', '1', '1794111816');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('22', '09111111111', 'P-1002', '$2y$10$eWaSyDRiAp8c9Hli9htGfOQBUl8zk6itXF2H7pVfilNuV3pu.pdj6', '425230', 'account_creation', '1794112509', '1', '1794111909');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('23', '09111111111', 'P-1002', '$2y$10$txGDVIqtZW6twCDMZ2.IWO7Dm.SQiyyFkdYFnWOxJeNvluoA5T1GS', '202873', 'account_creation', '1794116043', '1', '1794115443');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('24', 'albardeen.test@gmail.com', 'P-1002', '$2y$10$MaQjx3vssSQ9hO3LQLPcXeF7azQ0w9AMzhVyj2WAnWILy9ifLL6SW', '813606', 'profile_email_change', '1794116600', '1', '1794116000');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('25', '09518474940', 'MRN-10001', '$2y$10$rJ1KUHsIMm7tEaBcL7w4O./7E.d3KiyvPOdGADiPVGt/gnag5p6Ui', '782419', 'verification', '1794151024', '1', '1794150424');
INSERT INTO `phone_otps` (`id`, `phone`, `patient_id`, `code_hash`, `plain_code`, `purpose`, `expires_at`, `used`, `created_at`) VALUES ('26', '09518474940', 'MRN-10001', '$2y$10$DQ7dwXMqKyQv2xCB1ODmTuSJMbGAiItK3QNN196pN49BxFxvoPYe2', '979109', 'account_creation', '1794151284', '1', '1794150684');

DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rx_code` varchar(30) NOT NULL,
  `record_code` varchar(30) NOT NULL DEFAULT '',
  `patient_id` varchar(20) NOT NULL DEFAULT '',
  `patient_name` varchar(150) NOT NULL,
  `doctor_name` varchar(150) NOT NULL DEFAULT '',
  `medication` varchar(150) NOT NULL,
  `dosage` varchar(100) NOT NULL DEFAULT '',
  `frequency` varchar(100) NOT NULL DEFAULT '',
  `duration` varchar(100) NOT NULL DEFAULT '',
  `prescribed_date` varchar(40) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `prescriptions` (`id`, `rx_code`, `record_code`, `patient_id`, `patient_name`, `doctor_name`, `medication`, `dosage`, `frequency`, `duration`, `prescribed_date`, `status`) VALUES ('148', 'RX-B015B5', 'REC-346859', 'P-1001', 'Meryll Joy Evangelista', 'Dr. Meryll Silvano Evangelista', 'Ibuprofen', '10mg', 'Once daily', '30 days', 'Oct 29, 2026', 'Active');

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `user_count` int(11) NOT NULL DEFAULT 0,
  `permissions_json` text NOT NULL,
  `icon` varchar(40) NOT NULL DEFAULT 'shield',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=685 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('681', 'Doctor', 'Clinical records and appointments', '1', '[\"View patients\",\"Edit patients\",\"View medical records\",\"Edit medical records\",\"Manage appointments\",\"Manage admissions\",\"Manage billing\",\"View lab results\"]', 'patient');
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('682', 'Staff', 'Registration, scheduling, admissions, and billing', '1', '[\"View patients\",\"Edit patients\",\"Manage appointments\",\"Manage admissions\",\"Manage billing\"]', 'users');
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('683', 'Patient', 'Patient portal access for appointments, records, and billing', '6', '[\"View medical records\",\"Manage appointments\",\"View lab results\"]', 'patient');
INSERT INTO `roles` (`id`, `role_name`, `description`, `user_count`, `permissions_json`, `icon`) VALUES ('684', 'Cashier', 'Billing, payment collection, official receipts, and daily reports', '1', '[\"View patients\",\"Manage billing\"]', 'card');

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_number` varchar(40) NOT NULL,
  `ward` varchar(100) NOT NULL DEFAULT 'General Ward',
  `status` varchar(30) NOT NULL DEFAULT 'Available',
  `patient_name` varchar(150) NOT NULL DEFAULT '—',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`)
) ENGINE=InnoDB AUTO_INCREMENT=332 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rooms` (`id`, `room_number`, `ward`, `status`, `patient_name`) VALUES ('330', 'Room 101', 'General Ward', 'Occupied', '342200224');
INSERT INTO `rooms` (`id`, `room_number`, `ward`, `status`, `patient_name`) VALUES ('331', 'Room 102', 'General Ward', 'Occupied', 'Meryll Joy Evangelista');

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(30) NOT NULL,
  `role` varchar(30) NOT NULL,
  `created_at` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`,`role`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('1', 'A-0001', 'Admin', 'Oct 29, 2026 302267 07:35');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('2', 'D-1001', 'Doctor', 'Oct 29, 2026 · 09:01');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('3', 'P-1001', 'Patient', 'Oct 29, 2026 · 09:03');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('4', 'S-1001', 'Staff', 'Oct 29, 2026 · 10:04');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('5', 'C-1001', 'Cashier', 'Oct 29, 2026 · 10:49');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('6', 'P-1002', 'Patient', 'Nov 08, 2026 · 06:24');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('92', 'A-0001', 'Patient', '2026-11-08 15:51:18');
INSERT INTO `user_roles` (`id`, `user_id`, `role`, `created_at`) VALUES ('108', 'C-1001', 'Patient', '2026-11-08 16:12:04');

