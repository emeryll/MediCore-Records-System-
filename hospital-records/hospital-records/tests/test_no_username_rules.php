<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/db.php';

echo "=== Verifying No-Username & Atomic Names Rules ===\n";

$pdo = db();

// 1. Verify accounts columns
$cols = array_column($pdo->query("PRAGMA table_info(accounts)")->fetchAll(PDO::FETCH_ASSOC), 'name');
echo "[1] accounts columns: " . implode(', ', $cols) . "\n";
assert(!in_array('username', $cols, true), 'username column must NOT exist on accounts');
assert(!in_array('full_name', $cols, true), 'full_name column must NOT exist on accounts');
assert(in_array('first_name', $cols, true), 'first_name column must exist on accounts');
assert(in_array('middle_name', $cols, true), 'middle_name column must exist on accounts');
assert(in_array('last_name', $cols, true), 'last_name column must exist on accounts');

// 2. Verify Patient accounts have NULL names in accounts and derive display name from patients
$ptAccs = $pdo->query("SELECT * FROM accounts WHERE role = 'Patient'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($ptAccs as $pa) {
    assert($pa['first_name'] === null && $pa['middle_name'] === null && $pa['last_name'] === null, "Patient account {$pa['user_id']} must not store names in accounts");
    $disp = db_format_account_name($pa, $pdo);
    echo "    Patient account {$pa['user_id']} -> linked patient display name: {$disp}\n";
    assert($disp !== '', "Patient account {$pa['user_id']} must resolve name from patients table");
}

// 3. Verify Employee accounts have split names and format properly
$empExpected = [
    'A-0001' => ['Medea', null, 'Solon', 'Medea Solon'],
    'D-1001' => ['Meryll', 'Silvano', 'Evangelista', 'Meryll Silvano Evangelista'],
    'S-1001' => ['Jereme', 'Silvano', 'Evangelista', 'Jereme Silvano Evangelista'],
    'C-1001' => ['Jae', 'Silvano', 'Evangelista', 'Jae Silvano Evangelista'],
    'S-1002' => ['Mjoy', null, 'Evangelista', 'Mjoy Evangelista'],
];
foreach ($empExpected as $uid => [$fn, $mn, $ln, $full]) {
    $acc = db_find_account_by_id($uid);
    assert($acc !== null, "Employee {$uid} must exist");
    assert($acc['first_name'] === $fn, "{$uid} first_name mismatch");
    assert($acc['middle_name'] === $mn, "{$uid} middle_name mismatch");
    assert($acc['last_name'] === $ln, "{$uid} last_name mismatch");
    assert($acc['full_name'] === $full, "{$uid} computed full_name mismatch: got {$acc['full_name']}");
    echo "    Employee {$uid}: first='{$fn}', middle=" . ($mn ?? 'NULL') . ", last='{$ln}' => '{$acc['full_name']}'\n";
}

// 4. Verify Login Rule: Only Email or Phone + password works; old usernames or user_ids are rejected
$resEmail = db_verify_login('', 'solonmedea582@gmail.com', 'admin123');
assert(!empty($resEmail['ok']), 'Login by email must succeed');

$resPhone = db_verify_login('', '09171234567', 'admin123');
assert(!empty($resPhone['ok']), 'Login by phone must succeed');

$resOldUsername = db_verify_login('', 'admin', 'admin123');
assert(isset($resOldUsername['error']), 'Login by old username "admin" must be rejected');

$resUserId = db_verify_login('', 'A-0001', 'admin123');
assert(isset($resUserId['error']), 'Login by user_id "A-0001" must be rejected');
echo "[4] Login verification passed (email=OK, phone=OK, username=REJECTED, user_id=REJECTED)\n";

// 5. Verify db_load_state() succeeds and populates users & doctors
$state = db_load_state();
$_SESSION['state'] = $state;
$docs  = doctors_data();
assert(!empty($state['users']), 'state[users] must not be empty');
assert(!empty($docs), 'doctors_data() must not be empty');
echo "[5] db_load_state() loaded " . count($state['users']) . " users and " . count($docs) . " doctors.\n";

echo "=== ALL NO-USERNAME & NAME SPLIT VERIFICATION CHECKS PASSED! ===\n";
