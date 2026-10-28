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
            ['Medications', 'pill', 'medications'],
            ['Archive', 'clipboard', 'archive'],
            ['Audit Logs', 'history', 'audit-logs'],
        ],
        'Staff' => [
            ['Dashboard', 'grid', 'dashboard'],
            ['Patients', 'patient', 'patients'],
            ['Appointments', 'calendar', 'appointments'],
            ['Admissions', 'clipboard', 'admissions'],
            ['Billing', 'card', 'billing'],
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
            'dashboard'   => 'pages/admin/dashboard.php',
            'users'       => 'pages/admin/users.php',
            'roles'       => 'pages/admin/roles.php',
            'departments' => 'pages/admin/crud.php',
            'rooms'       => 'pages/admin/crud.php',
            'medications' => 'pages/admin/crud.php',
            'archive'     => 'pages/admin/archive.php',
            'audit-logs'  => 'pages/admin/audit.php',
        ],
        'Staff' => [
            'dashboard'    => 'pages/staff/dashboard.php',
            'patients'     => 'pages/shared/patients.php',
            'appointments' => 'pages/staff/appointments.php',
            'admissions'   => 'pages/staff/admissions.php',
            'billing'      => 'pages/staff/billing.php',
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
        'Staff'  => ['profile' => 'pages/staff/patient_profile.php', 'bill' => 'pages/staff/bill_detail.php'],
        'Doctor' => [
            'history'    => 'pages/doctor/patient_history.php',
            'new-record' => 'pages/doctor/new_record.php',
            'lab'        => 'pages/doctor/lab_detail.php',
        ],
        default => [],
    };
}

function current_user_name(string $role): string
{
    return ['Admin' => 'Amelia Hart', 'Staff' => 'Maya Chen', 'Doctor' => 'Dr. James Wilson'][$role] ?? 'User';
}

function current_user_title(string $role): string
{
    return ['Admin' => 'System administrator', 'Staff' => 'Front desk staff', 'Doctor' => 'Cardiologist'][$role] ?? '';
}

function seed_users(): array
{
    return [
        ['Olivia Bennett', 'olivia.b@medicore.com', 'Doctor', 'Active', 'Today, 09:42'],
        ['Marcus Lee', 'marcus.l@medicore.com', 'Nurse', 'Active', 'Today, 08:15'],
        ['Sophia Clarke', 'sophia.c@medicore.com', 'Front desk', 'Active', 'Yesterday, 17:32'],
        ['Noah Williams', 'noah.w@medicore.com', 'Doctor', 'Disabled', 'Mar 12, 2025'],
        ['Emma Davis', 'emma.d@medicore.com', 'Billing', 'Active', 'Mar 19, 2025'],
    ];
}

function patients_data(): array
{
    return [
        ['Eleanor Rigby', 'Apr 18, 1982', '(415) 555-0192', 'HIN-884-120', 'ER'],
        ['Michael Torres', 'Sep 03, 1974', '(415) 555-0138', 'HIN-293-771', 'MT'],
        ['Ava Thompson', 'Jan 22, 1991', '(415) 555-0145', 'HIN-551-092', 'AT'],
        ['Henry Adams', 'Jun 11, 1968', '(415) 555-0166', 'HIN-008-431', 'HA'],
        ['Nora Patel', 'Dec 08, 1987', '(415) 555-0114', 'HIN-714-903', 'NP'],
    ];
}

function archive_records(): array
{
    return [
        ['#ARC-5812', 'Eleanor Rigby', 'Medical record', 'Mar 20, 2025', 'Amelia Hart'],
        ['#ARC-5809', 'Michael Torres', 'Billing', 'Mar 20, 2025', 'Emma Davis'],
        ['#ARC-5788', 'Ava Thompson', 'Appointment', 'Mar 18, 2025', 'Sophia Clarke'],
        ['#ARC-5664', 'Henry Adams', 'Admission', 'Feb 26, 2025', 'Marcus Lee'],
        ['#ARC-5490', 'Nora Patel', 'Lab result', 'Jan 14, 2025', 'Olivia Bennett'],
        ['#ARC-4881', 'Daniel Kim', 'Medical record', 'Nov 08, 2024', 'Amelia Hart'],
    ];
}

function audit_rows(): array
{
    return [
        ['Amelia Hart', 'Updated', 'users', '#USR-1042', 'Mar 20, 2025 · 09:42'],
        ['Olivia Bennett', 'Viewed', 'medical_records', '#REC-8841', 'Mar 20, 2025 · 09:18'],
        ['Sophia Clarke', 'Created', 'appointments', '#APT-3281', 'Mar 20, 2025 · 08:56'],
        ['Marcus Lee', 'Updated', 'admissions', '#ADM-0912', 'Mar 20, 2025 · 08:41'],
        ['Amelia Hart', 'Disabled', 'users', '#USR-0937', 'Mar 19, 2025 · 17:12'],
    ];
}

function seed_crud(): array
{
    return [
        'departments' => [
            ['Cardiology', 'Dr. Olivia Bennett', '18', '2nd Floor'],
            ['Neurology', 'Dr. Ethan Ross', '12', '3rd Floor'],
            ['Pediatrics', 'Dr. Mia Morgan', '15', '1st Floor'],
            ['Orthopedics', 'Dr. Liam Scott', '11', 'West Wing'],
        ],
        'rooms' => [
            ['Room 201', 'General Ward', 'Occupied', 'Eleanor Rigby'],
            ['Room 202', 'General Ward', 'Available', '—'],
            ['Room 204', 'Cardiology', 'Occupied', 'Michael Torres'],
            ['Room 301', 'ICU', 'Cleaning', '—'],
        ],
        'medications' => [
            ['Amoxicillin', 'Antibiotic', '500 mg', '412 units'],
            ['Atorvastatin', 'Cardiovascular', '20 mg', '286 units'],
            ['Metformin', 'Diabetes', '850 mg', '328 units'],
            ['Lisinopril', 'Cardiovascular', '10 mg', '94 units'],
        ],
    ];
}

function seed_admissions(): array
{
    return [
        ['Michael Torres', 'Room 204', 'Cardiology', 'Mar 18, 2025', 'Dr. Bennett'],
        ['Henry Adams', 'Room 318', 'Orthopedics', 'Mar 17, 2025', 'Dr. Scott'],
        ['Lily Morgan', 'Room 112', 'Pediatrics', 'Mar 19, 2025', 'Dr. Morgan'],
        ['Daniel Kim', 'Room 302', 'ICU', 'Mar 16, 2025', 'Dr. Ross'],
    ];
}

function bills_data(): array
{
    return [
        ['Eleanor Rigby', '$240.00', 'Paid', 'Mar 20, 2025'],
        ['Michael Torres', '$1,280.00', 'Unpaid', 'Mar 18, 2025'],
        ['Ava Thompson', '$180.00', 'Pending', 'Mar 18, 2025'],
        ['Henry Adams', '$3,420.00', 'Unpaid', 'Mar 17, 2025'],
    ];
}

function doctor_visits(): array
{
    return [
        ['08:30', 'Eleanor Rigby', 'Follow-up', 'Completed'],
        ['10:00', 'Michael Torres', 'Consultation', 'In progress'],
        ['11:30', 'Ava Thompson', 'Follow-up', 'Waiting'],
        ['14:00', 'Nora Patel', 'New patient', 'Confirmed'],
        ['15:30', 'Henry Adams', 'Post-op review', 'Confirmed'],
    ];
}

function lab_rows(): array
{
    return [
        ['CBC-8421', 'Eleanor Rigby', 'Complete blood count', 'Done', 'Mar 20, 2025'],
        ['LIP-2938', 'Michael Torres', 'Lipid panel', 'Pending', 'Mar 20, 2025'],
        ['MET-7712', 'Ava Thompson', 'Metabolic panel', 'Done', 'Mar 19, 2025'],
        ['THY-0084', 'Nora Patel', 'Thyroid function', 'Pending', 'Mar 19, 2025'],
    ];
}
