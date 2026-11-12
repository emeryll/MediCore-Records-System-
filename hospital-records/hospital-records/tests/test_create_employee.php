<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/components.php';
require_once __DIR__ . '/../includes/mailer.php';

echo "=== Test Employee Account Creation & Password Setup ===\n";

$cfg = get_mail_config();
echo "Mail Config: host={$cfg['smtp_host']}, port={$cfg['smtp_port']}, user={$cfg['smtp_user']}, has_pass=" . (!empty($cfg['smtp_pass']) ? 'YES' : 'NO') . "\n";

$pdo = db();
$testEmail = 'test_employee_' . time() . '@example.com';
$newUserId = db_next_user_id('S');
$tempPw = bin2hex(random_bytes(16));

echo "[1] Creating Staff account ({$newUserId} / {$testEmail})...\n";
$created = db_create_account([
    'user_id'        => $newUserId,
    'username'       => 'Test Employee Staff',
    'email'          => $testEmail,
    'password'       => $tempPw,
    'full_name'      => 'Test Employee Staff',
    'role'           => 'Staff',
    'status'         => 'Pending',
    'department'     => '',
    'specialty'      => '',
    'license'        => '',
    'birthdate'      => '1990-01-01',
    'phone'          => '09170001122',
    'phone_verified' => 0,
    'sex'            => 'Female',
    'country'        => 'Philippines',
    'province'       => 'Zamboanga del Sur',
    'city'           => 'Zamboanga City',
    'barangay'       => 'Tetuan',
    'street'         => '123 Test St',
    'postal_code'    => '7000',
], true);

if (!empty($created['error'])) {
    echo "FAILED to create account: {$created['error']}\n";
    exit(1);
}

$finalId = $created['account']['user_id'];
db_add_user_role($finalId, 'Staff');
$token = bin2hex(random_bytes(24));
$expiresAt = time() + (24 * 3600);
$pdo->prepare('UPDATE accounts SET status = "Pending", force_password_change = 1, set_password_token = :tk, set_password_expires_at = :exp WHERE UPPER(user_id) = :uid')
    ->execute([':tk' => $token, ':exp' => $expiresAt, ':uid' => strtoupper($finalId)]);

echo "    Account created successfully! ID: {$finalId}\n";

echo "[2] Verifying account is Pending and has token...\n";
$acc = db_find_account_by_id($finalId);
assert($acc !== null, 'Account must be found');
assert($acc['status'] === 'Pending', 'Status must be Pending');
assert($acc['set_password_token'] === $token, 'Token must match');

echo "[3] Verifying Set Password screen renders even when Admin session is active...\n";
$_SESSION['role'] = 'Admin';
$_SESSION['user_id'] = 'A-0001';
$_GET = ['auth' => 'login', 'set_password' => '1', 'uid' => $finalId, 'token' => $token];
ob_start();
require __DIR__ . '/../pages/login.php';
$setPwHtml = ob_get_clean();
assert(str_contains($setPwHtml, 'Set your personal password'), 'Must render Set your personal password form');
assert(str_contains($setPwHtml, 'Save password &amp; activate account'), 'Must render Save password & activate account button');
assert(str_contains($setPwHtml, $testEmail), 'Must show employee email');
echo "    Set Password screen rendered properly!\n";

echo "[4] Simulating user clicking email link and setting password...\n";
$newPersonalPw = 'MyNewSecurePass123!';
$hash = password_hash($newPersonalPw, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE accounts SET password_hash = :pw, status = "Active", force_password_change = 0, set_password_token = "", set_password_expires_at = 0 WHERE id = :id')
    ->execute([':pw' => $hash, ':id' => $acc['id']]);

echo "[4] Testing login with new credentials...\n";
$verify = db_verify_login('', $testEmail, $newPersonalPw);
assert(!empty($verify['ok']), 'Login should succeed with new password');
assert($verify['account']['status'] === 'Active', 'Account should now be Active');
assert($verify['account']['user_id'] === $finalId, 'User ID should match');
$portals = db_get_available_portals($finalId);
echo "    Login succeeded! Portals: " . json_encode($portals) . "\n";
assert(in_array('Staff', $portals, true), 'Staff portal must be available');

echo "[5] Cleaning up test account...\n";
$pdo->prepare('DELETE FROM user_roles WHERE UPPER(user_id) = :uid')->execute([':uid' => strtoupper($finalId)]);
$pdo->prepare('DELETE FROM accounts WHERE id = :id')->execute([':id' => $acc['id']]);

echo "=== ALL EMPLOYEE CREATION & ACTIVATION TESTS PASSED! ===\n";
