<?php
declare(strict_types=1);

$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=medicore_records', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$pdo->exec("SET SESSION sql_mode = (SELECT CONCAT(IFNULL(@@sql_mode, ''), ',PIPES_AS_CONCAT'))");

echo "=== Running MySQL 3NF Normalization Migration ===\n";

$hasColumn = function(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
    $stmt->execute([':col' => $column]);
    return (bool) $stmt->fetch();
};

// 1. ADMISSIONS
echo "1. Migrating admissions...\n";
if (!$hasColumn($pdo, 'admissions', 'doctor_id')) {
    $pdo->exec("ALTER TABLE admissions ADD COLUMN doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'admissions', 'doctor_name')) {
    $pdo->exec("UPDATE admissions 
        SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = admissions.doctor_name OR ('Dr. ' || accounts.full_name) = admissions.doctor_name) LIMIT 1)");
    $pdo->exec("ALTER TABLE admissions DROP COLUMN doctor_name");
}
if ($hasColumn($pdo, 'admissions', 'patient_name')) {
    $pdo->exec("UPDATE admissions 
        SET mrn = (SELECT patient_id FROM patients WHERE patients.first_name || ' ' || (CASE WHEN patients.middle_name IS NOT NULL AND patients.middle_name != '' THEN patients.middle_name || ' ' ELSE '' END) || patients.last_name = admissions.patient_name LIMIT 1)
        WHERE mrn IS NULL OR mrn = ''");
    $pdo->exec("ALTER TABLE admissions DROP COLUMN patient_name");
}
if ($hasColumn($pdo, 'admissions', 'ward')) {
    $pdo->exec("ALTER TABLE admissions DROP COLUMN ward");
}

// 2. APPOINTMENTS
echo "2. Migrating appointments...\n";
if (!$hasColumn($pdo, 'appointments', 'doctor_id')) {
    $pdo->exec("ALTER TABLE appointments ADD COLUMN doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'appointments', 'doctor_name')) {
    $pdo->exec("UPDATE appointments 
        SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = appointments.doctor_name OR ('Dr. ' || accounts.full_name) = appointments.doctor_name) LIMIT 1)");
    $pdo->exec("ALTER TABLE appointments DROP COLUMN doctor_name");
}
if ($hasColumn($pdo, 'appointments', 'patient_name')) {
    $pdo->exec("ALTER TABLE appointments DROP COLUMN patient_name");
}

// 3. BILLS
echo "3. Migrating bills...\n";
if (!$hasColumn($pdo, 'bills', 'patient_id')) {
    $pdo->exec("ALTER TABLE bills ADD COLUMN patient_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'bills', 'patient_name')) {
    $pdo->exec("UPDATE bills 
        SET patient_id = (SELECT patient_id FROM patients WHERE patients.first_name || ' ' || (CASE WHEN patients.middle_name IS NOT NULL AND patients.middle_name != '' THEN patients.middle_name || ' ' ELSE '' END) || patients.last_name = bills.patient_name LIMIT 1)");
    $pdo->exec("ALTER TABLE bills DROP COLUMN patient_name");
}

// 4. DEPARTMENTS
echo "4. Migrating departments...\n";
if (!$hasColumn($pdo, 'departments', 'head_doctor_id')) {
    $pdo->exec("ALTER TABLE departments ADD COLUMN head_doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'departments', 'head_doctor')) {
    $pdo->exec("UPDATE departments 
        SET head_doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = departments.head_doctor OR ('Dr. ' || accounts.full_name) = departments.head_doctor) LIMIT 1)");
    $pdo->exec("ALTER TABLE departments DROP COLUMN head_doctor");
}
if ($hasColumn($pdo, 'departments', 'staff_count')) {
    $pdo->exec("ALTER TABLE departments DROP COLUMN staff_count");
}

// 5. DISCHARGES
echo "5. Migrating discharges...\n";
if (!$hasColumn($pdo, 'discharges', 'doctor_id')) {
    $pdo->exec("ALTER TABLE discharges ADD COLUMN doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'discharges', 'doctor_name')) {
    $pdo->exec("UPDATE discharges 
        SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = discharges.doctor_name OR ('Dr. ' || accounts.full_name) = discharges.doctor_name) LIMIT 1)");
    $pdo->exec("ALTER TABLE discharges DROP COLUMN doctor_name");
}
if ($hasColumn($pdo, 'discharges', 'patient_name')) {
    $pdo->exec("ALTER TABLE discharges DROP COLUMN patient_name");
}
if ($hasColumn($pdo, 'discharges', 'ward')) {
    $pdo->exec("ALTER TABLE discharges DROP COLUMN ward");
}
if ($hasColumn($pdo, 'discharges', 'length_of_stay')) {
    $pdo->exec("ALTER TABLE discharges DROP COLUMN length_of_stay");
}

// 6. LAB_RESULTS
echo "6. Migrating lab_results...\n";
if (!$hasColumn($pdo, 'lab_results', 'doctor_id')) {
    $pdo->exec("ALTER TABLE lab_results ADD COLUMN doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'lab_results', 'doctor_name')) {
    $pdo->exec("UPDATE lab_results 
        SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = lab_results.doctor_name OR ('Dr. ' || accounts.full_name) = lab_results.doctor_name) LIMIT 1)");
    $pdo->exec("ALTER TABLE lab_results DROP COLUMN doctor_name");
}
if ($hasColumn($pdo, 'lab_results', 'patient_name')) {
    $pdo->exec("ALTER TABLE lab_results DROP COLUMN patient_name");
}

// 7. MEDICAL_RECORDS
echo "7. Migrating medical_records...\n";
if (!$hasColumn($pdo, 'medical_records', 'doctor_id')) {
    $pdo->exec("ALTER TABLE medical_records ADD COLUMN doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'medical_records', 'doctor_name')) {
    $pdo->exec("UPDATE medical_records 
        SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = medical_records.doctor_name OR ('Dr. ' || accounts.full_name) = medical_records.doctor_name) LIMIT 1)
        WHERE doctor_id IS NULL OR doctor_id = ''");
    $pdo->exec("ALTER TABLE medical_records DROP COLUMN doctor_name");
}
if ($hasColumn($pdo, 'medical_records', 'patient_name')) {
    $pdo->exec("ALTER TABLE medical_records DROP COLUMN patient_name");
}

// 8. PATIENT_ACCOUNT_LINKS
echo "8. Migrating patient_account_links...\n";
if ($hasColumn($pdo, 'patient_account_links', 'patient_name')) {
    $pdo->exec("ALTER TABLE patient_account_links DROP COLUMN patient_name");
}
if ($hasColumn($pdo, 'patient_account_links', 'mrn')) {
    $pdo->exec("ALTER TABLE patient_account_links DROP COLUMN mrn");
}

// 9. PAYMENTS
echo "9. Migrating payments...\n";
if (!$hasColumn($pdo, 'payments', 'cashier_id')) {
    $pdo->exec("ALTER TABLE payments ADD COLUMN cashier_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'payments', 'cashier_name')) {
    $pdo->exec("UPDATE payments 
        SET cashier_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Cashier' AND accounts.full_name = payments.cashier_name LIMIT 1)");
    $pdo->exec("ALTER TABLE payments DROP COLUMN cashier_name");
}
if ($hasColumn($pdo, 'payments', 'patient_name')) {
    $pdo->exec("ALTER TABLE payments DROP COLUMN patient_name");
}
if ($hasColumn($pdo, 'payments', 'change_due')) {
    $pdo->exec("ALTER TABLE payments DROP COLUMN change_due");
}

// 10. PRESCRIPTIONS
echo "10. Migrating prescriptions...\n";
if (!$hasColumn($pdo, 'prescriptions', 'doctor_id')) {
    $pdo->exec("ALTER TABLE prescriptions ADD COLUMN doctor_id VARCHAR(30)");
}
if ($hasColumn($pdo, 'prescriptions', 'doctor_name')) {
    $pdo->exec("UPDATE prescriptions 
        SET doctor_id = (SELECT user_id FROM accounts WHERE accounts.role = 'Doctor' AND (accounts.full_name = prescriptions.doctor_name OR ('Dr. ' || accounts.full_name) = prescriptions.doctor_name) LIMIT 1)");
    $pdo->exec("ALTER TABLE prescriptions DROP COLUMN doctor_name");
}
if ($hasColumn($pdo, 'prescriptions', 'patient_name')) {
    $pdo->exec("ALTER TABLE prescriptions DROP COLUMN patient_name");
}

// 11. ROLES
echo "11. Migrating roles...\n";
if ($hasColumn($pdo, 'roles', 'user_count')) {
    $pdo->exec("ALTER TABLE roles DROP COLUMN user_count");
}

// 12. ROOMS
echo "12. Migrating rooms...\n";
if ($hasColumn($pdo, 'rooms', 'patient_name')) {
    $pdo->exec("ALTER TABLE rooms DROP COLUMN patient_name");
}

// 13. ACCOUNTS
echo "13. Migrating accounts...\n";
if ($hasColumn($pdo, 'accounts', 'user_role_label')) {
    $pdo->exec("ALTER TABLE accounts DROP COLUMN user_role_label");
}
if ($hasColumn($pdo, 'accounts', 'address')) {
    $pdo->exec("ALTER TABLE accounts DROP COLUMN address");
}

// 14. PATIENTS
echo "14. Migrating patients...\n";
if ($hasColumn($pdo, 'patients', 'full_name')) {
    $pdo->exec("ALTER TABLE patients DROP COLUMN full_name");
}
if ($hasColumn($pdo, 'patients', 'address')) {
    $pdo->exec("ALTER TABLE patients DROP COLUMN address");
}
if ($hasColumn($pdo, 'patients', 'contact_name')) {
    $pdo->exec("ALTER TABLE patients DROP COLUMN contact_name");
}

echo "\n=== MySQL 3NF Normalization Migration Completed Successfully! ===\n";
