<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/db.php';

echo "=== 3NF End-to-End Verification Test ===\n";

// 1. Test db_load_state()
echo "[1] Testing db_load_state()...\n";
$state = db_load_state();
assert(!empty($state['users']), 'Users state should not be empty');
assert(!empty($state['patients']), 'Patients state should not be empty');
assert(!empty($state['roles']), 'Roles state should not be empty');
assert(!empty($state['crud']['departments']), 'Departments state should not be empty');
assert(!empty($state['crud']['rooms']), 'Rooms state should not be empty');
assert(!empty($state['appointments']), 'Appointments state should not be empty');
assert(!empty($state['admissions']), 'Admissions state should not be empty');
assert(!empty($state['bills']), 'Bills state should not be empty');
assert(!empty($state['payments']), 'Payments state should not be empty');

echo "    Users count: " . count($state['users']) . "\n";
echo "    Patients count: " . count($state['patients']) . "\n";
echo "    Departments count: " . count($state['crud']['departments']) . "\n";
echo "    Rooms count: " . count($state['crud']['rooms']) . "\n";
echo "    Bills count: " . count($state['bills']) . "\n";
echo "    Payments count: " . count($state['payments']) . "\n";

// Verify a user address format
$firstUser = $state['users'][0];
echo "    Sample User: {$firstUser[0]} ({$firstUser[2]}) - Address: {$firstUser[8]}\n";
assert(!empty($firstUser[8]), 'User address should be computed and non-empty');

// Verify a patient address & full_name
$firstPatient = $state['patients'][0];
echo "    Sample Patient: {$firstPatient[0]} ({$firstPatient[8]}) - Address: {$firstPatient[7]}\n";
assert(!empty($firstPatient[0]), 'Patient full name should be computed and non-empty');
assert(!empty($firstPatient[7]), 'Patient address should be computed and non-empty');

// 2. Test db_save_state()
echo "\n[2] Testing db_save_state()...\n";
db_save_state($state);
echo "    db_save_state() completed successfully without exception.\n";

// 3. Test reloading after save
echo "\n[3] Testing reload after save...\n";
$reloadedState = db_load_state();
assert(count($reloadedState['users']) === count($state['users']), 'User count mismatch after save');
assert(count($reloadedState['patients']) === count($state['patients']), 'Patient count mismatch after save');
assert(count($reloadedState['bills']) === count($state['bills']), 'Bills count mismatch after save');
assert(count($reloadedState['payments']) === count($state['payments']), 'Payments count mismatch after save');
echo "    Reloaded state counts verified.\n";

// 4. Test db_find_patient_by_id()
echo "\n[4] Testing db_find_patient_by_id()...\n";
$p = db_find_patient_by_id($firstPatient[8]);
assert($p !== null, 'Patient lookup by ID failed');
echo "    Found patient: {$p['full_name']} (MRN: {$p['mrn']})\n";
assert(!empty($p['full_name']), 'Patient full_name must be present');
assert(isset($p['address']), 'Patient address must be present');

// 5. Test db_find_account_by_id()
echo "\n[5] Testing db_find_account_by_id()...\n";
$acc = db_find_account_by_id($firstUser[7]);
assert($acc !== null, 'Account lookup by ID failed');
echo "    Found account: {$acc['full_name']} ({$acc['role']})\n";
assert(isset($acc['address']), 'Account address must be present');
assert(isset($acc['user_role_label']), 'Account user_role_label must be present');

// 6. Test linked patients
echo "\n[6] Testing patient_account_links...\n";
$patientAcc = null;
foreach ($state['users'] as $u) {
    if (($u[2] ?? '') === 'Patient') {
        $patientAcc = $u;
        break;
    }
}
if ($patientAcc) {
    $linked = db_get_account_linked_patients($patientAcc[7]);
    echo "    Linked patients for {$patientAcc[0]} ({$patientAcc[7]}): " . count($linked) . "\n";
    foreach ($linked as $l) {
        echo "      -> Linked: {$l['patient_name']} (MRN: {$l['mrn']}, rel: {$l['relationship_label']})\n";
        assert(!empty($l['patient_name']), 'Linked patient must have non-empty computed patient_name');
    }
} else {
    echo "    No patient account found in sample to test linking.\n";
}

// 7. Verify Historical Preservations (bills.patient_address, address_history, audit_logs)
echo "\n[7] Verifying Historical Exception tables...\n";
$pdo = db();
$billsAddrCount = (int)$pdo->query("SELECT count(*) FROM bills WHERE patient_address IS NOT NULL AND patient_address != ''")->fetchColumn();
echo "    Bills with historical address snapshot: {$billsAddrCount}\n";
assert($billsAddrCount > 0, 'Bills must retain their historical address snapshots');

$histCount = (int)$pdo->query("SELECT count(*) FROM address_history")->fetchColumn();
echo "    Address history rows: {$histCount}\n";

$auditCount = (int)$pdo->query("SELECT count(*) FROM audit_logs")->fetchColumn();
echo "    Audit log rows: {$auditCount}\n";

echo "\n=== ALL 3NF VERIFICATION TESTS PASSED SUCCESSFULLY! ===\n";
