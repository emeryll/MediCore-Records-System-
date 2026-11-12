<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/db.php';

$pdo = db();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
echo "Executing 3NF migration on driver: {$driver}\n";

$sqlFile = __DIR__ . '/migrate_to_3nf.sql';
if (!file_exists($sqlFile)) {
    die("Error: migrate_to_3nf.sql not found.\n");
}

$rawSql = file_get_contents($sqlFile);

// Parse statements (split by semicolon, ignoring comments)
$lines = explode("\n", $rawSql);
$currentStmt = '';
$statements = [];

foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--')) {
        continue;
    }
    $currentStmt .= ' ' . $trimmed;
    if (str_ends_with($trimmed, ';')) {
        $statements[] = trim($currentStmt);
        $currentStmt = '';
    }
}

echo "Found " . count($statements) . " statements to execute.\n\n";

$successCount = 0;
foreach ($statements as $idx => $stmt) {
    $clean = rtrim($stmt, ';');
    try {
        $pdo->exec($clean);
        $successCount++;
        echo "[" . ($idx + 1) . "/" . count($statements) . "] OK: " . substr($clean, 0, 70) . "...\n";
    } catch (Throwable $e) {
        echo "[" . ($idx + 1) . "/" . count($statements) . "] NOTICE/ERROR on: " . substr($clean, 0, 70) . "...\n";
        echo "   -> Reason: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration execution completed! Successful statements: {$successCount}/" . count($statements) . "\n";
