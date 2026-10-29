<?php
declare(strict_types=1);

/** Sidebar navigation per role: [label, icon, slug]. */
function nav_for(string $role): array
{
    return match ($role) {
        'Admin' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Users', 'users', 'users'],
            ['Roles', 'shield', 'roles'],
            ['Departments', 'building', 'departments'],
            ['Rooms', 'bed', 'rooms'],
            ['Sales Records', 'card', 'sales-records'],
            ['Archive', 'clipboard', 'archive'],
            ['Audit Logs', 'history', 'audit-logs'],
        ],
        'Staff' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Patients', 'patient', 'patients'],
            ['Appointments', 'calendar', 'appointments'],
            ['Admissions', 'clipboard', 'admissions'],
            ['Rooms', 'bed', 'staff-rooms'],
        ],
        'Cashier' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Unpaid Bills', 'card', 'unpaid-bills'],
            ['Process Payment', 'clipboard', 'process-payment'],
            ['Payment History', 'history', 'payment-history'],
            ['Daily Report', 'printer', 'daily-report'],
        ],
        'Patient' => [
            ['Home', 'grid', 'dashboard'],
            ['Appointments', 'calendar', 'appointments'],
            ['Find a Doctor', 'search', 'doctors'],
            ['Medical History', 'history', 'history'],
            ['Prescriptions', 'pill', 'prescriptions'],
            ['Bills', 'card', 'bills'],
            ['Profile', 'patient', 'profile'],
        ],
        default => [
            ['Dashboard', 'grid', 'dashboard'],
            ['My Appointments', 'calendar', 'my-appointments'],
            ['Patients', 'patient', 'patients'],
            ['Lab Results', 'flask', 'lab-results'],
        ],
    };
}

/** slug => page file, per role. */
function routes_for(string $role): array
{
    return match ($role) {
        'Admin' => [
            'dashboard'     => 'pages/admin/dashboard.php',
            'users'         => 'pages/admin/users.php',
            'roles'         => 'pages/admin/roles.php',
            'departments'   => 'pages/admin/crud.php',
            'rooms'         => 'pages/admin/crud.php',
            'sales-records' => 'pages/admin/sales.php',
            'archive'       => 'pages/admin/archive.php',
            'audit-logs'    => 'pages/admin/audit.php',
        ],
        'Staff' => [
            'dashboard'    => 'pages/staff/dashboard.php',
            'patients'     => 'pages/shared/patients.php',
            'appointments' => 'pages/staff/appointments.php',
            'admissions'   => 'pages/staff/admissions.php',
            'staff-rooms'  => 'pages/staff/rooms.php',
            'book'         => 'pages/shared/book.php',
        ],
        'Cashier' => [
            'dashboard'       => 'pages/cashier/dashboard.php',
            'unpaid-bills'    => 'pages/cashier/unpaid_bills.php',
            'process-payment' => 'pages/cashier/process_payment.php',
            'payment-history' => 'pages/cashier/payment_history.php',
            'daily-report'    => 'pages/cashier/daily_report.php',
        ],
        'Patient' => [
            'dashboard'     => 'pages/patient/home.php',
            'appointments'  => 'pages/patient/appointments.php',
            'doctors'       => 'pages/patient/doctors.php',
            'book'          => 'pages/shared/book.php',
            'history'       => 'pages/patient/history.php',
            'prescriptions' => 'pages/patient/prescriptions.php',
            'bills'         => 'pages/patient/bills.php',
            'profile'       => 'pages/patient/profile.php',
        ],
        default => [
            'dashboard'       => 'pages/doctor/dashboard.php',
            'my-appointments' => 'pages/doctor/appointments.php',
            'patients'        => 'pages/shared/patients.php',
            'lab-results'     => 'pages/doctor/lab_results.php',
        ],
    };
}

/** detail view => page file, per role. */
function details_for(string $role): array
{
    return match ($role) {
        'Admin'   => [
            'receipt' => 'pages/cashier/receipt.php',
        ],
        'Staff'   => ['profile' => 'pages/staff/patient_profile.php'],
        'Cashier' => [
            'receipt' => 'pages/cashier/receipt.php',
        ],
        'Doctor'  => [
            'history'    => 'pages/doctor/patient_history.php',
            'new-record' => 'pages/doctor/new_record.php',
            'lab'        => 'pages/doctor/lab_detail.php',
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
    return ['Admin' => 'Administrator', 'Staff' => 'Front Desk', 'Cashier' => 'Hospital Cashier', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$role] ?? 'User';
}

function current_user_title(string $role): string
{
    $base = ['Admin' => 'System administrator', 'Staff' => 'Front desk staff', 'Cashier' => 'Billing & cashier desk', 'Doctor' => 'Attending physician', 'Patient' => 'Patient portal'][$role] ?? '';
    $uid  = $_SESSION['user_id'] ?? '';
    return $uid !== '' ? $base . ' · ' . $uid : $base;
}

/** Hospital doctor directory: [name, department, specialty, years of experience, availability] */
function doctors_data(): array
{
    $docs = [];
    foreach ($_SESSION['state']['users'] ?? [] as $u) {
        if (($u[2] ?? '') === 'Doctor' && ($u[3] ?? '') === 'Active') {
            $name = str_starts_with($u[0], 'Dr.') ? $u[0] : 'Dr. ' . $u[0];
            $dept = !empty($u[5]) ? $u[5] : 'General Medicine';
            $spec = !empty($u[6]) ? $u[6] : 'General Practice';
            $docs[] = [$name, $dept, $spec, 1, 'Mon – Fri'];
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

/** [name, description, user count, permissions[], icon] — Administrator excluded (only 1 system admin). */
function seed_roles(): array
{
    $all = permissions_list();
    return [
        ['Doctor', 'Clinical records and appointments', 0, array_slice($all, 0, 8), 'patient'],
        ['Staff', 'Registration, scheduling, and admissions', 0, ['View patients', 'Edit patients', 'Manage appointments', 'Manage admissions'], 'users'],
        ['Cashier', 'Billing, payment collection, official receipts, and daily reports', 0, ['View patients', 'Manage billing'], 'card'],
        ['Patient', 'Patient portal access for appointments, records, and billing', 0, ['View medical records', 'Manage appointments', 'View lab results'], 'patient'],
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

function seed_crud(): array
{
    return [
        'departments' => [],
        'rooms'       => [],
        'medications' => [],
    ];
}

function seed_admissions(): array
{
    return [];
}

/** Formatted date N days before today (sample data stays current). */
function days_ago(int $n): string
{
    return date('M d, Y', strtotime('-' . $n . ' days'));
}

/** [patient, room, ward, discharged date, proof image path|null, timestamp] */
function seed_discharges(): array
{
    return [];
}

function bills_data(): array
{
    return [];
}

/** [time, patient, visit_type, status, complaint, doctor, department, date] */
function doctor_visits(): array
{
    return [];
}

/** Mutable version of doctor_visits() stored in session. */
function seed_appointments(): array { return doctor_visits(); }

function seed_lab_results(): array
{
    return [];
}

function lab_rows(): array { return seed_lab_results(); }

function seed_medical_records(): array
{
    return [];
}
