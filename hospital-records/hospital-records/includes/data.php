<?php
declare(strict_types=1);

/** Sidebar navigation per role: [label, icon, slug]. */
function nav_for(string $role): array
{
    return match ($role) {
        'Admin' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['User Management', 'users', 'users'],
            ['Departments & Doctors', 'building', 'departments'],
            ['Room Setup', 'bed', 'rooms'],
            ['Price List', 'card', 'price-list'],
            ['Archive', 'archive', 'archive'],
            ['Audit Log', 'history', 'audit-logs'],
            ['Reports', 'printer', 'sales-records'],
            ['Profile', 'patient', 'profile'],
        ],
        'Staff' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Patients', 'patient', 'patients'],
            ['Appointments', 'calendar', 'appointments'],
            ['Admissions', 'clipboard', 'admissions'],
            ['Rooms', 'bed', 'staff-rooms'],
            ['Profile', 'patient', 'profile'],
        ],
        'Cashier' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Billing', 'card', 'unpaid-bills'],
            ['Payments', 'clipboard', 'process-payment'],
            ['Discharge Clearance', 'shield', 'discharge-clearance'],
            ['Payment History', 'history', 'payment-history'],
            ['End-of-Day Report', 'printer', 'daily-report'],
            ['Profile', 'patient', 'profile'],
        ],
        'Patient' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Appointments', 'calendar', 'appointments'],
            ['Find a Doctor', 'search', 'doctors'],
            ['Medical History', 'history', 'history'],
            ['Prescriptions', 'pill', 'prescriptions'],
            ['Bills', 'card', 'bills'],
            ['Profile', 'patient', 'profile'],
        ],
        default => [
            ['Dashboard', 'grid', 'dashboard'],
            ['My Schedule', 'clock', 'schedule'],
            ['Appointments', 'calendar', 'my-appointments'],
            ['Patients', 'patient', 'patients'],
            ['Consultation', 'clipboard', 'consultation'],
            ['Admissions', 'bed', 'doctor-admissions'],
            ['Lab Results', 'flask', 'lab-results'],
            ['Profile', 'patient', 'profile'],
        ],
    };
}

/** slug => page file, per role. */
function routes_for(string $role): array
{
    return match ($role) {
        'Admin' => [
            'dashboard'         => 'pages/admin/dashboard.php',
            'users'             => 'pages/admin/users.php',
            'roles'             => 'pages/admin/roles.php',
            'departments'       => 'pages/admin/crud.php',
            'rooms'             => 'pages/admin/crud.php',
            'price-list'        => 'pages/admin/price_list.php',
            'archive'           => 'pages/admin/archive.php',
            'audit-logs'        => 'pages/admin/audit.php',
            'sales-records'     => 'pages/admin/sales.php',
            'profile'           => 'pages/shared/my_profile.php',
        ],
        'Staff' => [
            'dashboard'    => 'pages/staff/dashboard.php',
            'patients'     => 'pages/shared/patients.php',
            'appointments' => 'pages/staff/appointments.php',
            'admissions'   => 'pages/staff/admissions.php',
            'staff-rooms'  => 'pages/staff/rooms.php',
            'book'         => 'pages/shared/book.php',
            'profile'      => 'pages/shared/my_profile.php',
        ],
        'Cashier' => [
            'dashboard'           => 'pages/cashier/dashboard.php',
            'unpaid-bills'        => 'pages/cashier/unpaid_bills.php',
            'process-payment'     => 'pages/cashier/process_payment.php',
            'discharge-clearance' => 'pages/cashier/discharge_clearance.php',
            'payment-history'     => 'pages/cashier/payment_history.php',
            'daily-report'        => 'pages/cashier/daily_report.php',
            'profile'             => 'pages/shared/my_profile.php',
        ],
        'Patient' => [
            'dashboard'     => 'pages/patient/home.php',
            'appointments'  => 'pages/patient/appointments.php',
            'doctors'       => 'pages/patient/doctors.php',
            'book'          => 'pages/shared/book.php',
            'history'       => 'pages/patient/history.php',
            'prescriptions' => 'pages/patient/prescriptions.php',
            'bills'         => 'pages/patient/bills.php',
            'profile'       => 'pages/shared/my_profile.php',
        ],
        default => [
            'dashboard'         => 'pages/doctor/dashboard.php',
            'schedule'          => 'pages/doctor/schedule.php',
            'my-appointments'   => 'pages/doctor/appointments.php',
            'patients'          => 'pages/shared/patients.php',
            'consultation'      => 'pages/doctor/new_record.php',
            'doctor-admissions' => 'pages/doctor/admissions.php',
            'lab-results'       => 'pages/doctor/lab_results.php',
            'profile'           => 'pages/shared/my_profile.php',
        ],
    };
}

/** detail view => page file, per role. */
function details_for(string $role): array
{
    return match ($role) {
        'Admin'   => [
            'receipt' => 'pages/cashier/receipt.php',
            'profile' => 'pages/staff/patient_profile.php',
        ],
        'Staff'   => [
            'profile' => 'pages/staff/patient_profile.php',
        ],
        'Cashier' => [
            'receipt' => 'pages/cashier/receipt.php',
            'bill'    => 'pages/patient/bill_detail.php',
        ],
        'Doctor'  => [
            'history'    => 'pages/doctor/patient_history.php',
            'new-record' => 'pages/doctor/new_record.php',
            'lab'        => 'pages/doctor/lab_detail.php',
            'profile'    => 'pages/staff/patient_profile.php',
        ],
        'Patient' => [
            'bill'    => 'pages/patient/bill_detail.php',
            'receipt' => 'pages/cashier/receipt.php',
        ],
        default => [],
    };
}

function current_user_name(string $role): string
{
    if (!empty($_SESSION['user_name']) && ($_SESSION['role'] ?? '') === $role) {
        return (string) $_SESSION['user_name'];
    }
    if ($role === 'Patient' && !empty($_SESSION['state']['patients'][0][0])) {
        return (string) $_SESSION['state']['patients'][0][0];
    }
    if ($role === 'Doctor') {
        foreach ($_SESSION['state']['users'] ?? [] as $u) {
            if (($u[2] ?? '') === 'Doctor' && ($u[3] ?? '') === 'Active') {
                return str_starts_with($u[0], 'Dr.') ? $u[0] : 'Dr. ' . $u[0];
            }
        }
    }
    return ['Admin' => 'System Administrator', 'Staff' => 'Front Desk Staff', 'Cashier' => 'Hospital Cashier', 'Doctor' => 'Attending Physician', 'Patient' => 'Patient'][$role] ?? 'User';
}

function current_user_title(string $role): string
{
    $base = ['Admin' => 'WMMC System Admin', 'Staff' => 'Front desk & records', 'Cashier' => 'Billing & cashier desk', 'Doctor' => 'Attending physician', 'Patient' => 'Patient portal'][$role] ?? '';
    $uid  = $_SESSION['user_id'] ?? '';
    return $uid !== '' ? $base . ' · ' . $uid : $base;
}

/**
 * Hospital doctor directory:
 * Returns [0 => name, 1 => department, 2 => specialty, 3 => years_exp, 4 => availability_days, 'user_id' => ..., 'license' => ..., 'schedule' => ...]
 */
function doctors_data(): array
{
    $docs      = [];
    $schedules = $_SESSION['state']['doctor_schedules'] ?? seed_doctor_schedules();
    foreach ($_SESSION['state']['users'] ?? [] as $u) {
        if (($u[2] ?? '') === 'Doctor' && ($u[3] ?? '') === 'Active') {
            $name  = str_starts_with((string) $u[0], 'Dr.') ? (string) $u[0] : 'Dr. ' . $u[0];
            $dept  = !empty($u[5]) ? (string) $u[5] : 'Internal Medicine';
            $spec  = !empty($u[6]) ? (string) $u[6] : 'General Internal Medicine';
            $uid   = (string) ($u[7] ?? '');
            $lic   = (string) ($u[9] ?? 'PRC-0108421');
            $sched = $schedules[$uid] ?? ($schedules[$name] ?? [
                'days'          => [1, 2, 3, 4, 5],
                'days_label'    => 'Mon – Fri',
                'slots'         => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00'],
                'blocked_dates' => [],
            ]);
            $docs[] = [
                0           => $name,
                1           => $dept,
                2           => $spec,
                3           => 8,
                4           => (string) ($sched['days_label'] ?? 'Mon – Fri'),
                'user_id'   => $uid,
                'license'   => $lic,
                'schedule'  => $sched,
                'phone'     => (string) ($u['phone'] ?? ''),
                'email'     => (string) ($u[1] ?? ''),
            ];
        }
    }
    return $docs;
}

function seed_users(): array
{
    return [];
}

function patients_data(): array
{
    return [];
}

function archive_records(): array
{
    return [];
}

/** [user, action, table, record id, date & time, user role] */
function audit_rows(): array
{
    return [];
}

/** All assignable permissions. */
function permissions_list(): array
{
    return [
        'View patients', 'Edit patients', 'View medical records', 'Edit medical records',
        'Manage appointments', 'Manage admissions', 'Manage billing', 'View lab results',
        'Manage users', 'Manage roles', 'View audit logs', 'Manage archive',
    ];
}

/** [name, description, user count, permissions[], icon] */
function seed_roles(): array
{
    $all = permissions_list();
    return [
        ['Doctor', 'Clinical consultations, schedule management, inpatient orders, and discharge summaries', 0, array_slice($all, 0, 8), 'patient'],
        ['Staff', 'Patient registration (Full & Provisional), check-in, appointments, admissions, and bed status', 0, ['View patients', 'Edit patients', 'Manage appointments', 'Manage admissions'], 'users'],
        ['Cashier', 'Billing, charges, payments (Cash/Card/HMO/PhilHealth), discharge clearance, and EOD reports', 0, ['View patients', 'Manage billing'], 'card'],
        ['Patient', 'Self-service portal for appointments, medical history, prescriptions, and billing statements', 0, ['View medical records', 'Manage appointments', 'View lab results'], 'patient'],
    ];
}

/** role => [[title, text, time, read]] */
function seed_notifications(): array
{
    return [
        'Admin'   => [],
        'Staff'   => [],
        'Cashier' => [],
        'Doctor'  => [],
        'Patient' => [],
    ];
}

/** Default West Metro Medical Center (Zamboanga City) departments & rooms. */
function seed_departments_list(): array
{
    return [
        ['Internal Medicine', 'Dr. Rafael Lorenzo', '6', '2nd Floor · Main Medical Wing'],
        ['Cardiology', 'Dr. Elena Santos', '4', '2nd Floor · Heart & Vascular Center'],
        ['Pediatrics & Neonatology', 'Dr. Camille Navarro', '5', '3rd Floor · Maternal & Child Wing'],
        ['Obstetrics & Gynecology', 'Dr. Patricia Mendoza', '5', '3rd Floor · Maternal & Child Wing'],
        ['General Surgery', 'Dr. Marco Villanueva', '4', '2nd Floor · Surgical Pavilion'],
        ['Orthopedics', 'Dr. Antonio Reyes', '3', '2nd Floor · Bone & Joint Center'],
        ['Emergency Medicine', 'Dr. Gabriel Cruz', '8', 'Ground Floor · ER Complex'],
        ['Nephrology & Hemodialysis', 'Dr. Sofia Lim', '4', 'Ground Floor · Dialysis Center'],
        ['Radiology & Diagnostic Imaging', 'Dr. Victor Alvarez', '3', 'Ground Floor · Diagnostic Wing'],
    ];
}

/** Default West Metro Medical Center rooms & beds structure. */
function seed_beds(): array
{
    return [
        ['bed_id' => 'RM-101-A', 'room_no' => '101', 'bed_code' => 'Bed A', 'ward' => 'General Ward', 'room_type' => 'Ward', 'floor' => '1st Floor', 'rate' => 1500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-101-B', 'room_no' => '101', 'bed_code' => 'Bed B', 'ward' => 'General Ward', 'room_type' => 'Ward', 'floor' => '1st Floor', 'rate' => 1500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-101-C', 'room_no' => '101', 'bed_code' => 'Bed C', 'ward' => 'General Ward', 'room_type' => 'Ward', 'floor' => '1st Floor', 'rate' => 1500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-102-A', 'room_no' => '102', 'bed_code' => 'Bed A', 'ward' => 'General Ward', 'room_type' => 'Ward', 'floor' => '1st Floor', 'rate' => 1500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-102-B', 'room_no' => '102', 'bed_code' => 'Bed B', 'ward' => 'General Ward', 'room_type' => 'Ward', 'floor' => '1st Floor', 'rate' => 1500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-201-A', 'room_no' => '201', 'bed_code' => 'Bed A', 'ward' => 'Pediatrics Ward', 'room_type' => 'Semi-Private', 'floor' => '2nd Floor', 'rate' => 2500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-201-B', 'room_no' => '201', 'bed_code' => 'Bed B', 'ward' => 'Pediatrics Ward', 'room_type' => 'Semi-Private', 'floor' => '2nd Floor', 'rate' => 2500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-202-A', 'room_no' => '202', 'bed_code' => 'Bed A', 'ward' => 'Maternity Ward', 'room_type' => 'Semi-Private', 'floor' => '2nd Floor', 'rate' => 2500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-202-B', 'room_no' => '202', 'bed_code' => 'Bed B', 'ward' => 'Maternity Ward', 'room_type' => 'Semi-Private', 'floor' => '2nd Floor', 'rate' => 2500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-301-A', 'room_no' => '301', 'bed_code' => 'Bed A', 'ward' => 'Medical Ward', 'room_type' => 'Private', 'floor' => '3rd Floor', 'rate' => 4200.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'RM-302-A', 'room_no' => '302', 'bed_code' => 'Bed A', 'ward' => 'Cardiology Ward', 'room_type' => 'Private', 'floor' => '3rd Floor', 'rate' => 4500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'ICU-01-A', 'room_no' => 'ICU-1', 'bed_code' => 'Bed A', 'ward' => 'ICU', 'room_type' => 'ICU', 'floor' => '2nd Floor', 'rate' => 7500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'ICU-01-B', 'room_no' => 'ICU-1', 'bed_code' => 'Bed B', 'ward' => 'ICU', 'room_type' => 'ICU', 'floor' => '2nd Floor', 'rate' => 7500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
        ['bed_id' => 'STE-401-A', 'room_no' => 'Suite 401', 'bed_code' => 'Bed A', 'ward' => 'Executive Wing', 'room_type' => 'Suite', 'floor' => '4th Floor', 'rate' => 9500.0, 'status' => 'Vacant', 'patient' => '', 'mrn' => '', 'admitted_date' => '', 'doctor' => '', 'maintenance_reason' => '', 'active' => true],
    ];
}

/** Default West Metro Medical Center Price List (Section 8.5). */
function seed_price_list(): array
{
    $raw = [
        ['code' => 'PRC-101', 'name' => 'General Outpatient Consultation', 'category' => 'Consultation', 'department' => 'Internal Medicine', 'price' => 600.00, 'status' => 'Active'],
        ['code' => 'PRC-102', 'name' => 'Specialist Consultation', 'category' => 'Consultation', 'department' => 'All Departments', 'price' => 850.00, 'status' => 'Active'],
        ['code' => 'PRC-103', 'name' => 'Follow-up Consultation', 'category' => 'Consultation', 'department' => 'All Departments', 'price' => 500.00, 'status' => 'Active'],
        ['code' => 'PRC-104', 'name' => 'Emergency Room Evaluation', 'category' => 'Consultation', 'department' => 'Emergency Medicine', 'price' => 1200.00, 'status' => 'Active'],
        ['code' => 'LAB-201', 'name' => 'Complete Blood Count (CBC)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 350.00, 'status' => 'Active'],
        ['code' => 'LAB-202', 'name' => 'Urinalysis (Routine)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 200.00, 'status' => 'Active'],
        ['code' => 'LAB-203', 'name' => 'Fasting Blood Sugar (FBS)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 280.00, 'status' => 'Active'],
        ['code' => 'LAB-204', 'name' => 'Lipid Panel (Cholesterol/Triglycerides)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 850.00, 'status' => 'Active'],
        ['code' => 'LAB-205', 'name' => 'Metabolic Panel (Electrolytes/Creatinine)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 1100.00, 'status' => 'Active'],
        ['code' => 'LAB-206', 'name' => 'Liver Function Test (SGPT/SGOT)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 750.00, 'status' => 'Active'],
        ['code' => 'LAB-207', 'name' => 'Thyroid Function (TSH / FT4)', 'category' => 'Lab & Diagnostics', 'department' => 'Clinical Laboratory', 'price' => 1450.00, 'status' => 'Active'],
        ['code' => 'RAD-301', 'name' => 'Chest X-Ray (PA View)', 'category' => 'Lab & Diagnostics', 'department' => 'Radiology & Diagnostic Imaging', 'price' => 650.00, 'status' => 'Active'],
        ['code' => 'RAD-302', 'name' => 'Electrocardiogram (ECG)', 'category' => 'Lab & Diagnostics', 'department' => 'Cardiology', 'price' => 600.00, 'status' => 'Active'],
        ['code' => 'RAD-303', 'name' => '2D Echocardiography with Doppler', 'category' => 'Lab & Diagnostics', 'department' => 'Cardiology', 'price' => 3500.00, 'status' => 'Active'],
        ['code' => 'MED-401', 'name' => 'Amoxicillin 500mg Capsule (Course)', 'category' => 'Medication', 'department' => 'Pharmacy', 'price' => 350.00, 'status' => 'Active'],
        ['code' => 'MED-402', 'name' => 'Losartan 50mg Tablet (30s)', 'category' => 'Medication', 'department' => 'Pharmacy', 'price' => 540.00, 'status' => 'Active'],
        ['code' => 'MED-403', 'name' => 'Amlodipine 5mg Tablet (30s)', 'category' => 'Medication', 'department' => 'Pharmacy', 'price' => 450.00, 'status' => 'Active'],
        ['code' => 'MED-404', 'name' => 'Metformin 500mg Tablet (30s)', 'category' => 'Medication', 'department' => 'Pharmacy', 'price' => 360.00, 'status' => 'Active'],
        ['code' => 'MED-405', 'name' => 'Paracetamol 500mg Tablet (Strip)', 'category' => 'Medication', 'department' => 'Pharmacy', 'price' => 120.00, 'status' => 'Active'],
        ['code' => 'RM-501',  'name' => 'General Ward Bed (Per Day)', 'category' => 'Room Rate', 'department' => 'Inpatient', 'price' => 1500.00, 'status' => 'Active'],
        ['code' => 'RM-502',  'name' => 'Semi-Private Room (Per Day)', 'category' => 'Room Rate', 'department' => 'Inpatient', 'price' => 2500.00, 'status' => 'Active'],
        ['code' => 'RM-503',  'name' => 'Private Room (Per Day)', 'category' => 'Room Rate', 'department' => 'Inpatient', 'price' => 4200.00, 'status' => 'Active'],
        ['code' => 'RM-504',  'name' => 'Intensive Care Unit - ICU (Per Day)', 'category' => 'Room Rate', 'department' => 'Inpatient', 'price' => 7500.00, 'status' => 'Active'],
        ['code' => 'RM-505',  'name' => 'Executive Suite (Per Day)', 'category' => 'Room Rate', 'department' => 'Inpatient', 'price' => 9500.00, 'status' => 'Active'],
    ];
    foreach ($raw as &$it) {
        $it['id']     = $it['code'];
        $it['active'] = ($it['status'] === 'Active');
    }
    unset($it);
    return $raw;
}

function seed_doctor_schedules(): array
{
    return [];
}

function seed_crud(): array
{
    $rooms = [];
    foreach (seed_beds() as $rm) {
        $rNo = $rm['room_no'] ?? ($rm['room_number'] ?? 'RM-101');
        $rooms[] = [$rNo, $rm['ward'] ?? 'General Ward', 'Available', '—'];
    }
    return [
        'departments' => seed_departments_list(),
        'rooms'       => $rooms,
    ];
}

function seed_admissions(): array
{
    return [];
}

/** Formatted date N days before today. */
function days_ago(int $n): string
{
    return date('M d, Y', strtotime('-' . $n . ' days'));
}

function seed_discharges(): array
{
    return [];
}

function bills_data(): array
{
    return [];
}

function doctor_visits(): array
{
    return [];
}

function seed_appointments(): array
{
    return doctor_visits();
}

function seed_lab_results(): array
{
    return [];
}

function lab_rows(): array
{
    return seed_lab_results();
}

function seed_medical_records(): array
{
    return [];
}
