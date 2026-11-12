<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/data.php';
require_once __DIR__ . '/../../includes/db.php';

function execute_address_migration(): void
{
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Running address migration on driver: $driver\n";

    // 1. Create address_history table
    $autoInc = ($driver === 'sqlite') ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $pdo->exec("CREATE TABLE IF NOT EXISTS address_history (
        id {$autoInc},
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
    )");
    echo "Table address_history created or already exists.\n";

    // 2. Add patient_address to bills
    try {
        $pdo->exec("ALTER TABLE bills ADD COLUMN patient_address VARCHAR(255) DEFAULT NULL");
        echo "Column patient_address added to bills.\n";
    } catch (Throwable $e) {
        echo "Column patient_address already exists on bills or failed: " . $e->getMessage() . "\n";
    }

    // 3. Backfill patient_address snapshot on existing bills
    $pts = $pdo->query("SELECT full_name, address FROM patients WHERE address IS NOT NULL AND address != ''")->fetchAll(PDO::FETCH_ASSOC);
    $ptAddressMap = [];
    foreach ($pts as $p) {
        $ptAddressMap[strtoupper(trim((string)$p['full_name']))] = (string)$p['address'];
    }

    $bills = $pdo->query("SELECT id, patient_name, patient_address FROM bills")->fetchAll(PDO::FETCH_ASSOC);
    $upd = $pdo->prepare("UPDATE bills SET patient_address = :addr WHERE id = :id");
    $migrated = 0;
    foreach ($bills as $b) {
        if (empty($b['patient_address'])) {
            $nameKey = strtoupper(trim((string)$b['patient_name']));
            if (isset($ptAddressMap[$nameKey])) {
                $upd->execute([':addr' => $ptAddressMap[$nameKey], ':id' => $b['id']]);
                $migrated++;
            }
        }
    }
    echo "Updated $migrated bills with snapshot patient_address.\n";
    echo "Address migration completed successfully.\n";
}

if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    execute_address_migration();
}
