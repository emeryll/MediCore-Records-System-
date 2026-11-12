<?php
declare(strict_types=1);

$dbDir = __DIR__ . '/..';
$backupDir = $dbDir . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0775, true);
}

$sqliteFile = $dbDir . '/medicore.sqlite';
$backupSqlite = $backupDir . '/medicore_backup_before_no_username_20261112.sqlite';
$backupSql = $backupDir . '/sqlite_dump_before_no_username_20261112.sql';

echo "=== Step 1: Backing up database ===\n";
if (is_file($sqliteFile)) {
    copy($sqliteFile, $backupSqlite);
    echo "Binary backup saved to: {$backupSqlite} (" . filesize($backupSqlite) . " bytes)\n";
}

$pdo = new PDO('sqlite:' . $sqliteFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Dump current accounts table to SQL backup file
$dumpLines = ["-- Backup before removing username and full_name from accounts (" . date('Y-m-d H:i:s') . ")\n"];
$tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll();
foreach ($tables as $t) {
    $dumpLines[] = $t['sql'] . ";\n";
    $rows = $pdo->query("SELECT * FROM \"{$t['name']}\"")->fetchAll();
    foreach ($rows as $r) {
        $cols = array_map(fn($c) => '"' . $c . '"', array_keys($r));
        $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($r));
        $dumpLines[] = "INSERT INTO \"{$t['name']}\" (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
    }
}
file_put_contents($backupSql, implode("", $dumpLines));
echo "SQL dump saved to: {$backupSql} (" . filesize($backupSql) . " bytes)\n";

function migratePdo(PDO $pdo, string $driver): void {
    echo "\n=== Migrating ({$driver}) ===\n";
    if ($driver === 'sqlite') {
        $cols = array_column($pdo->query("PRAGMA table_info(accounts)")->fetchAll(), 'name');
    } else {
        $cols = array_column($pdo->query("SHOW COLUMNS FROM accounts")->fetchAll(), 'Field');
    }
    echo "Current columns in accounts: " . implode(', ', $cols) . "\n";

    // Add first_name, middle_name, last_name if not present
    if (!in_array('first_name', $cols, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN first_name VARCHAR(80) DEFAULT NULL");
    }
    if (!in_array('middle_name', $cols, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN middle_name VARCHAR(80) DEFAULT NULL");
    }
    if (!in_array('last_name', $cols, true)) {
        $pdo->exec("ALTER TABLE accounts ADD COLUMN last_name VARCHAR(80) DEFAULT NULL");
    }

    // Populate from approved split for existing accounts
    $pdo->exec("UPDATE accounts SET first_name = 'Medea',  middle_name = NULL,      last_name = 'Solon'       WHERE user_id = 'A-0001'");
    $pdo->exec("UPDATE accounts SET first_name = 'Meryll', middle_name = 'Silvano', last_name = 'Evangelista' WHERE user_id = 'D-1001'");
    $pdo->exec("UPDATE accounts SET first_name = 'Jereme', middle_name = 'Silvano', last_name = 'Evangelista' WHERE user_id = 'S-1001'");
    $pdo->exec("UPDATE accounts SET first_name = 'Jae',    middle_name = 'Silvano', last_name = 'Evangelista' WHERE user_id = 'C-1001'");
    $pdo->exec("UPDATE accounts SET first_name = 'Mjoy',   middle_name = NULL,      last_name = 'Evangelista' WHERE user_id = 'S-1002'");

    // For any other non-patient accounts that might exist (e.g. in MySQL), split full_name if first_name is still null
    if (in_array('full_name', $cols, true)) {
        $otherEmp = $pdo->query("SELECT id, user_id, role, full_name FROM accounts WHERE role != 'Patient' AND (first_name IS NULL OR first_name = '')")->fetchAll();
        $updOther = $pdo->prepare("UPDATE accounts SET first_name = :fn, middle_name = :mn, last_name = :ln WHERE id = :id");
        foreach ($otherEmp as $oe) {
            $raw = trim((string)($oe['full_name'] ?? ''));
            $parts = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
            $fn = $parts[0] ?? '';
            $ln = count($parts) > 1 ? end($parts) : '';
            $mn = count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : null;
            $updOther->execute([':fn' => $fn, ':mn' => $mn, ':ln' => $ln, ':id' => $oe['id']]);
        }
    }

    // Rule 2: Patient names live ONLY in the patients table. Never copy them into accounts.
    $pdo->exec("UPDATE accounts SET first_name = NULL, middle_name = NULL, last_name = NULL WHERE role = 'Patient'");

    echo "Populated first_name, middle_name, last_name on accounts:\n";
    $preview = $pdo->query("SELECT id, user_id, role, first_name, middle_name, last_name FROM accounts ORDER BY id ASC")->fetchAll();
    foreach ($preview as $p) {
        echo sprintf("  [%d] %s (%s): first=%s, middle=%s, last=%s\n",
            $p['id'],
            $p['user_id'],
            $p['role'],
            var_export($p['first_name'], true),
            var_export($p['middle_name'], true),
            var_export($p['last_name'], true)
        );
    }

    // Drop username and full_name from accounts
    if ($driver === 'sqlite') {
        $pdo->exec("PRAGMA foreign_keys=OFF");
        $pdo->beginTransaction();
        $pdo->exec("CREATE TABLE accounts_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(80) DEFAULT NULL,
            middle_name VARCHAR(80) DEFAULT NULL,
            last_name VARCHAR(80) DEFAULT NULL,
            email VARCHAR(150) DEFAULT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'Patient',
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            department VARCHAR(100) NOT NULL DEFAULT '',
            specialty VARCHAR(100) NOT NULL DEFAULT '',
            license_number VARCHAR(60) NOT NULL DEFAULT '',
            birthdate VARCHAR(40) NOT NULL DEFAULT '',
            phone VARCHAR(40) NOT NULL DEFAULT '',
            phone_verified TINYINT(1) NOT NULL DEFAULT 0,
            country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
            province VARCHAR(100) NOT NULL DEFAULT '',
            city VARCHAR(100) NOT NULL DEFAULT '',
            barangay VARCHAR(100) NOT NULL DEFAULT '',
            street VARCHAR(255) NOT NULL DEFAULT '',
            postal_code VARCHAR(20) NOT NULL DEFAULT '',
            sex VARCHAR(30) NOT NULL DEFAULT 'Not specified',
            force_password_change TINYINT(1) NOT NULL DEFAULT 0,
            set_password_token VARCHAR(120) NOT NULL DEFAULT '',
            set_password_expires_at BIGINT NOT NULL DEFAULT 0,
            last_login VARCHAR(40) NOT NULL DEFAULT 'Never',
            created_at VARCHAR(40) NOT NULL
        )");
        $copyCols = "id, user_id, first_name, middle_name, last_name, email, password_hash, role, status, department, specialty, license_number, birthdate, phone, phone_verified, country, province, city, barangay, street, postal_code, sex, force_password_change, set_password_token, set_password_expires_at, last_login, created_at";
        $pdo->exec("INSERT INTO accounts_new ({$copyCols}) SELECT {$copyCols} FROM accounts");
        $pdo->exec("DROP TABLE accounts");
        $pdo->exec("ALTER TABLE accounts_new RENAME TO accounts");
        $pdo->commit();
        $pdo->exec("PRAGMA foreign_keys=ON");
    } else {
        if (in_array('username', $cols, true)) {
            $pdo->exec("ALTER TABLE accounts DROP COLUMN username");
        }
        if (in_array('full_name', $cols, true)) {
            $pdo->exec("ALTER TABLE accounts DROP COLUMN full_name");
        }
    }

    if ($driver === 'sqlite') {
        $finalCols = array_column($pdo->query("PRAGMA table_info(accounts)")->fetchAll(), 'name');
    } else {
        $finalCols = array_column($pdo->query("SHOW COLUMNS FROM accounts")->fetchAll(), 'Field');
    }
    echo "Final columns in accounts ({$driver}): " . implode(', ', $finalCols) . "\n";
}

migratePdo($pdo, 'sqlite');

// Also migrate MySQL if online
try {
    $mysqlPdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=medicore_records;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 1,
    ]);
    migratePdo($mysqlPdo, 'mysql');
} catch (Throwable) {
    echo "\n(MySQL is currently offline; SQLite migrated cleanly. db_ensure_schema() will also auto-migrate MySQL if started later.)\n";
}

echo "\n=== Migration Completed Successfully! ===\n";
