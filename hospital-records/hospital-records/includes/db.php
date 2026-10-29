<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 * Database Layer (XAMPP MySQL `medicore_records` primary + SQLite fallback)
 * Stores real accounts (with hashed passwords), one-time Admin lock,
 * roles, departments, rooms, medications, archives, audit logs, and
 * all clinical/hospital records in relational database tables.
 * ------------------------------------------------------------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbDir = __DIR__ . '/../database';
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0775, true);
    }
    $realDir    = realpath($dbDir) ?: $dbDir;
    $sqlitePath = $realDir . DIRECTORY_SEPARATOR . 'medicore.sqlite';

    $driver = 'sqlite';
    // 1. Primary: XAMPP MySQL (127.0.0.1:3306 -> `medicore_records` in phpMyAdmin)
    if (in_array('mysql', PDO::getAvailableDrivers(), true)) {
        try {
            $host = getenv('MEDICORE_DB_HOST') ?: '127.0.0.1';
            $port = getenv('MEDICORE_DB_PORT') ?: '3306';
            $user = getenv('MEDICORE_DB_USER') ?: 'root';
            $pass = getenv('MEDICORE_DB_PASS') !== false ? (string) getenv('MEDICORE_DB_PASS') : '';
            $name = getenv('MEDICORE_DB_NAME') ?: 'medicore_records';

            $tmp = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 1,
            ]);
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tmp->exec("USE `{$name}`");
            $pdo    = $tmp;
            $driver = 'mysql';
        } catch (Throwable) {
            $pdo = null;
        }
    }

    // 2. Fallback to local PDO SQLite file if XAMPP MySQL daemon is not running yet
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA temp_store = MEMORY');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $driver = 'sqlite';
    }

    db_init_schema($pdo, $driver);

    // 3. If connected to MySQL and MySQL `accounts` is empty, automatically migrate any accounts/data from SQLite
    if ($driver === 'mysql' && is_file($sqlitePath)) {
        db_migrate_sqlite_to_mysql($pdo, $sqlitePath);
    }

    return $pdo;
}

function db_init_schema(PDO $pdo, string $driver): void
{
    $autoInc = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

    $pdo->exec("CREATE TABLE IF NOT EXISTS accounts (
        id {$autoInc},
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
        birthdate VARCHAR(40) NOT NULL DEFAULT '',
        last_login VARCHAR(60) NOT NULL DEFAULT 'Never',
        created_at VARCHAR(40) NOT NULL
    )");
    try {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN birthdate VARCHAR(40) NOT NULL DEFAULT ''");
    } catch (Throwable) {
        // Column already exists
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id {$autoInc},
        role_name VARCHAR(60) NOT NULL UNIQUE,
        description VARCHAR(255) NOT NULL DEFAULT '',
        user_count INT NOT NULL DEFAULT 0,
        permissions_json TEXT NOT NULL,
        icon VARCHAR(40) NOT NULL DEFAULT 'shield'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (
        id {$autoInc},
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
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
        id {$autoInc},
        name VARCHAR(100) NOT NULL UNIQUE,
        head_doctor VARCHAR(150) NOT NULL DEFAULT 'Unassigned',
        staff_count VARCHAR(20) NOT NULL DEFAULT '0',
        location VARCHAR(100) NOT NULL DEFAULT 'Main Wing'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id {$autoInc},
        room_number VARCHAR(40) NOT NULL UNIQUE,
        ward VARCHAR(100) NOT NULL DEFAULT 'General Ward',
        status VARCHAR(30) NOT NULL DEFAULT 'Available',
        patient_name VARCHAR(150) NOT NULL DEFAULT '—'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS medications (
        id {$autoInc},
        name VARCHAR(120) NOT NULL UNIQUE,
        category VARCHAR(80) NOT NULL DEFAULT 'General',
        strength VARCHAR(60) NOT NULL DEFAULT '',
        stock VARCHAR(60) NOT NULL DEFAULT '0 units'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
        id {$autoInc},
        patient_id VARCHAR(20) NOT NULL DEFAULT '',
        slot_time VARCHAR(20) NOT NULL,
        patient_name VARCHAR(150) NOT NULL,
        visit_type VARCHAR(80) NOT NULL DEFAULT 'Consultation',
        status VARCHAR(30) NOT NULL DEFAULT 'Confirmed',
        complaint TEXT NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        department VARCHAR(100) NOT NULL,
        appt_date VARCHAR(40) NOT NULL
    )");
    try {
        $pdo->exec("ALTER TABLE appointments ADD COLUMN patient_id VARCHAR(20) NOT NULL DEFAULT ''");
    } catch (Throwable) {
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS admissions (
        id {$autoInc},
        patient_name VARCHAR(150) NOT NULL,
        room_number VARCHAR(40) NOT NULL,
        ward VARCHAR(100) NOT NULL,
        admitted_date VARCHAR(40) NOT NULL,
        doctor_name VARCHAR(150) NOT NULL,
        reason TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discharges (
        id {$autoInc},
        patient_name VARCHAR(150) NOT NULL,
        room_number VARCHAR(40) NOT NULL,
        ward VARCHAR(100) NOT NULL,
        discharged_date VARCHAR(40) NOT NULL,
        proof_path VARCHAR(255) DEFAULT NULL,
        discharged_ts BIGINT NOT NULL DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bills (
        id {$autoInc},
        bill_no VARCHAR(40) NOT NULL DEFAULT '',
        patient_name VARCHAR(150) NOT NULL,
        amount VARCHAR(40) NOT NULL,
        paid_amount VARCHAR(40) NOT NULL DEFAULT '0',
        status VARCHAR(30) NOT NULL DEFAULT 'Unpaid',
        issued_date VARCHAR(40) NOT NULL,
        receipt_path VARCHAR(255) DEFAULT NULL,
        items_json LONGTEXT DEFAULT NULL
    )");
    foreach ([
        "ALTER TABLE bills ADD COLUMN bill_no VARCHAR(40) NOT NULL DEFAULT ''",
        "ALTER TABLE bills ADD COLUMN paid_amount VARCHAR(40) NOT NULL DEFAULT '0'",
        "ALTER TABLE bills ADD COLUMN items_json LONGTEXT DEFAULT NULL",
    ] as $altBillSql) {
        try {
            $pdo->exec($altBillSql);
        } catch (Throwable) {
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id {$autoInc},
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
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (
        id {$autoInc},
        record_code VARCHAR(30) NOT NULL,
        patient_id VARCHAR(20) NOT NULL DEFAULT '',
        patient_name VARCHAR(150) NOT NULL,
        doctor_id VARCHAR(20) NOT NULL DEFAULT '',
        doctor_name VARCHAR(150) NOT NULL,
        visit_date VARCHAR(40) NOT NULL,
        visit_type VARCHAR(80) NOT NULL,
        complaint TEXT NOT NULL,
        notes TEXT NOT NULL,
        diagnoses_json TEXT NOT NULL,
        prescriptions_json TEXT NOT NULL,
        lab_orders_json TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Completed'
    )");
    foreach ([
        "ALTER TABLE medical_records ADD COLUMN patient_id VARCHAR(20) NOT NULL DEFAULT ''",
        "ALTER TABLE medical_records ADD COLUMN doctor_id VARCHAR(20) NOT NULL DEFAULT ''",
    ] as $altMrSql) {
        try {
            $pdo->exec($altMrSql);
        } catch (Throwable) {
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS prescriptions (
        id {$autoInc},
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
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lab_results (
        id {$autoInc},
        test_code VARCHAR(30) NOT NULL,
        record_code VARCHAR(30) NOT NULL DEFAULT '',
        patient_id VARCHAR(20) NOT NULL DEFAULT '',
        patient_name VARCHAR(150) NOT NULL,
        doctor_name VARCHAR(150) NOT NULL DEFAULT '',
        test_name VARCHAR(120) NOT NULL,
        priority VARCHAR(30) NOT NULL DEFAULT 'Routine',
        status VARCHAR(30) NOT NULL DEFAULT 'Pending',
        clinical_notes TEXT DEFAULT NULL,
        results_json TEXT DEFAULT NULL,
        collected_date VARCHAR(40) NOT NULL
    )");
    foreach ([
        "ALTER TABLE lab_results ADD COLUMN record_code VARCHAR(30) NOT NULL DEFAULT ''",
        "ALTER TABLE lab_results ADD COLUMN patient_id VARCHAR(20) NOT NULL DEFAULT ''",
        "ALTER TABLE lab_results ADD COLUMN doctor_name VARCHAR(150) NOT NULL DEFAULT ''",
        "ALTER TABLE lab_results ADD COLUMN priority VARCHAR(30) NOT NULL DEFAULT 'Routine'",
        "ALTER TABLE lab_results ADD COLUMN clinical_notes TEXT DEFAULT NULL",
        "ALTER TABLE lab_results ADD COLUMN results_json TEXT DEFAULT NULL",
    ] as $altLrSql) {
        try {
            $pdo->exec($altLrSql);
        } catch (Throwable) {
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS archive_records (
        id {$autoInc},
        archive_code VARCHAR(30) NOT NULL UNIQUE,
        subject_name VARCHAR(150) NOT NULL,
        record_type VARCHAR(60) NOT NULL,
        archived_date VARCHAR(40) NOT NULL,
        archived_by VARCHAR(150) NOT NULL,
        payload_json LONGTEXT DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id {$autoInc},
        actor_name VARCHAR(150) NOT NULL,
        action VARCHAR(40) NOT NULL,
        table_name VARCHAR(60) NOT NULL,
        record_id VARCHAR(60) NOT NULL,
        logged_at VARCHAR(60) NOT NULL,
        actor_role VARCHAR(40) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_store (
        store_key VARCHAR(64) PRIMARY KEY,
        store_value LONGTEXT NOT NULL,
        updated_at VARCHAR(40) NOT NULL
    )");
}

/** Migrate accounts & store from SQLite to MySQL once when MySQL starts up. */
function db_migrate_sqlite_to_mysql(PDO $mysql, string $sqlitePath): void
{
    try {
        $count = (int) ($mysql->query('SELECT COUNT(*) AS c FROM accounts')->fetch()['c'] ?? 0);
        if ($count > 0) {
            return;
        }
        $sq = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $accRows = $sq->query('SELECT * FROM accounts')->fetchAll();
        if (!$accRows) {
            return;
        }
        $ins = $mysql->prepare('INSERT IGNORE INTO accounts
            (user_id, username, email, password_hash, full_name, role, user_role_label, status, department, specialty, license_number, last_login, created_at)
            VALUES (:uid, :un, :em, :pw, :fn, :r, :rl, :st, :dp, :sp, :lc, :ll, :ca)');
        foreach ($accRows as $a) {
            $ins->execute([
                ':uid' => $a['user_id'],
                ':un'  => $a['username'],
                ':em'  => $a['email'],
                ':pw'  => $a['password_hash'],
                ':fn'  => $a['full_name'],
                ':r'   => $a['role'],
                ':rl'  => $a['user_role_label'],
                ':st'  => $a['status'],
                ':dp'  => $a['department'] ?? '',
                ':sp'  => $a['specialty'] ?? '',
                ':lc'  => $a['license_number'] ?? '',
                ':ll'  => $a['last_login'] ?? 'Never',
                ':ca'  => $a['created_at'] ?? date('M d, Y · H:i'),
            ]);
        }
        $storeRows = $sq->query('SELECT * FROM app_store')->fetchAll();
        $insStore  = $mysql->prepare('INSERT IGNORE INTO app_store (store_key, store_value, updated_at) VALUES (:k, :v, :t)');
        foreach ($storeRows as $s) {
            $insStore->execute([':k' => $s['store_key'], ':v' => $s['store_value'], ':t' => $s['updated_at']]);
        }
    } catch (Throwable) {
        // Ignore if sqlite file is locked or unreadable
    }
}

function db_get_store(string $key, mixed $default = null): mixed
{
    $stmt = db()->prepare('SELECT store_value FROM app_store WHERE store_key = :k LIMIT 1');
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch();
    if (!$row) {
        return $default;
    }
    $decoded = json_decode((string) $row['store_value'], true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $row['store_value'];
}

function db_set_store(string $key, mixed $value): void
{
    $pdo  = db();
    $json = is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE);
    $now  = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('SELECT store_key FROM app_store WHERE store_key = :k LIMIT 1');
    $stmt->execute([':k' => $key]);
    if ($stmt->fetch()) {
        $up = $pdo->prepare('UPDATE app_store SET store_value = :v, updated_at = :t WHERE store_key = :k');
        $up->execute([':v' => $json, ':t' => $now, ':k' => $key]);
    } else {
        $ins = $pdo->prepare('INSERT INTO app_store (store_key, store_value, updated_at) VALUES (:k, :v, :t)');
        $ins->execute([':k' => $key, ':v' => $json, ':t' => $now]);
    }
}

/**
 * Has the single Administrator (A-ID) account EVER been created?
 * Once true, no A-ID or Admin account can ever be registered again.
 */
function db_admin_claimed(): bool
{
    if (db_get_store('admin_id_claimed') === '1' || db_get_store('admin_id_claimed') === 1) {
        return true;
    }
    $stmt = db()->query("SELECT COUNT(*) AS c FROM accounts WHERE role = 'Admin' OR UPPER(user_id) LIKE 'A%'");
    $cnt  = (int) ($stmt->fetch()['c'] ?? 0);
    if ($cnt > 0) {
        db_set_store('admin_id_claimed', '1');
        return true;
    }
    return false;
}

/** Map a role name ('Doctor', 'Staff', 'Cashier', 'Front desk', 'Patient') to its ID prefix ('D', 'S', 'C', 'P'). */
function db_prefix_for_role(string $roleName): string
{
    $norm = strtolower(trim($roleName));
    if (str_contains($norm, 'doc')) {
        return 'D';
    }
    if (str_contains($norm, 'cash')) {
        return 'C';
    }
    if (str_contains($norm, 'pat')) {
        return 'P';
    }
    if ($norm === 'admin' || $norm === 'administrator') {
        return 'A';
    }
    return 'S';
}

/** Generate next available auto-incremented prefix ID (D-1001, S-1001, C-1001, P-1001) ensuring no duplicate ID ever exists. */
function db_next_user_id(string $prefix): string
{
    $prefix  = strtoupper(substr(trim($prefix), 0, 1));
    if (!in_array($prefix, ['D', 'S', 'C', 'P', 'A'], true)) {
        $prefix = 'S';
    }
    $pdo     = db();
    $maxNum  = 1000;
    $usedIds = [];

    $recordId = static function (string $rawId) use ($prefix, &$maxNum, &$usedIds): void {
        $rawId = strtoupper(str_replace(' ', '', trim($rawId)));
        if ($rawId === '') {
            return;
        }
        $usedIds[$rawId] = true;
        if (preg_match('/^([A-Z])-?0*(\d+)$/i', $rawId, $m)) {
            $p   = strtoupper($m[1]);
            $num = (int) $m[2];
            $norm = $p . '-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
            $usedIds[$norm] = true;
            if ($p === $prefix) {
                if ($num < 1000) {
                    $num += 1000;
                }
                $maxNum = max($maxNum, $num);
            }
        }
    };

    // 1. Check all accounts in DB
    $stmt = $pdo->query('SELECT user_id FROM accounts');
    foreach ($stmt->fetchAll() as $r) {
        $recordId((string) ($r['user_id'] ?? ''));
    }

    // 2. Check all patients in DB
    $stmt2 = $pdo->query('SELECT patient_id FROM patients');
    foreach ($stmt2->fetchAll() as $r) {
        $recordId((string) ($r['patient_id'] ?? ''));
    }

    // 3. Check archived records so archived IDs are never reused
    try {
        $stmt3 = $pdo->query('SELECT payload_json FROM archive_records');
        foreach ($stmt3->fetchAll() as $r) {
            $payload = !empty($r['payload_json']) ? json_decode((string) $r['payload_json'], true) : null;
            if (is_array($payload) && isset($payload['data']['user_id'])) {
                $recordId((string) $payload['data']['user_id']);
            }
        }
    } catch (Throwable) {
    }

    // 4. Check current in-memory session state if present
    foreach ($_SESSION['state']['users'] ?? [] as $u) {
        if (!empty($u[7])) {
            $recordId((string) $u[7]);
        }
    }
    foreach ($_SESSION['state']['patients'] ?? [] as $pt) {
        if (!empty($pt[8])) {
            $recordId((string) $pt[8]);
        }
    }

    $nextNum = $maxNum + 1;
    do {
        $candidate = $prefix . '-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        $nextNum++;
    } while (isset($usedIds[$candidate]));

    return $candidate;
}

/** Fetch an account row by email (used by Admin -> Edit user modal). */
function db_get_account_by_email(string $email): ?array
{
    if ($email === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) LIMIT 1');
    $stmt->execute([':em' => $email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Create a real user account in the `accounts` database table.
 * If $fromAdmin is false (public registration page), only P-ID (Patient) and one-time A-ID (Admin) are permitted;
 * Doctor (D) and Staff (S) accounts can only be created by the Admin ($fromAdmin = true).
 */
function db_create_account(array $input, bool $fromAdmin = false): array
{
    $pdo       = db();
    $userId    = strtoupper(trim((string) ($input['user_id'] ?? '')));
    $username  = strtolower(trim((string) ($input['username'] ?? '')));
    $email     = strtolower(trim((string) ($input['email'] ?? '')));
    $password  = (string) ($input['password'] ?? '');
    $fullName  = trim((string) ($input['full_name'] ?? ''));
    $dept      = trim((string) ($input['department'] ?? ''));
    $spec      = trim((string) ($input['specialty'] ?? ''));
    $license   = trim((string) ($input['license'] ?? ''));
    $rawBdate  = trim((string) ($input['birthdate'] ?? ''));
    $birthdate = $rawBdate !== '' && function_exists('format_birthdate') ? format_birthdate($rawBdate) : $rawBdate;
    if ($birthdate === '—') {
        $birthdate = '';
    }
    $status    = ($input['status'] ?? 'Active') === 'Disabled' ? 'Disabled' : 'Active';

    if ($userId === '' || !preg_match('/^([ASDPC])-?[A-Z0-9]+$/i', $userId, $m)) {
        return ['error' => 'User ID must start with A (Admin), S (Staff), C (Cashier), D (Doctor), or P (Patient) followed by numbers (e.g. P-1001).'];
    }
    if (preg_match('/^([ASDPC])-?0*(\d+)$/i', $userId, $nm)) {
        $userId = strtoupper($nm[1]) . '-' . str_pad((string) ((int) $nm[2]), 4, '0', STR_PAD_LEFT);
    }

    $prefix = strtoupper(substr($userId, 0, 1));

    // Public self-registration cannot create Doctor (D), Staff (S), or Cashier (C) accounts
    if (!$fromAdmin && in_array($prefix, ['D', 'S', 'C'], true)) {
        return ['error' => 'Doctor (D), Staff (S), and Cashier (C) accounts can only be created by the System Administrator in the Admin portal.'];
    }

    // STRICT ONE-TIME ADMIN RULE: Once an A-ID is used, no A-ID can EVER be used again!
    if ($prefix === 'A' || strcasecmp((string) ($input['role'] ?? ''), 'Admin') === 0 || strcasecmp((string) ($input['role'] ?? ''), 'Administrator') === 0) {
        if ($fromAdmin || db_admin_claimed()) {
            return ['error' => 'An Administrator account (A-ID) has already been registered. The A ID can never be used again.'];
        }
    }

    $role = match ($prefix) {
        'A' => 'Admin',
        'S' => 'Staff',
        'C' => 'Cashier',
        'D' => 'Doctor',
        'P' => 'Patient',
    };
    $roleLabel = match ($prefix) {
        'A' => 'Administrator',
        'S' => 'Staff',
        'C' => 'Cashier',
        'D' => 'Doctor',
        'P' => 'Patient',
    };

    if ($fullName === '' || $username === '' || $email === '') {
        return ['error' => 'Please fill in full name, username, and email address.'];
    }
    if ($prefix === 'P' && $birthdate === '') {
        return ['error' => 'Please enter the patient\'s birthday (date of birth).'];
    }
    if (mb_strlen($password) < 8) {
        return ['error' => 'Password must be at least 8 characters long.'];
    }

    // Check if user_id is already used in accounts
    $chkId = $pdo->prepare('SELECT id FROM accounts WHERE UPPER(user_id) = :uid LIMIT 1');
    $chkId->execute([':uid' => $userId]);
    if ($chkId->fetch()) {
        if ($fromAdmin && $prefix !== 'A') {
            $userId = db_next_user_id($prefix);
        } else {
            return ['error' => 'The ID "' . $userId . '" is already registered to an account and cannot be used again.'];
        }
    }

    // Check if username or email is already taken
    $chkUser = $pdo->prepare('SELECT id, username, email FROM accounts WHERE LOWER(username) = :u OR LOWER(email) = :e LIMIT 1');
    $chkUser->execute([':u' => $username, ':e' => $email]);
    if ($existing = $chkUser->fetch()) {
        if (strtolower((string) $existing['username']) === $username) {
            return ['error' => 'Username "' . $username . '" is already taken.'];
        }
        return ['error' => 'Email "' . $email . '" is already registered.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $now  = date('M d, Y · H:i');

    $ins = $pdo->prepare('INSERT INTO accounts
        (user_id, username, email, password_hash, full_name, role, user_role_label, status, department, specialty, license_number, birthdate, last_login, created_at)
        VALUES (:uid, :uname, :email, :hash, :fname, :role, :rlabel, :status, :dept, :spec, :lic, :bdate, :llogin, :cat)');
    $ins->execute([
        ':uid'    => $userId,
        ':uname'  => $username,
        ':email'  => $email,
        ':hash'   => $hash,
        ':fname'  => $fullName,
        ':role'   => $role,
        ':rlabel' => $roleLabel,
        ':status' => $status,
        ':dept'   => $dept,
        ':spec'   => $spec,
        ':lic'    => $license,
        ':bdate'  => $birthdate,
        ':llogin' => $fromAdmin ? 'Never' : 'Just now',
        ':cat'    => $now,
    ]);

    if ($prefix === 'A') {
        db_set_store('admin_id_claimed', '1');
    }

    return [
        'ok'      => true,
        'account' => [
            'user_id'         => $userId,
            'username'        => $username,
            'email'           => $email,
            'full_name'       => $fullName,
            'role'            => $role,
            'user_role_label' => $roleLabel,
            'status'          => $status,
            'department'      => $dept,
            'specialty'       => $spec,
            'license_number'  => $license,
            'birthdate'       => $birthdate,
        ],
    ];
}

/**
 * Verify credentials against the database `accounts` table.
 * Matches by User ID (e.g. A-0001, D-1001, S-1001, P-1001) and/or Username, Email, or Full Name,
 * then verifies the password hash.
 */
function db_verify_login(string $userId, string $usernameOrEmail, string $password, string $birthdate = ''): array
{
    $rawId = strtoupper(str_replace(' ', '', trim($userId)));
    if (preg_match('/^([ASDPC])-?0*(\d+)$/i', $rawId, $nm)) {
        $prefix     = strtoupper($nm[1]);
        $normNumber = (int) $nm[2];
        $paddedId   = $prefix . '-' . str_pad((string) $normNumber, 4, '0', STR_PAD_LEFT);
    } else {
        $prefix     = substr($rawId, 0, 1);
        $normNumber = -1;
        $paddedId   = $rawId;
    }

    if ($rawId === '' || !in_array($prefix, ['A', 'S', 'C', 'D', 'P'], true)) {
        return ['error' => 'invalid_id'];
    }
    if ($password === '') {
        return ['error' => 'wrong'];
    }

    $expectedRole = match ($prefix) {
        'A' => 'Admin',
        'S' => 'Staff',
        'C' => 'Cashier',
        'D' => 'Doctor',
        'P' => 'Patient',
    };
    $uQuery = strtolower(trim($usernameOrEmail));

    $pdo        = db();
    $candidates = [];

    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE role = :r');
    $stmt->execute([':r' => $expectedRole]);
    $roleAccounts = $stmt->fetchAll();

    foreach ($roleAccounts as $row) {
        $rowId     = strtoupper(str_replace(' ', '', (string) $row['user_id']));
        $idMatches = ($rowId === $rawId || $rowId === $paddedId || str_replace('-', '', $rowId) === str_replace('-', '', $rawId));
        if (!$idMatches && $normNumber > 0 && preg_match('/^[ASDPC]-?0*(\d+)$/i', $rowId, $rm)) {
            $idMatches = ((int) $rm[1] === $normNumber);
        }

        $userMatches = $uQuery !== '' && (
            strtolower((string) $row['username']) === $uQuery
            || strtolower((string) $row['email']) === $uQuery
            || strtolower((string) $row['full_name']) === $uQuery
            || strtolower(explode(' ', (string) $row['full_name'])[0]) === $uQuery
        );

        if ($idMatches || $userMatches) {
            $candidates[] = $row;
        }
    }

    if ($prefix === 'A' && !$candidates && count($roleAccounts) === 1) {
        $candidates[] = $roleAccounts[0];
    }

    $formattedBdate = $birthdate !== '' && function_exists('format_birthdate') ? format_birthdate($birthdate) : trim($birthdate);
    if ($formattedBdate === '—') {
        $formattedBdate = '';
    }

    foreach ($candidates as $acc) {
        if (password_verify($password, (string) $acc['password_hash'])) {
            if (($acc['status'] ?? 'Active') === 'Disabled') {
                return ['error' => 'disabled'];
            }
            $now = date('M d, Y · H:i');
            if ($formattedBdate !== '') {
                $up = $pdo->prepare('UPDATE accounts SET last_login = :ll, birthdate = :bd WHERE id = :id');
                $up->execute([':ll' => $now, ':bd' => $formattedBdate, ':id' => $acc['id']]);
                $acc['birthdate'] = $formattedBdate;
            } else {
                $up = $pdo->prepare('UPDATE accounts SET last_login = :ll WHERE id = :id');
                $up->execute([':ll' => $now, ':id' => $acc['id']]);
            }
            $acc['last_login'] = $now;
            return ['ok' => true, 'account' => $acc];
        }
    }

    return ['error' => 'wrong'];
}

/** Update an existing non-Admin account from Admin -> Users modal. */
function db_update_user_account(
    string $origEmail,
    string $fullName,
    string $email,
    string $username,
    string $uRoleLabel,
    string $status,
    string $dept,
    string $spec,
    string $license = '',
    string $newPassword = '',
    string $birthdate = ''
): array {
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:orig) OR LOWER(email) = LOWER(:em) LIMIT 1');
    $stmt->execute([':orig' => $origEmail, ':em' => $email]);
    $acc = $stmt->fetch();
    if (!$acc) {
        return ['error' => 'User account not found in database.'];
    }

    // Check username/email uniqueness against other accounts
    $chk = $pdo->prepare('SELECT id, username, email FROM accounts WHERE (LOWER(username) = :u OR LOWER(email) = :e) AND id != :id LIMIT 1');
    $chk->execute([':u' => strtolower($username), ':e' => strtolower($email), ':id' => $acc['id']]);
    if ($dup = $chk->fetch()) {
        if (strtolower((string) $dup['username']) === strtolower($username)) {
            return ['error' => 'Username "' . $username . '" is already used by another account.'];
        }
        return ['error' => 'Email "' . $email . '" is already used by another account.'];
    }

    $expectedPrefix = db_prefix_for_role($uRoleLabel);
    $role = match ($expectedPrefix) {
        'D'     => 'Doctor',
        'C'     => 'Cashier',
        'P'     => 'Patient',
        default => 'Staff',
    };
    $normRoleLabel = match ($expectedPrefix) {
        'D'     => 'Doctor',
        'C'     => 'Cashier',
        'P'     => 'Patient',
        default => 'Staff',
    };

    $currentUserId = strtoupper(trim((string) ($acc['user_id'] ?? '')));
    if ($currentUserId === '' || strtoupper(substr($currentUserId, 0, 1)) !== $expectedPrefix) {
        $currentUserId = db_next_user_id($expectedPrefix);
    }

    $fmtBdate = $birthdate !== '' && function_exists('format_birthdate')
        ? format_birthdate($birthdate)
        : (string) ($acc['birthdate'] ?? '');
    if ($fmtBdate === '—') {
        $fmtBdate = '';
    }

    $sql = 'UPDATE accounts SET user_id = :uid, full_name = :fn, username = :un, email = :em, role = :r, user_role_label = :rl, status = :st, department = :dp, specialty = :sp, license_number = :lc, birthdate = :bd';
    $params = [
        ':uid' => $currentUserId,
        ':fn'  => $fullName,
        ':un'  => strtolower($username),
        ':em'  => strtolower($email),
        ':r'   => $role,
        ':rl'  => $normRoleLabel,
        ':st'  => $status,
        ':dp'  => $dept,
        ':sp'  => $spec,
        ':lc'  => $license,
        ':bd'  => $fmtBdate,
        ':id'  => $acc['id'],
    ];
    if ($newPassword !== '') {
        if (mb_strlen($newPassword) < 8) {
            return ['error' => 'New password must be at least 8 characters long.'];
        }
        $sql .= ', password_hash = :pw';
        $params[':pw'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }
    $sql .= ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);

    return ['ok' => true, 'user_id' => $currentUserId, 'birthdate' => $fmtBdate];
}

function db_toggle_account_by_email(string $email, string $newStatus): void
{
    $stmt = db()->prepare('UPDATE accounts SET status = :st WHERE LOWER(email) = LOWER(:em)');
    $stmt->execute([':st' => $newStatus, ':em' => $email]);
}

/** Archive and remove a non-Admin user account so Admin can restore it later from Archive. */
function db_archive_user_account(string $email, string $archivedBy): ?array
{
    $pdo  = db();
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) AND role != 'Admin' LIMIT 1");
    $stmt->execute([':em' => $email]);
    $acc = $stmt->fetch();
    if (!$acc) {
        return null;
    }
    $del = $pdo->prepare('DELETE FROM accounts WHERE id = :id');
    $del->execute([':id' => $acc['id']]);

    return db_archive_record(
        $acc['full_name'] . ' (' . $acc['user_id'] . ')',
        'User account',
        $archivedBy,
        ['kind' => 'user_account', 'data' => $acc]
    );
}

/** Create an archive entry in `archive_records` with full JSON payload for restoration. */
function db_archive_record(string $subject, string $recordType, string $archivedBy, array $payload): array
{
    $pdo  = db();
    $stmt = $pdo->query('SELECT archive_code FROM archive_records');
    $max  = 1000;
    foreach ($stmt->fetchAll() as $r) {
        if (preg_match('/(\d+)$/', (string) $r['archive_code'], $m)) {
            $max = max($max, (int) $m[1]);
        }
    }
    $arcCode = '#ARC-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    $date    = date('M d, Y');

    $ins = $pdo->prepare('INSERT INTO archive_records (archive_code, subject_name, record_type, archived_date, archived_by, payload_json)
        VALUES (:c, :s, :t, :d, :b, :p)');
    $ins->execute([
        ':c' => $arcCode,
        ':s' => $subject,
        ':t' => $recordType,
        ':d' => $date,
        ':b' => $archivedBy,
        ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    return [$arcCode, $subject, $recordType, $date, $archivedBy];
}

/** Restore an archived item from `archive_records` back into its active table & state. */
function db_restore_archive_record(string $archiveCode, array &$state): ?array
{
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT * FROM archive_records WHERE archive_code = :c LIMIT 1');
    $stmt->execute([':c' => $archiveCode]);
    $arc = $stmt->fetch();

    $payload = $arc && !empty($arc['payload_json']) ? json_decode((string) $arc['payload_json'], true) : null;
    if ($arc) {
        $pdo->prepare('DELETE FROM archive_records WHERE id = :id')->execute([':id' => $arc['id']]);
    }
    $state['archive'] = array_values(array_filter(
        $state['archive'] ?? [],
        static fn ($r) => $r[0] !== $archiveCode
    ));

    if (!is_array($payload)) {
        return $arc ?: ['subject_name' => $archiveCode, 'record_type' => 'Record'];
    }

    $kind = (string) ($payload['kind'] ?? '');
    $data = $payload['data'] ?? null;

    if ($kind === 'user_account' && is_array($data)) {
        $ins = $pdo->prepare('INSERT INTO accounts
            (user_id, username, email, password_hash, full_name, role, user_role_label, status, department, specialty, license_number, last_login, created_at)
            VALUES (:uid, :un, :em, :pw, :fn, :r, :rl, :st, :dp, :sp, :lc, :ll, :ca)');
        try {
            $ins->execute([
                ':uid' => $data['user_id'],
                ':un'  => $data['username'],
                ':em'  => $data['email'],
                ':pw'  => $data['password_hash'],
                ':fn'  => $data['full_name'],
                ':r'   => $data['role'],
                ':rl'  => $data['user_role_label'],
                ':st'  => $data['status'] ?? 'Active',
                ':dp'  => $data['department'] ?? '',
                ':sp'  => $data['specialty'] ?? '',
                ':lc'  => $data['license_number'] ?? '',
                ':ll'  => $data['last_login'] ?? 'Never',
                ':ca'  => $data['created_at'] ?? date('M d, Y · H:i'),
            ]);
            array_unshift($state['users'], [
                $data['full_name'],
                $data['email'],
                $data['user_role_label'],
                $data['status'] ?? 'Active',
                $data['last_login'] ?? 'Never',
                $data['department'] ?? '',
                $data['specialty'] ?? '',
                $data['user_id'],
                $data['username'],
                $data['license_number'] ?? '',
            ]);
        } catch (Throwable) {
            // Ignore duplicate if user was recreated in the meantime
        }
    } elseif (in_array($kind, ['departments', 'rooms', 'medications'], true) && is_array($data)) {
        $exists = false;
        foreach ($state['crud'][$kind] ?? [] as $existing) {
            if (strcasecmp((string) $existing[0], (string) $data[0]) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $state['crud'][$kind][] = $data;
        }
    } elseif ($kind === 'role' && is_array($data)) {
        $exists = false;
        foreach ($state['roles'] ?? [] as $existing) {
            if (strcasecmp((string) $existing[0], (string) $data[0]) === 0) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $state['roles'][] = $data;
        }
    }

    return $arc ?: ['subject_name' => $archiveCode, 'record_type' => $kind];
}

function db_change_account_password(string $userId, string $currentPassword, string $newPassword): array
{
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE UPPER(user_id) = UPPER(:uid) LIMIT 1');
    $stmt->execute([':uid' => $userId]);
    $acc = $stmt->fetch();
    if (!$acc) {
        return ['error' => 'Account not found in database.'];
    }
    if (!password_verify($currentPassword, (string) $acc['password_hash'])) {
        return ['error' => 'Your current password is incorrect.'];
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $up   = $pdo->prepare('UPDATE accounts SET password_hash = :h WHERE id = :id');
    $up->execute([':h' => $hash, ':id' => $acc['id']]);
    return ['ok' => true];
}

/** Load the hospital state from relational database tables. */
function db_load_state(): array
{
    $pdo = db();

    // 1. Users (non-Admin staff/doctors/patients from `accounts`)
    $users   = [];
    $seenIds = [];
    $stmt    = $pdo->query("SELECT * FROM accounts WHERE role != 'Admin' ORDER BY id ASC");
    $rawUsers = $stmt->fetchAll();
    foreach ($rawUsers as &$u) {
        $rl = (string) ($u['user_role_label'] ?? '');
        if ($rl === '' || strcasecmp($rl, 'Front desk') === 0 || strcasecmp($rl, 'Staff') === 0) {
            $rl = $u['role'] === 'Doctor' ? 'Doctor' : ($u['role'] === 'Cashier' ? 'Cashier' : ($u['role'] === 'Patient' ? 'Patient' : 'Staff'));
        }
        $expPrefix = db_prefix_for_role($rl);
        $uid       = strtoupper(trim((string) ($u['user_id'] ?? '')));
        if ($uid === '' || strtoupper(substr($uid, 0, 1)) !== $expPrefix || isset($seenIds[$uid])) {
            $uid = db_next_user_id($expPrefix);
            $pdo->prepare('UPDATE accounts SET user_id = :uid, user_role_label = :rl WHERE id = :id')
                ->execute([':uid' => $uid, ':rl' => $rl, ':id' => $u['id']]);
        } elseif ($u['user_role_label'] !== $rl) {
            $pdo->prepare('UPDATE accounts SET user_role_label = :rl WHERE id = :id')
                ->execute([':rl' => $rl, ':id' => $u['id']]);
        }
        $seenIds[$uid]        = true;
        $u['user_id']         = $uid;
        $u['user_role_label'] = $rl;
    }
    unset($u);
    foreach (array_reverse($rawUsers) as $u) {
        $users[] = [
            $u['full_name'],
            $u['email'],
            $u['user_role_label'],
            $u['status'],
            $u['last_login'],
            $u['department'],
            $u['specialty'],
            $u['user_id'],
            $u['username'],
            $u['license_number'],
            $u['birthdate'] ?? '',
        ];
    }

    // 2. Patients
    $patients = [];
    $stmt     = $pdo->query('SELECT * FROM patients ORDER BY id DESC');
    foreach ($stmt->fetchAll() as $p) {
        $patients[] = [
            $p['full_name'],
            $p['birthdate'],
            $p['phone'],
            $p['insurance_no'],
            $p['avatar'],
            $p['sex'],
            $p['blood_type'],
            $p['address'],
            $p['patient_id'],
        ];
    }

    // Sync any Patient accounts with $patients so their birthday and ID are always present across all UIs
    foreach ($users as $u) {
        if ($u[2] === 'Patient') {
            $uBdate = trim((string) ($u[10] ?? ''));
            $foundPt = false;
            foreach ($patients as &$pt) {
                if (strcasecmp((string) ($pt[8] ?? ''), (string) $u[7]) === 0 || strcasecmp((string) $pt[0], (string) $u[0]) === 0) {
                    $pt[8] = $u[7];
                    if ($uBdate !== '' && ($pt[1] === '' || $pt[1] === '—')) {
                        $pt[1] = $uBdate;
                    }
                    $foundPt = true;
                    break;
                }
            }
            unset($pt);
            if (!$foundPt) {
                array_unshift($patients, [
                    $u[0],
                    $uBdate !== '' ? $uBdate : '—',
                    '—',
                    'HIN-' . rand(100, 999) . '-' . rand(100, 999),
                    function_exists('initials') ? initials((string) $u[0], 2) : 'PT',
                    'Not specified',
                    'Not specified',
                    '—',
                    $u[7],
                ]);
            }
        }
    }

    // 3. CRUD: departments, rooms, medications
    $departments = [];
    foreach ($pdo->query('SELECT * FROM departments ORDER BY id ASC')->fetchAll() as $d) {
        $departments[] = [$d['name'], $d['head_doctor'], $d['staff_count'], $d['location']];
    }
    $rooms = [];
    foreach ($pdo->query('SELECT * FROM rooms ORDER BY room_number ASC')->fetchAll() as $r) {
        $rooms[] = [$r['room_number'], $r['ward'], $r['status'], $r['patient_name']];
    }
    $medications = [];
    foreach ($pdo->query('SELECT * FROM medications ORDER BY id ASC')->fetchAll() as $m) {
        $medications[] = [$m['name'], $m['category'], $m['strength'], $m['stock']];
    }

    // 4. Appointments
    $appointments = [];
    foreach ($pdo->query('SELECT * FROM appointments ORDER BY id ASC')->fetchAll() as $a) {
        $appointments[] = [
            $a['slot_time'],
            $a['patient_name'],
            $a['visit_type'],
            $a['status'],
            $a['complaint'],
            $a['doctor_name'],
            $a['department'],
            $a['appt_date'],
            $a['patient_id'] ?? '',
        ];
    }

    // 5. Admissions & Discharges
    $admissions = [];
    $admittedRoomMap = [];
    foreach ($pdo->query('SELECT * FROM admissions ORDER BY id DESC')->fetchAll() as $ad) {
        $admissions[] = [$ad['patient_name'], $ad['room_number'], $ad['ward'], $ad['admitted_date'], $ad['doctor_name'], $ad['reason']];
        if (!empty($ad['room_number']) && !empty($ad['patient_name']) && $ad['patient_name'] !== '—') {
            $admittedRoomMap[$ad['room_number']] = $ad['patient_name'];
        }
    }
    foreach ($rooms as &$rmRow) {
        $rmKey = (string) ($rmRow[0] ?? '');
        $rmPt  = $admittedRoomMap[$rmKey] ?? trim((string) ($rmRow[3] ?? '—'));
        if ($rmPt === '' || $rmPt === '—') {
            $rmRow[2] = 'Available';
            $rmRow[3] = '—';
        } else {
            $rmRow[2] = 'Occupied';
            $rmRow[3] = $rmPt;
        }
    }
    unset($rmRow);
    $discharges = [];
    foreach ($pdo->query('SELECT * FROM discharges ORDER BY id ASC')->fetchAll() as $dc) {
        $discharges[] = [$dc['patient_name'], $dc['room_number'], $dc['ward'], $dc['discharged_date'], $dc['proof_path'], (int) $dc['discharged_ts']];
    }

    // 6. Bills, Receipts & Payments
    $bills    = [];
    $receipts = [];
    foreach ($pdo->query('SELECT * FROM bills ORDER BY id DESC')->fetchAll() as $idx => $b) {
        $bNo    = !empty($b['bill_no']) ? (string) $b['bill_no'] : ('BILL-' . str_pad((string) (1000 + (int) ($b['id'] ?? ($idx + 1))), 4, '0', STR_PAD_LEFT));
        $bItems = !empty($b['items_json']) ? json_decode((string) $b['items_json'], true) : null;
        $bPaid  = isset($b['paid_amount']) && $b['paid_amount'] !== ''
            ? (float) preg_replace('/[^0-9.\-]/', '', (string) $b['paid_amount'])
            : (($b['status'] ?? '') === 'Paid' ? (float) preg_replace('/[^0-9.\-]/', '', (string) $b['amount']) : 0.0);
        $bills[] = normalize_bill_row([
            $b['patient_name'],
            $b['amount'],
            $b['status'],
            $b['issued_date'],
            $bNo,
            $bPaid,
            is_array($bItems) && $bItems ? $bItems : null,
        ], $idx);
        if (!empty($b['receipt_path'])) {
            $receipts[$b['patient_name']] = $b['receipt_path'];
        }
    }

    $payments = [];
    foreach ($pdo->query('SELECT * FROM payments ORDER BY id DESC')->fetchAll() as $py) {
        $payments[] = [
            'receipt_no'      => (string) ($py['receipt_no'] ?? 'OR-1001'),
            'bill_no'         => (string) ($py['bill_no'] ?? 'BILL-1001'),
            'patient_name'    => (string) ($py['patient_name'] ?? '—'),
            'amount_paid'     => parse_money($py['amount_paid'] ?? 0),
            'amount_received' => parse_money($py['amount_received'] ?? 0),
            'change_due'      => parse_money($py['change_due'] ?? 0),
            'payment_method'  => (string) ($py['payment_method'] ?? 'Cash'),
            'reference_no'    => (string) ($py['reference_no'] ?? ''),
            'cashier_name'    => (string) ($py['cashier_name'] ?? 'Hospital Cashier'),
            'paid_date'       => (string) ($py['paid_date'] ?? date('Y-m-d')),
            'paid_at'         => (string) ($py['paid_at'] ?? date('M d, Y · H:i')),
            'items'           => !empty($py['items_json']) ? (json_decode((string) $py['items_json'], true) ?: []) : [],
        ];
    }

    // Automatically generate a bill for any patient/admission/appointment if bills is currently empty
    if (!$bills && $patients) {
        foreach (array_slice($patients, 0, 3) as $pIdx => $ptRow) {
            $sampleTotal = [240.00, 480.00, 185.00][$pIdx % 3];
            $bills[] = normalize_bill_row([
                $ptRow[0],
                format_money($sampleTotal),
                'Unpaid',
                date('M d, Y'),
                'BILL-' . str_pad((string) (1001 + $pIdx), 4, '0', STR_PAD_LEFT),
                0.0,
                default_bill_items($sampleTotal),
            ], $pIdx);
        }
    }

    // 7. Medical Records
    $records = [];
    foreach ($pdo->query('SELECT * FROM medical_records ORDER BY id DESC')->fetchAll() as $mr) {
        $pt = $mr['patient_name'];
        $records[$pt][] = [
            'id'            => $mr['record_code'],
            'patient_id'    => $mr['patient_id'] ?? '',
            'patient_name'  => $pt,
            'doctor_id'     => $mr['doctor_id'] ?? '',
            'doctor'        => $mr['doctor_name'],
            'date'          => $mr['visit_date'],
            'visit_type'    => $mr['visit_type'],
            'complaint'     => $mr['complaint'],
            'notes'         => $mr['notes'],
            'diagnoses'     => json_decode((string) $mr['diagnoses_json'], true) ?: [],
            'prescriptions' => json_decode((string) $mr['prescriptions_json'], true) ?: [],
            'lab_orders'    => json_decode((string) $mr['lab_orders_json'], true) ?: [],
            'status'        => $mr['status'],
        ];
    }

    // 8. Lab Results
    $labResults = [];
    foreach ($pdo->query('SELECT * FROM lab_results ORDER BY id DESC')->fetchAll() as $lr) {
        $labResults[] = [
            $lr['test_code'],
            $lr['patient_name'],
            $lr['test_name'],
            $lr['status'],
            $lr['collected_date'],
            $lr['patient_id'] ?? '',
            $lr['priority'] ?? 'Routine',
            $lr['clinical_notes'] ?? '',
            $lr['record_code'] ?? '',
            $lr['doctor_name'] ?? '',
            $lr['results_json'] ?? '',
        ];
    }

    // 8b. Prescriptions
    $prescriptions = [];
    try {
        foreach ($pdo->query('SELECT * FROM prescriptions ORDER BY id DESC')->fetchAll() as $rx) {
            $prescriptions[] = [
                'rx_code'         => (string) $rx['rx_code'],
                'record_code'     => (string) ($rx['record_code'] ?? ''),
                'patient_id'      => (string) ($rx['patient_id'] ?? ''),
                'patient_name'    => (string) $rx['patient_name'],
                'doctor_name'     => (string) ($rx['doctor_name'] ?? ''),
                'medication'      => (string) $rx['medication'],
                'dosage'          => (string) ($rx['dosage'] ?? ''),
                'frequency'       => (string) ($rx['frequency'] ?? ''),
                'duration'        => (string) ($rx['duration'] ?? ''),
                'prescribed_date' => (string) $rx['prescribed_date'],
                'status'          => (string) ($rx['status'] ?? 'Active'),
            ];
        }
    } catch (Throwable) {
    }

    // 9. Roles from `roles` table (seeded on first run)
    $roles = [];
    foreach ($pdo->query('SELECT * FROM roles ORDER BY id ASC')->fetchAll() as $rl) {
        $rName = strcasecmp((string) $rl['role_name'], 'Front desk') === 0 ? 'Staff' : (string) $rl['role_name'];
        $roles[] = [
            $rName,
            $rl['description'],
            (int) $rl['user_count'],
            json_decode((string) $rl['permissions_json'], true) ?: [],
            $rl['icon'] ?: 'shield',
        ];
    }
    if (!$roles) {
        $roles = seed_roles();
    }
    $hasCashierRole = false;
    foreach ($roles as $rlCheck) {
        if (strcasecmp((string) $rlCheck[0], 'Cashier') === 0) {
            $hasCashierRole = true;
            break;
        }
    }
    if (!$hasCashierRole) {
        $roles[] = ['Cashier', 'Billing, payment collection, official receipts, and daily reports', 0, ['View patients', 'Manage billing'], 'card'];
    }
    foreach ($roles as &$rr) {
        if ($rr[0] === 'Doctor') {
            $rr[2] = count(array_filter($users, static fn ($u) => $u[2] === 'Doctor'));
        } elseif ($rr[0] === 'Staff' || $rr[0] === 'Front desk') {
            $rr[0] = 'Staff';
            $rr[2] = count(array_filter($users, static fn ($u) => $u[2] === 'Staff' || $u[2] === 'Front desk'));
        } elseif ($rr[0] === 'Cashier') {
            $rr[2] = count(array_filter($users, static fn ($u) => $u[2] === 'Cashier'));
        } elseif ($rr[0] === 'Patient') {
            $ptUserCount = count(array_filter($users, static fn ($u) => $u[2] === 'Patient'));
            $rr[2]       = max($ptUserCount, count($patients));
        } else {
            $rr[2] = count(array_filter($users, static fn ($u) => $u[2] === $rr[0]));
        }
    }
    unset($rr);

    // 10. Archive Records from `archive_records` table
    $archive = [];
    foreach ($pdo->query('SELECT * FROM archive_records ORDER BY id DESC')->fetchAll() as $ar) {
        $archive[] = [
            $ar['archive_code'],
            $ar['subject_name'],
            $ar['record_type'],
            $ar['archived_date'],
            $ar['archived_by'],
        ];
    }

    // 11. Audit Logs from `audit_logs` table
    $audit = [];
    foreach ($pdo->query('SELECT * FROM audit_logs ORDER BY id DESC')->fetchAll() as $al) {
        $audit[] = [$al['actor_name'], $al['action'], $al['table_name'], $al['record_id'], $al['logged_at'], $al['actor_role']];
    }

    $notifications = db_get_store('notifications');
    if (!is_array($notifications)) {
        $notifications = seed_notifications();
    }

    return [
        '_v'            => 12,
        'patients'      => $patients,
        'users'         => $users,
        'crud'          => [
            'departments' => $departments,
            'rooms'       => $rooms,
            'medications' => $medications,
        ],
        'admissions'    => $admissions,
        'bills'         => $bills,
        'payments'      => $payments,
        'discharges'    => $discharges,
        'receipts'      => $receipts,
        'roles'         => $roles,
        'notifications' => $notifications,
        'appointments'  => $appointments,
        'records'       => $records,
        'prescriptions' => $prescriptions,
        'lab_results'   => $labResults,
        'archive'       => $archive,
        'audit'         => $audit,
    ];
}

/** Save the current in-memory state back to the relational database tables. */
function db_save_state(array $state): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Roles
        $pdo->exec('DELETE FROM roles');
        $insRl = $pdo->prepare('INSERT INTO roles (role_name, description, user_count, permissions_json, icon) VALUES (:rn, :ds, :uc, :pm, :ic)');
        foreach ($state['roles'] ?? [] as $r) {
            $insRl->execute([
                ':rn' => $r[0] ?? 'Role',
                ':ds' => $r[1] ?? '',
                ':uc' => (int) ($r[2] ?? 0),
                ':pm' => json_encode($r[3] ?? []),
                ':ic' => $r[4] ?? 'shield',
            ]);
        }

        // Patients
        $pdo->exec('DELETE FROM patients');
        $insPt = $pdo->prepare('INSERT INTO patients (patient_id, full_name, birthdate, phone, insurance_no, avatar, sex, blood_type, address, created_at) VALUES (:pid, :fn, :bd, :ph, :in, :av, :sx, :bt, :ad, :ca)');
        foreach (array_reverse($state['patients'] ?? []) as $idx => $p) {
            $pid = $p[8] ?? ('P-' . str_pad((string) (1001 + $idx), 4, '0', STR_PAD_LEFT));
            $insPt->execute([
                ':pid' => $pid,
                ':fn'  => $p[0] ?? '—',
                ':bd'  => $p[1] ?? '—',
                ':ph'  => $p[2] ?? '—',
                ':in'  => $p[3] ?? '—',
                ':av'  => $p[4] ?? 'PT',
                ':sx'  => $p[5] ?? 'Not specified',
                ':bt'  => $p[6] ?? 'Not specified',
                ':ad'  => $p[7] ?? '—',
                ':ca'  => date('Y-m-d H:i:s'),
            ]);
        }

        // Departments
        $pdo->exec('DELETE FROM departments');
        $insDp = $pdo->prepare('INSERT INTO departments (name, head_doctor, staff_count, location) VALUES (:n, :h, :s, :l)');
        foreach ($state['crud']['departments'] ?? [] as $d) {
            $insDp->execute([':n' => $d[0], ':h' => $d[1] ?? 'Unassigned', ':s' => (string) ($d[2] ?? '0'), ':l' => $d[3] ?? 'Main Wing']);
        }

        // Rooms
        $pdo->exec('DELETE FROM rooms');
        $insRm = $pdo->prepare('INSERT INTO rooms (room_number, ward, status, patient_name) VALUES (:r, :w, :s, :p)');
        foreach ($state['crud']['rooms'] ?? [] as $r) {
            $rPt = trim((string) ($r[3] ?? '—'));
            if ($rPt === '') {
                $rPt = '—';
            }
            $rSt = ($rPt !== '—') ? 'Occupied' : 'Available';
            $insRm->execute([':r' => $r[0], ':w' => $r[1] ?? 'General Ward', ':s' => $rSt, ':p' => $rPt]);
        }

        // Medications
        $pdo->exec('DELETE FROM medications');
        $insMd = $pdo->prepare('INSERT INTO medications (name, category, strength, stock) VALUES (:n, :c, :st, :sk)');
        foreach ($state['crud']['medications'] ?? [] as $m) {
            $insMd->execute([':n' => $m[0], ':c' => $m[1] ?? 'General', ':st' => $m[2] ?? '', ':sk' => $m[3] ?? '0 units']);
        }

        // Appointments
        $pdo->exec('DELETE FROM appointments');
        $insAp = $pdo->prepare('INSERT INTO appointments (patient_id, slot_time, patient_name, visit_type, status, complaint, doctor_name, department, appt_date) VALUES (:pid, :tm, :pt, :vt, :st, :cp, :dr, :dp, :dt)');
        foreach ($state['appointments'] ?? [] as $a) {
            $ptName = (string) ($a[1] ?? '—');
            $ptId   = (string) ($a[8] ?? '');
            if ($ptId === '' && $ptName !== '—') {
                $resolved = resolve_patient_identity($state, $ptName);
                $ptId     = $resolved['id'];
            }
            $insAp->execute([
                ':pid' => $ptId,
                ':tm'  => $a[0] ?? '09:00',
                ':pt'  => $ptName,
                ':vt'  => $a[2] ?? 'Consultation',
                ':st'  => $a[3] ?? 'Confirmed',
                ':cp'  => $a[4] ?? '',
                ':dr'  => $a[5] ?? '—',
                ':dp'  => $a[6] ?? 'General Medicine',
                ':dt'  => $a[7] ?? date('M d, Y'),
            ]);
        }

        // Admissions
        $pdo->exec('DELETE FROM admissions');
        $insAd = $pdo->prepare('INSERT INTO admissions (patient_name, room_number, ward, admitted_date, doctor_name, reason) VALUES (:pt, :rm, :wd, :dt, :dr, :rs)');
        foreach (array_reverse($state['admissions'] ?? []) as $ad) {
            $insAd->execute([
                ':pt' => $ad[0] ?? '—',
                ':rm' => $ad[1] ?? '—',
                ':wd' => $ad[2] ?? 'General Ward',
                ':dt' => $ad[3] ?? date('M d, Y'),
                ':dr' => $ad[4] ?? 'Unassigned',
                ':rs' => $ad[5] ?? '',
            ]);
        }

        // Discharges
        $pdo->exec('DELETE FROM discharges');
        $insDc = $pdo->prepare('INSERT INTO discharges (patient_name, room_number, ward, discharged_date, proof_path, discharged_ts) VALUES (:pt, :rm, :wd, :dt, :pf, :ts)');
        foreach ($state['discharges'] ?? [] as $dc) {
            $insDc->execute([
                ':pt' => $dc[0] ?? '—',
                ':rm' => $dc[1] ?? '—',
                ':wd' => $dc[2] ?? 'General Ward',
                ':dt' => $dc[3] ?? date('M d, Y'),
                ':pf' => $dc[4] ?? null,
                ':ts' => (int) ($dc[5] ?? time()),
            ]);
        }

        // Bills
        $pdo->exec('DELETE FROM bills');
        $insBl = $pdo->prepare('INSERT INTO bills (bill_no, patient_name, amount, paid_amount, status, issued_date, receipt_path, items_json) VALUES (:bn, :pt, :am, :pa, :st, :dt, :rc, :it)');
        foreach (array_reverse($state['bills'] ?? []) as $idx => $b) {
            $nb     = normalize_bill_row($b, $idx);
            $ptName = $nb[0];
            $insBl->execute([
                ':bn' => $nb[4],
                ':pt' => $ptName,
                ':am' => $nb[1],
                ':pa' => number_format((float) $nb[5], 2, '.', ''),
                ':st' => $nb[2],
                ':dt' => $nb[3],
                ':rc' => $state['receipts'][$ptName] ?? null,
                ':it' => json_encode($nb[6] ?? []),
            ]);
        }

        // Payments
        $pdo->exec('DELETE FROM payments');
        $insPy = $pdo->prepare('INSERT INTO payments (receipt_no, bill_no, patient_name, amount_paid, amount_received, change_due, payment_method, reference_no, cashier_name, paid_date, paid_at, items_json) VALUES (:rn, :bn, :pt, :ap, :ar, :cd, :pm, :rf, :cn, :pd, :pa, :it)');
        foreach (array_reverse($state['payments'] ?? []) as $py) {
            $insPy->execute([
                ':rn' => $py['receipt_no'] ?? ('OR-' . rand(1000, 9999)),
                ':bn' => $py['bill_no'] ?? 'BILL-1001',
                ':pt' => $py['patient_name'] ?? '—',
                ':ap' => number_format(parse_money($py['amount_paid'] ?? 0), 2, '.', ''),
                ':ar' => number_format(parse_money($py['amount_received'] ?? 0), 2, '.', ''),
                ':cd' => number_format(parse_money($py['change_due'] ?? 0), 2, '.', ''),
                ':pm' => $py['payment_method'] ?? 'Cash',
                ':rf' => $py['reference_no'] ?? '',
                ':cn' => $py['cashier_name'] ?? 'Hospital Cashier',
                ':pd' => $py['paid_date'] ?? date('Y-m-d'),
                ':pa' => $py['paid_at'] ?? date('M d, Y · H:i'),
                ':it' => json_encode($py['items'] ?? []),
            ]);
        }

        // Medical Records
        $pdo->exec('DELETE FROM medical_records');
        $insMr = $pdo->prepare('INSERT INTO medical_records (record_code, patient_id, patient_name, doctor_id, doctor_name, visit_date, visit_type, complaint, notes, diagnoses_json, prescriptions_json, lab_orders_json, status) VALUES (:rc, :pid, :pt, :did, :dr, :dt, :vt, :cp, :nt, :dg, :rx, :lb, :st)');
        $allPrescriptionsToSave = [];
        foreach ($state['records'] ?? [] as $ptName => $recList) {
            $ptResolved = resolve_patient_identity($state, $ptName);
            foreach (array_reverse($recList) as $r) {
                $recPtId   = !empty($r['patient_id']) ? (string) $r['patient_id'] : $ptResolved['id'];
                $recPtName = !empty($r['patient_name']) ? (string) $r['patient_name'] : $ptName;
                $recDocId  = (string) ($r['doctor_id'] ?? '');
                $recDoc    = (string) ($r['doctor'] ?? '—');
                $recCode   = $r['id'] ?? ('REC-' . rand(1000, 9999));
                $recDate   = $r['date'] ?? date('M d, Y');
                $recStatus = $r['status'] ?? 'Completed';

                $insMr->execute([
                    ':rc'  => $recCode,
                    ':pid' => $recPtId,
                    ':pt'  => $recPtName,
                    ':did' => $recDocId,
                    ':dr'  => $recDoc,
                    ':dt'  => $recDate,
                    ':vt'  => $r['visit_type'] ?? 'Consultation',
                    ':cp'  => $r['complaint'] ?? '',
                    ':nt'  => $r['notes'] ?? '',
                    ':dg'  => json_encode($r['diagnoses'] ?? []),
                    ':rx'  => json_encode($r['prescriptions'] ?? []),
                    ':lb'  => json_encode($r['lab_orders'] ?? []),
                    ':st'  => $recStatus,
                ]);

                // Collect prescriptions from this record
                foreach ($r['prescriptions'] ?? [] as $rxIdx => $rx) {
                    $med = trim((string) ($rx[0] ?? ''));
                    if ($med !== '' && $med !== 'Select medication') {
                        $allPrescriptionsToSave[] = [
                            'rx_code'         => 'RX-' . strtoupper(substr(md5($recCode . '_' . $med . '_' . $rxIdx), 0, 6)),
                            'record_code'     => $recCode,
                            'patient_id'      => $recPtId,
                            'patient_name'    => $recPtName,
                            'doctor_name'     => $recDoc,
                            'medication'      => $med,
                            'dosage'          => (string) ($rx[1] ?? 'As directed'),
                            'frequency'       => (string) ($rx[2] ?? 'Once daily'),
                            'duration'        => (string) ($rx[3] ?? '30 days'),
                            'prescribed_date' => $recDate,
                            'status'          => 'Active',
                        ];
                    }
                }
            }
        }

        // Dedicated Prescriptions Table
        try {
            $pdo->exec('DELETE FROM prescriptions');
            $insRx = $pdo->prepare('INSERT INTO prescriptions (rx_code, record_code, patient_id, patient_name, doctor_name, medication, dosage, frequency, duration, prescribed_date, status) VALUES (:rc, :rec, :pid, :pt, :dr, :md, :ds, :fq, :du, :dt, :st)');
            // Also merge any standalone $state['prescriptions']
            foreach ($state['prescriptions'] ?? [] as $stRx) {
                $stMed = trim((string) ($stRx['medication'] ?? ($stRx['med'] ?? '')));
                if ($stMed === '' || $stMed === 'Select medication') {
                    continue;
                }
                $allPrescriptionsToSave[] = [
                    'rx_code'         => $stRx['rx_code'] ?? ('RX-' . rand(1000, 9999)),
                    'record_code'     => $stRx['record_code'] ?? '',
                    'patient_id'      => $stRx['patient_id'] ?? '',
                    'patient_name'    => $stRx['patient_name'] ?? '—',
                    'doctor_name'     => $stRx['doctor_name'] ?? ($stRx['doctor'] ?? '—'),
                    'medication'      => $stMed,
                    'dosage'          => $stRx['dosage'] ?? 'As directed',
                    'frequency'       => $stRx['frequency'] ?? 'Once daily',
                    'duration'        => $stRx['duration'] ?? '30 days',
                    'prescribed_date' => $stRx['prescribed_date'] ?? ($stRx['date'] ?? date('M d, Y')),
                    'status'          => $stRx['status'] ?? 'Active',
                ];
            }
            $seenRxCodes = [];
            foreach ($allPrescriptionsToSave as $rxItem) {
                if (isset($seenRxCodes[$rxItem['rx_code']])) {
                    continue;
                }
                $seenRxCodes[$rxItem['rx_code']] = true;
                $insRx->execute([
                    ':rc'  => $rxItem['rx_code'],
                    ':rec' => $rxItem['record_code'],
                    ':pid' => $rxItem['patient_id'],
                    ':pt'  => $rxItem['patient_name'],
                    ':dr'  => $rxItem['doctor_name'],
                    ':md'  => $rxItem['medication'],
                    ':ds'  => $rxItem['dosage'],
                    ':fq'  => $rxItem['frequency'],
                    ':du'  => $rxItem['duration'],
                    ':dt'  => $rxItem['prescribed_date'],
                    ':st'  => $rxItem['status'],
                ]);
            }
        } catch (Throwable) {
        }

        // Lab Results
        $pdo->exec('DELETE FROM lab_results');
        $insLr = $pdo->prepare('INSERT INTO lab_results (test_code, record_code, patient_id, patient_name, doctor_name, test_name, priority, status, clinical_notes, results_json, collected_date) VALUES (:tc, :rec, :pid, :pt, :dr, :tn, :pr, :st, :nt, :rj, :dt)');
        foreach (array_reverse($state['lab_results'] ?? []) as $lr) {
            $lrPtName = (string) ($lr[1] ?? '—');
            $lrPtId   = (string) ($lr[5] ?? '');
            if ($lrPtId === '' && $lrPtName !== '—') {
                $resolved = resolve_patient_identity($state, $lrPtName);
                $lrPtId   = $resolved['id'];
            }
            $insLr->execute([
                ':tc'  => $lr[0] ?? 'LAB-0000',
                ':rec' => (string) ($lr[8] ?? ''),
                ':pid' => $lrPtId,
                ':pt'  => $lrPtName,
                ':dr'  => (string) ($lr[9] ?? ''),
                ':tn'  => $lr[2] ?? 'Test',
                ':pr'  => (string) ($lr[6] ?? 'Routine'),
                ':st'  => $lr[3] ?? 'Pending',
                ':nt'  => (string) ($lr[7] ?? ''),
                ':rj'  => (string) ($lr[10] ?? ''),
                ':dt'  => $lr[4] ?? date('M d, Y'),
            ]);
        }

        // Audit Logs
        $pdo->exec('DELETE FROM audit_logs');
        $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
        foreach (array_reverse($state['audit'] ?? []) as $al) {
            $insAl->execute([
                ':an' => $al[0] ?? 'System',
                ':ac' => $al[1] ?? 'Updated',
                ':tb' => $al[2] ?? 'system',
                ':ri' => $al[3] ?? '#0',
                ':la' => $al[4] ?? date('M d, Y · H:i'),
                ':ar' => $al[5] ?? 'System',
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    db_set_store('notifications', $state['notifications'] ?? []);
}
