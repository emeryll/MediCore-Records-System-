<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/icons.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/booking.php';
require __DIR__ . '/includes/mailer.php';
require __DIR__ . '/includes/archive_helper.php';

/* ---------------------------------------------------------------
 * 0. Direct file downloads (e.g. Archive CSV downloads)
 * ------------------------------------------------------------- */
if (($_GET['action'] ?? '') === 'download_csv' && in_array(($_SESSION['role'] ?? ''), ['Admin'], true)) {
    $category = (string) ($_GET['category'] ?? 'Patients');
    $adminId  = (string) ($_GET['admin_id'] ?? 'A-0001');
    $year     = (string) ($_GET['year'] ?? date('Y'));
    $q        = trim((string) ($_GET['q'] ?? ''));
    $from     = trim((string) ($_GET['from'] ?? ''));
    $to       = trim((string) ($_GET['to'] ?? ''));

    $st  = &state();
    $csv = archive_generate_csv($category, $st, $q ?: null, $from ?: null, $to ?: null);

    $safeCat  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $category);
    $filename = "{$safeCat}_{$year}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit;
}

/* ---------------------------------------------------------------
 * 1. POST actions (login, logout, state changes) -> redirect (PRG)
 * ------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    // AJAX Endpoints (Phone OTP & Patient Lookup)
    if ($action === 'ajax_send_phone_otp') {
        header('Content-Type: application/json; charset=utf-8');
        $phone     = trim((string) ($_POST['phone'] ?? ''));
        $patientId = trim((string) ($_POST['patient_id'] ?? ''));
        $purpose   = trim((string) ($_POST['purpose'] ?? 'verification'));
        $res       = db_generate_phone_otp($phone, $patientId, $purpose);
        if (!empty($res['ok'])) {
            $res['simulated_message'] = '💬 Simulated SMS to ' . $res['phone'] . ': Your West Metro Medical Center verification code is ' . $res['code'] . ' (valid for 10 minutes).';
        }
        echo json_encode($res);
        exit;
    }

    if ($action === 'ajax_verify_phone_otp') {
        header('Content-Type: application/json; charset=utf-8');
        $phone   = trim((string) ($_POST['phone'] ?? ''));
        $code    = trim((string) ($_POST['code'] ?? ''));
        $purpose = trim((string) ($_POST['purpose'] ?? 'verification'));
        $res     = db_verify_phone_otp($phone, $code, $purpose);
        echo json_encode($res);
        exit;
    }

    $exemptCsrf = ['login', 'register_account', 'forgot', 'reset_password', 'activate_step1', 'activate_step2', 'activate_step3', 'complete_set_password'];
    if (!csrf_valid() && !in_array($action, $exemptCsrf, true)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back and try again.');
    }

    $return = safe_return($_POST['return'] ?? null);
    $st     = &state();
    $role   = $_SESSION['role'] ?? 'Admin';

    $logAudit = static function (string $act, string $table, string $recId) use (&$st, $role): void {
        $actorRole = ['Admin' => 'Administrator', 'Staff' => 'Staff', 'Cashier' => 'Cashier', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$role] ?? 'Staff';
        array_unshift($st['audit'], [
            current_user_name($role),
            $act,
            $table,
            $recId,
            date('M d, Y · H:i'),
            $actorRole,
        ]);
    };

    switch ($action) {
        /* -------------------------------------------------------
         * PATIENT 3-STEP ACCOUNT ACTIVATION (Section 3.2)
         * ----------------------------------------------------- */
        case 'activate_step1':
            $phone      = trim((string) ($_POST['phone'] ?? ''));
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) < 10) {
                $_SESSION['activate_error'] = 'Please enter a valid 11-digit mobile number (09XXXXXXXXX).';
                redirect('index.php?auth=activate&step=1');
            }

            // Search for patient by phone in $st['patients'] and DB
            $matchedPt = null;
            foreach ($st['patients'] as $pt) {
                $ptPh = preg_replace('/[^0-9]/', '', (string) ($pt[2] ?? ''));
                if ($ptPh !== '' && ($ptPh === $cleanPhone || substr($ptPh, -10) === substr($cleanPhone, -10))) {
                    $matchedPt = $pt;
                    break;
                }
            }

            if (!$matchedPt) {
                $_SESSION['activate_error'] = 'No hospital record found for ' . $cleanPhone . '. Please register with Front Desk Staff at West Metro Medical Center first.';
                redirect('index.php?auth=activate&step=1');
            }

            if (is_provisional_patient($matchedPt)) {
                $_SESSION['activate_error'] = 'Your record (' . $matchedPt[0] . ') is currently Provisional (phone-in booking). Please complete full registration in person at the Front Desk upon arrival to receive your MRN before activating your online portal account.';
                redirect('index.php?auth=activate&step=1');
            }

            $mrn  = patient_mrn_display($matchedPt) ?: (string) ($matchedPt[8] ?? 'MRN-1001');
            $ptId = (string) ($matchedPt[8] ?? $mrn);

            // Check if already activated
            $existingAcc = db_find_account_by_phone($cleanPhone);
            if ($existingAcc && ($existingAcc['role'] ?? '') === 'Patient') {
                $_SESSION['activate_error'] = 'An online portal account is already active for ' . $matchedPt[0] . ' (' . $mrn . '). Please log in with your mobile number and password.';
                redirect('index.php?auth=activate&step=1');
            }

            $otpRes = db_generate_phone_otp($cleanPhone, $ptId, 'account_creation');
            $code   = $otpRes['code'] ?? '123456';

            $_SESSION['activate_phone'] = $cleanPhone;
            $_SESSION['activate_otp']   = $code;
            $_SESSION['activate_pt']    = [
                'patient_id' => $ptId,
                'mrn'        => $mrn,
                'full_name'  => (string) $matchedPt[0],
                'birthdate'  => (string) ($matchedPt[1] ?? ''),
                'sex'        => (string) ($matchedPt[5] ?? 'Not specified'),
                'address'    => (string) ($matchedPt[7] ?? ''),
                'email'      => (string) ($matchedPt['email'] ?? ''),
            ];
            push_sim_message('sms', $cleanPhone, 'West Metro Medical Center Account Activation', 'Your 6-digit OTP code to activate your Patient Portal account (' . $mrn . ') is ' . $code . '.', ['Code' => $code]);
            redirect('index.php?auth=activate&step=2');

        case 'activate_step2':
            $code  = trim((string) ($_POST['otp_code'] ?? ''));
            $phone = (string) ($_SESSION['activate_phone'] ?? '');
            if ($phone === '' || empty($_SESSION['activate_pt'])) {
                redirect('index.php?auth=activate&step=1');
            }
            $vRes = db_verify_phone_otp($phone, $code, 'account_creation');
            if (empty($vRes['ok']) && $code !== (string) ($_SESSION['activate_otp'] ?? '')) {
                $_SESSION['activate_error'] = $vRes['error'] ?? 'Invalid or expired 6-digit OTP code.';
                redirect('index.php?auth=activate&step=2');
            }
            $_SESSION['activate_verified'] = true;
            redirect('index.php?auth=activate&step=3');

        case 'activate_step3':
            if (empty($_SESSION['activate_verified']) || empty($_SESSION['activate_pt'])) {
                redirect('index.php?auth=activate&step=1');
            }
            $ptInfo   = $_SESSION['activate_pt'];
            $phone    = (string) ($_SESSION['activate_phone'] ?? '');
            $email    = trim((string) ($_POST['email'] ?? ($ptInfo['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $confirm  = (string) ($_POST['confirm_password'] ?? '');

            if ($password !== $confirm) {
                $_SESSION['activate_error'] = 'Passwords do not match.';
                redirect('index.php?auth=activate&step=3');
            }
            if (strlen($password) < 8) {
                $_SESSION['activate_error'] = 'Password must be at least 8 characters long.';
                redirect('index.php?auth=activate&step=3');
            }

            $userId   = (string) ($ptInfo['patient_id'] ?: $ptInfo['mrn']);
            $fullName = (string) $ptInfo['full_name'];
            $finalEm  = $email !== '' ? $email : (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userId)) . '@patient.westmetro.local');

            $res = db_create_account([
                'user_id'        => $userId,
                'username'       => $fullName,
                'email'          => $finalEm,
                'password'       => $password,
                'full_name'      => $fullName,
                'role'           => 'Patient',
                'birthdate'      => (string) ($ptInfo['birthdate'] ?? ''),
                'phone'          => $phone,
                'phone_verified' => 1,
                'sex'            => (string) ($ptInfo['sex'] ?? 'Not specified'),
                'address'        => (string) ($ptInfo['address'] ?? ''),
            ], true);

            if (isset($res['error'])) {
                $_SESSION['activate_error'] = $res['error'];
                redirect('index.php?auth=activate&step=3');
            }

            unset($_SESSION['activate_phone'], $_SESSION['activate_otp'], $_SESSION['activate_pt'], $_SESSION['activate_verified']);
            $st = db_load_state();
            $_SESSION['state']     = &$st;
            $_SESSION['role']      = 'Patient';
            $_SESSION['user_id']   = $userId;
            $_SESSION['user_name'] = $fullName;
            $logAudit('Activated account', 'accounts', '#' . $userId);
            flash('Welcome to West Metro Medical Center, ' . $fullName . '! Your Patient Portal account (' . ($ptInfo['mrn'] ?? $userId) . ') is now active.');
            redirect('index.php');

        /* -------------------------------------------------------
         * SET PASSWORD LINK / FIRST-LOGIN PASSWORD SETUP (Section 3.3)
         * ----------------------------------------------------- */
        case 'complete_set_password':
            $uid      = trim((string) ($_POST['uid'] ?? ($_SESSION['force_pw_change_uid'] ?? '')));
            $token    = trim((string) ($_POST['token'] ?? ($_GET['token'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $confirm  = (string) ($_POST['confirm_password'] ?? '');

            if ($uid === '') {
                $_SESSION['set_pw_error'] = 'Please enter your Account ID or Email.';
                redirect('index.php?auth=login&set_password=1');
            }
            if ($password !== $confirm) {
                $_SESSION['set_pw_error'] = 'Passwords do not match.';
                redirect('index.php?auth=login&set_password=1&uid=' . urlencode($uid) . ($token !== '' ? '&token=' . urlencode($token) : ''));
            }
            if (strlen($password) < 8) {
                $_SESSION['set_pw_error'] = 'Password must be at least 8 characters long.';
                redirect('index.php?auth=login&set_password=1&uid=' . urlencode($uid) . ($token !== '' ? '&token=' . urlencode($token) : ''));
            }

            $acc = db_find_account_by_id($uid);
            if (!$acc) {
                $stmt = db()->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) LIMIT 1');
                $stmt->execute([':em' => $uid]);
                $acc = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            if (!$acc) {
                $_SESSION['set_pw_error'] = 'Account not found for "' . $uid . '".';
                redirect('index.php?auth=login&set_password=1');
            }

            // Verify single-use expiring token if account has one
            $savedToken = (string) ($acc['set_password_token'] ?? '');
            $expiresAt  = (int) ($acc['set_password_expires_at'] ?? 0);
            if ($savedToken !== '') {
                if ($token !== '' && !hash_equals($savedToken, $token)) {
                    $_SESSION['set_pw_error'] = 'Invalid activation link token.';
                    redirect('index.php?auth=login&set_password=1&uid=' . urlencode($uid));
                }
                if ($expiresAt > 0 && time() > $expiresAt) {
                    $_SESSION['set_pw_error'] = 'This activation link has expired. Please ask the administrator to resend your activation email.';
                    redirect('index.php?auth=login&set_password=1&uid=' . urlencode($uid));
                }
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $up   = db()->prepare('UPDATE accounts SET password_hash = :pw, status = "Active", force_password_change = 0, set_password_token = "", set_password_expires_at = 0 WHERE id = :id');
            $up->execute([':pw' => $hash, ':id' => $acc['id']]);

            unset($_SESSION['force_pw_change_uid'], $_SESSION['set_pw_error']);
            $_SESSION['role']      = (string) $acc['role'];
            $_SESSION['user_id']   = (string) $acc['user_id'];
            $_SESSION['user_name'] = $acc['role'] === 'Doctor' && !str_starts_with((string) $acc['full_name'], 'Dr.')
                ? 'Dr. ' . $acc['full_name']
                : (string) $acc['full_name'];
            $logAudit('Activated account and set personal password', 'accounts', '#' . $acc['user_id']);
            flash('Account activated! Personal password saved. Welcome to your West Metro Medical Center portal.');
            redirect('index.php');

        /* -------------------------------------------------------
         * UNIFIED LOGIN (Phone or Email + Password)
         * ----------------------------------------------------- */
        case 'login':
            $userId     = strtoupper(trim((string) ($_POST['user_id'] ?? '')));
            $identifier = trim((string) ($_POST['login_identifier'] ?? ($_POST['phone'] ?? ($_POST['email'] ?? ($_POST['username'] ?? '')))));
            $password   = (string) ($_POST['password'] ?? '');

            if (str_contains($identifier, 'Warning') || str_contains($identifier, '<br') || str_contains($identifier, 'Undefined')) {
                $identifier = '';
            }

            $_SESSION['login_identifier'] = $identifier;

            $verify = db_verify_login($userId, $identifier, $password);
            if (isset($verify['error'])) {
                $_SESSION['login_error'] = $verify['error'];
                redirect('index.php?auth=login');
            }

            $acc = $verify['account'];
            unset($_SESSION['login_user_id'], $_SESSION['login_identifier'], $_SESSION['login_phone'], $_SESSION['login_error']);

            // Check if account is still Pending activation
            if (($acc['status'] ?? '') === 'Pending') {
                $_SESSION['login_error'] = 'pending';
                redirect('index.php?auth=login');
            }

            // Check if account requires first-login password change
            if (!empty($acc['force_password_change'])) {
                $_SESSION['force_pw_change_uid'] = (string) $acc['user_id'];
                redirect('index.php?auth=login&set_password=1&uid=' . urlencode((string) $acc['user_id']));
            }

            $_SESSION['role']      = (string) $acc['role'];
            $_SESSION['user_id']   = (string) $acc['user_id'];
            $_SESSION['user_name'] = $acc['role'] === 'Doctor' && !str_starts_with((string) $acc['full_name'], 'Dr.')
                ? 'Dr. ' . $acc['full_name']
                : (string) $acc['full_name'];

            $logAudit('Sign in', 'accounts', '#' . $acc['user_id']);
            redirect('index.php');

        case 'forgot':
            $resetMethod = (string) ($_POST['reset_method'] ?? 'phone');
            $phone       = trim((string) ($_POST['phone'] ?? ''));
            $email       = strtolower(trim((string) ($_POST['email'] ?? '')));
            $cleanPhone  = preg_replace('/[^0-9]/', '', $phone);

            $_SESSION['reset_method'] = $resetMethod;
            $_SESSION['reset_phone']  = $phone;
            $_SESSION['reset_email']  = $email;

            if ($resetMethod === 'email') {
                if ($email === '') {
                    $_SESSION['reset_error'] = 'Please enter your registered email address.';
                    redirect('index.php?auth=login&forgot=1');
                }
                $stmt = db()->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) LIMIT 1');
                $stmt->execute([':em' => $email]);
                $acc = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$acc) {
                    $_SESSION['reset_error'] = 'No account found with email address ' . $email . '.';
                    redirect('index.php?auth=login&forgot=1');
                }
                $otpRes = db_generate_phone_otp($email, (string) $acc['user_id'], 'password_reset');
                $code   = $otpRes['code'] ?? '123456';
                $_SESSION['reset_info']           = 'A 6-digit verification code has been sent to ' . $email . '.';
                $_SESSION['reset_sms_sim']        = '✉️ Simulated Email to ' . $email . ': Your West Metro Medical Center password reset code is ' . $code . '.';
                $_SESSION['reset_otp_code']       = $code;
                $_SESSION['reset_verified_phone'] = $email;
                $_SESSION['reset_target_uid']     = (string) $acc['user_id'];
            } else {
                if ($cleanPhone === '') {
                    $_SESSION['reset_error'] = 'Please enter your registered mobile number.';
                    redirect('index.php?auth=login&forgot=1');
                }
                $acc = db_find_account_by_phone($cleanPhone);
                if (!$acc) {
                    $_SESSION['reset_error'] = 'No account found with mobile number ' . $cleanPhone . '.';
                    redirect('index.php?auth=login&forgot=1');
                }
                $otpRes = db_generate_phone_otp($cleanPhone, (string) $acc['user_id'], 'password_reset');
                $code   = $otpRes['code'] ?? '123456';
                $_SESSION['reset_info']           = 'A 6-digit verification code has been sent to ' . $cleanPhone . '.';
                $_SESSION['reset_sms_sim']        = '💬 Simulated SMS to ' . $cleanPhone . ': Your West Metro Medical Center password reset code is ' . $code . '.';
                $_SESSION['reset_otp_code']       = $code;
                $_SESSION['reset_verified_phone'] = $cleanPhone;
                $_SESSION['reset_target_uid']     = (string) $acc['user_id'];
            }
            redirect('index.php?auth=login&reset_step=verify');

        case 'reset_password':
            $userId      = (string) ($_SESSION['reset_target_uid'] ?? '');
            $identifier  = (string) ($_SESSION['reset_verified_phone'] ?? '');
            $code        = trim((string) ($_POST['code'] ?? ''));
            $password    = (string) ($_POST['password'] ?? '');
            $confirmPass = (string) ($_POST['confirm_password'] ?? '');

            if ($password !== $confirmPass) {
                $_SESSION['reset_error'] = 'Passwords do not match.';
                redirect('index.php?auth=login&reset_step=verify');
            }
            if (strlen($password) < 8) {
                $_SESSION['reset_error'] = 'Password must be at least 8 characters long.';
                redirect('index.php?auth=login&reset_step=verify');
            }

            $vRes = db_verify_phone_otp($identifier, $code, 'password_reset');
            if (empty($vRes['ok']) && $code !== (string) ($_SESSION['reset_otp_code'] ?? '')) {
                $_SESSION['reset_error'] = 'Incorrect or expired verification code.';
                redirect('index.php?auth=login&reset_step=verify');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $up   = db()->prepare('UPDATE accounts SET password_hash = :pw WHERE UPPER(user_id) = :uid');
            $up->execute([':pw' => $hash, ':uid' => strtoupper($userId)]);

            unset($_SESSION['reset_target_uid'], $_SESSION['reset_verified_phone'], $_SESSION['reset_sms_sim'], $_SESSION['reset_info'], $_SESSION['reset_otp_code']);
            $_SESSION['login_success_msg'] = 'Password updated! Sign in with your mobile number/email and new password.';
            redirect('index.php?auth=login');

        case 'logout':
            unset($_SESSION['role'], $_SESSION['user_id'], $_SESSION['user_name']);
            header('Location: index.php');
            exit;

        case 'notify':
            flash((string) ($_POST['message'] ?? 'Done'));
            redirect($return);

        /* -------------------------------------------------------
         * PROFILE 2-STEP PHONE & EMAIL VERIFICATION (Section 4.7)
         * ----------------------------------------------------- */
        case 'request_profile_phone_otp':
            $newPhone   = preg_replace('/[^0-9]/', '', (string) ($_POST['new_phone'] ?? ''));
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if (strlen($newPhone) !== 11 || !str_starts_with($newPhone, '09')) {
                flash('Please enter a valid 11-digit mobile number starting with 09.');
                redirect('index.php?page=profile');
            }
            $otpRes = db_generate_phone_otp($newPhone, $currentUid, 'profile_phone_change');
            $code   = $otpRes['code'] ?? '123456';
            $_SESSION['pending_profile_phone']     = $newPhone;
            $_SESSION['pending_profile_phone_otp'] = $code;
            push_sim_message('sms', $newPhone, 'Confirm New Phone Number', 'Your 6-digit OTP to confirm updating your phone number is ' . $code . '.', ['Code' => $code]);
            flash('OTP sent to ' . $newPhone . '. Enter the 6-digit code to confirm your new mobile number.');
            redirect('index.php?page=profile');

        case 'confirm_profile_phone':
            $code       = trim((string) ($_POST['otp_code'] ?? ''));
            $newPhone   = (string) ($_SESSION['pending_profile_phone'] ?? '');
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if ($newPhone === '') {
                redirect('index.php?page=profile');
            }
            $vRes = db_verify_phone_otp($newPhone, $code, 'profile_phone_change');
            if (empty($vRes['ok']) && $code !== (string) ($_SESSION['pending_profile_phone_otp'] ?? '')) {
                flash('Invalid OTP code. Phone number was not changed.');
                redirect('index.php?page=profile');
            }
            $pdo = db();
            $pdo->prepare('UPDATE accounts SET phone = :ph, phone_verified = 1 WHERE UPPER(user_id) = :uid')->execute([':ph' => $newPhone, ':uid' => strtoupper($currentUid)]);
            $pdo->prepare('UPDATE patients SET phone = :ph, phone_verified = 1 WHERE UPPER(patient_id) = :uid')->execute([':ph' => $newPhone, ':uid' => strtoupper($currentUid)]);
            foreach ($st['patients'] as &$pt) {
                if (strcasecmp((string) ($pt[8] ?? ''), $currentUid) === 0 || strcasecmp($pt[0], current_user_name($role)) === 0) {
                    $pt[2] = $newPhone;
                }
            }
            unset($pt);
            unset($_SESSION['pending_profile_phone'], $_SESSION['pending_profile_phone_otp']);
            $logAudit('Verified new phone', 'accounts', '#' . $currentUid);
            flash('Mobile number verified and updated to ' . $newPhone . '!');
            redirect('index.php?page=profile');

        case 'request_profile_email_otp':
            $newEmail   = strtolower(trim((string) ($_POST['new_email'] ?? '')));
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                flash('Please enter a valid email address.');
                redirect('index.php?page=profile');
            }
            $otpRes = db_generate_phone_otp($newEmail, $currentUid, 'profile_email_change');
            $code   = $otpRes['code'] ?? '123456';
            $_SESSION['pending_profile_email']     = $newEmail;
            $_SESSION['pending_profile_email_otp'] = $code;
            push_sim_message('email', $newEmail, 'Confirm New Email Address', 'Your 6-digit verification code to confirm updating your email address is ' . $code . '.', ['Code' => $code]);
            flash('Verification code sent to ' . $newEmail . '. Enter the code to confirm your new email.');
            redirect('index.php?page=profile');

        case 'confirm_profile_email':
            $code       = trim((string) ($_POST['email_otp_code'] ?? ''));
            $newEmail   = (string) ($_SESSION['pending_profile_email'] ?? '');
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if ($newEmail === '') {
                redirect('index.php?page=profile');
            }
            $vRes = db_verify_phone_otp($newEmail, $code, 'profile_email_change');
            if (empty($vRes['ok']) && $code !== (string) ($_SESSION['pending_profile_email_otp'] ?? '')) {
                flash('Invalid verification code. Email address was not changed.');
                redirect('index.php?page=profile');
            }
            $pdo = db();
            $pdo->prepare('UPDATE accounts SET email = :em WHERE UPPER(user_id) = :uid')->execute([':em' => $newEmail, ':uid' => strtoupper($currentUid)]);
            $pdo->prepare('UPDATE patients SET email = :em WHERE UPPER(patient_id) = :uid')->execute([':em' => $newEmail, ':uid' => strtoupper($currentUid)]);
            unset($_SESSION['pending_profile_email'], $_SESSION['pending_profile_email_otp']);
            $logAudit('Verified new email', 'accounts', '#' . $currentUid);
            flash('Email address verified and updated to ' . $newEmail . '!');
            redirect('index.php?page=profile');

        case 'change_profile_password':
        case 'change_patient_password':
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            $currPw     = (string) ($_POST['current_password'] ?? '');
            $newPw      = (string) ($_POST['new_password'] ?? '');
            $confirmPw  = (string) ($_POST['confirm_password'] ?? '');

            if ($currentUid === '') {
                redirect('index.php?auth=login');
            }

            $stmt = db()->prepare('SELECT * FROM accounts WHERE UPPER(user_id) = :uid LIMIT 1');
            $stmt->execute([':uid' => strtoupper($currentUid)]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$acc) {
                flash('User account not found.');
                redirect('index.php?page=profile');
            }

            if (!password_verify($currPw, (string) ($acc['password_hash'] ?? ''))) {
                flash('Incorrect current password. Please try again.');
                redirect('index.php?page=profile');
            }

            if (strlen($newPw) < 8) {
                flash('New password must be at least 8 characters long.');
                redirect('index.php?page=profile');
            }

            if ($newPw !== $confirmPw) {
                flash('New password and confirmation password do not match.');
                redirect('index.php?page=profile');
            }

            $newHash = password_hash($newPw, PASSWORD_DEFAULT);
            $up = db()->prepare('UPDATE accounts SET password_hash = :pw, force_password_change = 0 WHERE id = :id');
            $up->execute([':pw' => $newHash, ':id' => $acc['id']]);

            $logAudit('Changed account password', 'accounts', '#' . $currentUid);
            flash('Your account password has been successfully updated!');
            redirect('index.php?page=profile');

        case 'update_my_profile':
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if ($currentUid === '') {
                redirect('index.php?auth=login');
            }
            $sex         = trim((string) ($_POST['sex'] ?? ''));
            $country     = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province    = trim((string) ($_POST['province'] ?? ''));
            $city        = trim((string) ($_POST['city'] ?? ''));
            $barangay    = trim((string) ($_POST['barangay'] ?? ''));
            $street      = trim((string) ($_POST['street'] ?? ''));
            $postal      = trim((string) ($_POST['postal_code'] ?? ''));
            $newPassword = (string) ($_POST['new_password'] ?? '');

            $addrParts     = array_filter([$street, $barangay, $city, $province, $country, $postal]);
            $formattedAddr = implode(', ', $addrParts) ?: '—';

            $pdo    = db();
            $sql    = 'UPDATE accounts SET sex = :sx, address = :ad, country = :co, province = :pr, city = :ci, barangay = :bg, street = :st, postal_code = :pc';
            $params = [
                ':sx'  => $sex !== '' ? $sex : 'Not specified',
                ':ad'  => $formattedAddr,
                ':co'  => $country,
                ':pr'  => $province,
                ':ci'  => $city,
                ':bg'  => $barangay,
                ':st'  => $street,
                ':pc'  => $postal,
                ':uid' => strtoupper($currentUid),
            ];
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    flash('New password must be at least 8 characters long.');
                    redirect('index.php?page=profile');
                }
                $sql .= ', password_hash = :pw';
                $params[':pw'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE UPPER(user_id) = :uid';
            $pdo->prepare($sql)->execute($params);

            foreach ($st['patients'] as &$pt) {
                if (strcasecmp((string) ($pt[8] ?? ''), $currentUid) === 0 || strcasecmp($pt[0], current_user_name($role)) === 0) {
                    $pt[5] = $sex !== '' ? $sex : $pt[5];
                    $pt[7] = $formattedAddr;
                }
            }
            unset($pt);
            $logAudit('Updated profile', 'accounts', '#' . $currentUid);
            flash('Profile details saved successfully.');
            redirect('index.php?page=profile');

        /* -------------------------------------------------------
         * PROVISIONAL vs REGISTERED PATIENT FLOWS (Sections 2 & 5.2)
         * ----------------------------------------------------- */
        case 'quick_add_provisional':
            $first  = trim((string) ($_POST['first_name'] ?? ''));
            $middle = trim((string) ($_POST['middle_name'] ?? ''));
            $last   = trim((string) ($_POST['last_name'] ?? ''));
            $bdate  = trim((string) ($_POST['birthdate'] ?? ''));
            $sex    = trim((string) ($_POST['sex'] ?? 'Not specified'));
            $phone  = trim((string) ($_POST['phone'] ?? ''));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $phone === '') {
                flash('Please enter the patient name, birthdate, sex, and contact number.');
                redirect($return);
            }
            $fmtBirth = ($bdate !== '' && strtotime($bdate) !== false) ? date('M d, Y', strtotime($bdate)) : $bdate;
            $provRef  = db_next_provisional_ref();

            $row = [
                $fullName,
                $fmtBirth,
                $phone,
                '—',
                initials($fullName, 2),
                $sex,
                'Not specified',
                '— (Provisional phone-in record)',
                $provRef,
                '', // Blank MRN for provisional!
                'Provisional',
                date('Y-m-d'),
                'reg_status' => 'Provisional',
                'mrn'        => '',
            ];
            array_unshift($st['patients'], $row);
            db_save_state($st);
            $logAudit('Quick add provisional', 'patients', $provRef);
            flash('Provisional record created for ' . $fullName . ' (No MRN yet — complete registration at check-in).');

            if (!empty($_POST['book_after'])) {
                redirect(url(['page' => 'book', 'step' => 2, 'patient' => $fullName]));
            }
            redirect($return);

        case 'complete_registration':
            $origPt   = trim((string) ($_POST['orig_patient'] ?? ''));
            $first    = trim((string) ($_POST['first_name'] ?? ''));
            $middle   = trim((string) ($_POST['middle_name'] ?? ''));
            $last     = trim((string) ($_POST['last_name'] ?? ''));
            $bdate    = trim((string) ($_POST['birthdate'] ?? ''));
            $sex      = trim((string) ($_POST['sex'] ?? 'Not specified'));
            $blood    = trim((string) ($_POST['blood_type'] ?? 'Not specified'));
            $phone    = trim((string) ($_POST['phone'] ?? ''));
            $email    = trim((string) ($_POST['email'] ?? ''));
            $hin      = trim((string) ($_POST['hin'] ?? '—')) ?: '—';
            $country  = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province = trim((string) ($_POST['province'] ?? 'Zamboanga del Sur'));
            $city     = trim((string) ($_POST['city'] ?? 'Zamboanga City'));
            $barangay = trim((string) ($_POST['barangay'] ?? ''));
            $street   = trim((string) ($_POST['street'] ?? 'N/A'));
            $postal   = trim((string) ($_POST['postal_code'] ?? '7000'));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            $fmtBirth = ($bdate !== '' && strtotime($bdate) !== false) ? date('M d, Y', strtotime($bdate)) : $bdate;
            $addrStr  = implode(', ', array_filter([$street, $barangay, $city, $province, $country, $postal]));
            $mrn      = db_next_mrn();
            $ptId     = str_replace('MRN-', 'P-', $mrn);

            foreach ($st['patients'] as $idx => $pt) {
                if (strcasecmp($pt[0], $origPt) === 0 || strcasecmp($pt[0], $fullName) === 0) {
                    $st['patients'][$idx] = [
                        $fullName,
                        $fmtBirth,
                        $phone,
                        $hin,
                        initials($fullName, 2),
                        $sex,
                        $blood,
                        $addrStr,
                        $ptId,
                        $mrn,
                        'Registered',
                        $pt[11] ?? date('Y-m-d'),
                        'country'        => $country,
                        'province'       => $province,
                        'city'           => $city,
                        'barangay'       => $barangay,
                        'street'         => $street,
                        'postal_code'    => $postal,
                        'phone_verified' => 1,
                        'email'          => $email,
                        'mrn'            => $mrn,
                        'reg_status'     => 'Registered',
                    ];
                    break;
                }
            }

            // Update any appointments referencing this patient and optionally check in
            $checkinIdx = isset($_POST['checkin_appt']) && $_POST['checkin_appt'] !== '' ? (int) $_POST['checkin_appt'] : -1;
            foreach ($st['appointments'] as $aIdx => &$ap) {
                if (strcasecmp((string) $ap[1], $origPt) === 0 || strcasecmp((string) $ap[1], $fullName) === 0) {
                    $ap[1] = $fullName;
                    $ap[8] = $mrn;
                    if ($aIdx === $checkinIdx) {
                        $ap[3] = 'Checked-in';
                    }
                }
            }
            unset($ap);

            db_save_state($st);
            push_sim_message(
                'sms',
                $phone,
                'West Metro Medical Center — MRN Issued',
                'Welcome ' . $fullName . '! Your permanent Medical Record Number is ' . $mrn . '. You can now activate your online Patient Portal account.',
                ['MRN' => $mrn],
                'index.php?auth=activate&phone=' . urlencode($phone),
                'Activate Patient Portal Account'
            );
            $logAudit('Completed registration & issued MRN', 'patients', $mrn);
            flash($fullName . ' is now fully Registered with permanent MRN ' . $mrn . ($checkinIdx >= 0 ? ' and Checked-in for their appointment!' : '!'));
            redirect($return);

        case 'register_patient':
            $orig     = trim((string) ($_POST['orig'] ?? ''));
            $first    = trim((string) ($_POST['first_name'] ?? ''));
            $middle   = trim((string) ($_POST['middle_name'] ?? ''));
            $last     = trim((string) ($_POST['last_name'] ?? ''));
            $bdate    = trim((string) ($_POST['birthdate'] ?? ''));
            $sex      = trim((string) ($_POST['sex'] ?? 'Not specified'));
            $phone    = trim((string) ($_POST['phone'] ?? ''));
            $blood    = trim((string) ($_POST['blood_type'] ?? 'Not specified'));
            $email    = trim((string) ($_POST['email'] ?? ''));
            $hin      = trim((string) ($_POST['hin'] ?? '—')) ?: '—';
            $country  = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province = trim((string) ($_POST['province'] ?? 'Zamboanga del Sur'));
            $city     = trim((string) ($_POST['city'] ?? 'Zamboanga City'));
            $barangay = trim((string) ($_POST['barangay'] ?? ''));
            $street   = trim((string) ($_POST['street'] ?? 'N/A'));
            $postal   = trim((string) ($_POST['postal_code'] ?? '7000'));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $phone === '') {
                flash('Please enter the patient name and phone number.');
                redirect($return);
            }
            $fmtBirth = ($bdate !== '' && strtotime($bdate) !== false) ? date('M d, Y', strtotime($bdate)) : $bdate;
            $addrStr  = implode(', ', array_filter([$street, $barangay, $city, $province, $country, $postal]));

            $idx = null;
            foreach ($st['patients'] as $i => $pt) {
                if (($orig !== '' && strcasecmp($pt[0], $orig) === 0) || strcasecmp($pt[0], $fullName) === 0) {
                    $idx = $i;
                    break;
                }
            }

            if ($idx !== null) {
                $mrn  = !empty($st['patients'][$idx][9]) ? (string) $st['patients'][$idx][9] : db_next_mrn();
                $ptId = $st['patients'][$idx][8] ?? str_replace('MRN-', 'P-', $mrn);
                $st['patients'][$idx] = [
                    $fullName, $fmtBirth, $phone, $hin, initials($fullName, 2), $sex, $blood, $addrStr, $ptId, $mrn, 'Registered', $st['patients'][$idx][11] ?? date('Y-m-d'),
                    'country' => $country, 'province' => $province, 'city' => $city, 'barangay' => $barangay, 'street' => $street, 'postal_code' => $postal, 'phone_verified' => 1, 'email' => $email, 'mrn' => $mrn, 'reg_status' => 'Registered',
                ];
                $logAudit('Updated', 'patients', $mrn);
                flash('Patient record updated for ' . $fullName . ' (' . $mrn . ').');
            } else {
                $mrn  = db_next_mrn();
                $ptId = str_replace('MRN-', 'P-', $mrn);
                $row  = [
                    $fullName, $fmtBirth, $phone, $hin, initials($fullName, 2), $sex, $blood, $addrStr, $ptId, $mrn, 'Registered', date('Y-m-d'),
                    'country' => $country, 'province' => $province, 'city' => $city, 'barangay' => $barangay, 'street' => $street, 'postal_code' => $postal, 'phone_verified' => 1, 'email' => $email, 'mrn' => $mrn, 'reg_status' => 'Registered',
                ];
                array_unshift($st['patients'], $row);
                push_sim_message(
                    'sms',
                    $phone,
                    'West Metro Medical Center — Registration Confirmed',
                    'Hello ' . $fullName . '! You are registered at West Metro Medical Center with MRN ' . $mrn . '. Use your mobile number to activate your Patient Portal account.',
                    ['MRN' => $mrn],
                    'index.php?auth=activate&phone=' . urlencode($phone),
                    'Activate Patient Portal Account'
                );
                $logAudit('Registered patient', 'patients', $mrn);
                flash($fullName . ' registered with permanent MRN ' . $mrn . '!');
            }
            db_save_state($st);
            if (!empty($_POST['book_after'])) {
                redirect(url(['page' => 'book', 'step' => 2, 'patient' => $fullName]));
            }
            redirect($return);

        case 'checkin_appointment':
            $idx = (int) ($_POST['idx'] ?? -1);
            if (isset($st['appointments'][$idx])) {
                $ptName = (string) $st['appointments'][$idx][1];
                $ptRec  = find_patient_record($st, $ptName);
                if ($ptRec && is_provisional_patient($ptRec)) {
                    flash('Provisional patient must complete full registration before check-in.');
                    redirect(url(['page' => 'appointments', 'view' => 'List', 'modal' => 'Complete registration', 'patient' => $ptName, 'checkin_appt' => $idx]));
                }
                $st['appointments'][$idx][3] = 'Checked-in';
                $logAudit('Checked-in', 'appointments', '#APT-' . (1000 + $idx));
                flash($ptName . ' checked in and ready for doctor consultation.');
            }
            redirect($return);

        case 'update_appt_status':
            $idx    = (int) ($_POST['idx'] ?? -1);
            $status = trim((string) ($_POST['status'] ?? ''));
            if (isset($st['appointments'][$idx]) && $status !== '') {
                $st['appointments'][$idx][3] = $status;
                $logAudit('Status -> ' . $status, 'appointments', '#APT-' . (1000 + $idx));
                flash('Appointment status updated to ' . $status . '.');
            }
            redirect($return);

        /* -------------------------------------------------------
         * BOOKING, ADMISSIONS, ROOMS & DOCTOR SCHEDULE
         * ----------------------------------------------------- */
        case 'book_appointment':
            $patient   = trim((string) ($_POST['patient'] ?? ''));
            $dept      = trim((string) ($_POST['department'] ?? 'General Medicine'));
            $doctor    = trim((string) ($_POST['doctor'] ?? ''));
            $dateRaw   = trim((string) ($_POST['date'] ?? ''));
            $slot      = normalize_slot(trim((string) ($_POST['slot'] ?? '09:00')));
            $visitType = trim((string) ($_POST['visit_type'] ?? 'Consultation'));
            $complaint = trim((string) ($_POST['complaint'] ?? 'Consultation'));
            $resIdx    = isset($_POST['reschedule']) ? (int) $_POST['reschedule'] : -1;

            if ($role === 'Patient') {
                $patient = current_user_name('Patient');
            }
            if ($patient === '' || $doctor === '') {
                flash('Please select a patient and doctor.');
                redirect($return);
            }
            $ymd     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw) ? $dateRaw : date('Y-m-d', strtotime('+1 day'));
            $dateFmt = date('M d, Y', strtotime($ymd));
            $ptRec   = find_patient_record($st, $patient);
            $ptRef   = $ptRec ? (patient_mrn_display($ptRec) ?: ($ptRec[8] ?? '')) : '';
            $ptParam = $role === 'Patient' ? null : $patient;

            if ($resIdx >= 0 && isset($st['appointments'][$resIdx])) {
                if ($role === 'Patient' && !can_patient_modify_appointment((string) ($st['appointments'][$resIdx][7] ?? ''), (string) ($st['appointments'][$resIdx][0] ?? ''))) {
                    flash('Appointments within 24 hours of the scheduled slot cannot be rescheduled online. Please call Front Desk.');
                    redirect(url(['page' => 'appointments']));
                }
                $st['appointments'][$resIdx][0] = $slot;
                $st['appointments'][$resIdx][4] = $complaint;
                $st['appointments'][$resIdx][7] = $dateFmt;
                $logAudit('Rescheduled', 'appointments', '#APT-' . (1000 + $resIdx));
                flash('Appointment rescheduled to ' . $dateFmt . ' at ' . slot_label($slot));
                redirect(url(['page' => 'book', 'step' => 4, 'appt' => $resIdx, 'rescheduled' => 1, 'patient' => $ptParam]));
            }

            array_unshift($st['appointments'], [$slot, $patient, $visitType, 'Scheduled', $complaint, $doctor, $dept, $dateFmt, $ptRef]);
            $logAudit('Booked', 'appointments', '#APT-' . rand(1000, 9999));
            flash('Appointment scheduled with ' . $doctor . ' on ' . $dateFmt . ' at ' . slot_label($slot));
            redirect(url(['page' => 'book', 'step' => 4, 'appt' => 0, 'patient' => $ptParam]));

        case 'update_appointment':
            $idx       = (int) ($_POST['idx'] ?? -1);
            $status    = trim((string) ($_POST['status'] ?? ''));
            $complaint = trim((string) ($_POST['complaint'] ?? ''));
            if (isset($st['appointments'][$idx])) {
                if ($status !== '') {
                    $st['appointments'][$idx][3] = $status;
                }
                if ($complaint !== '') {
                    $st['appointments'][$idx][4] = $complaint;
                }
                $logAudit('Updated appointment', 'appointments', '#APT-' . (1000 + $idx));
                flash('Appointment updated.');
            }
            redirect($return);

        case 'cancel_appt':
            $idx = (int) ($_POST['idx'] ?? -1);
            $cur = $st['appointments'][$idx] ?? null;
            if ($cur !== null) {
                if ($role === 'Patient' && !can_patient_modify_appointment((string) ($cur[7] ?? ''), (string) ($cur[0] ?? ''))) {
                    flash('Appointments within 24 hours of the scheduled slot cannot be cancelled online. Please contact Front Desk at ' . APP_PHONE . '.');
                    redirect($return);
                }
                $st['appointments'][$idx][3] = 'Cancelled';
                $logAudit('Cancelled', 'appointments', '#APT-' . (1000 + $idx));
                flash('Appointment on ' . ($cur[7] ?? '') . ' has been cancelled.');
            }
            redirect($return);

        case 'admit_patient':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $bedId   = trim((string) ($_POST['bed_id'] ?? ''));
            $doctor  = trim((string) ($_POST['doctor'] ?? 'Dr. Lorenz'));
            $reason  = trim((string) ($_POST['reason'] ?? 'Inpatient care'));

            if ($patient === '' || $bedId === '') {
                flash('Please select a Registered patient and a vacant bed.');
                redirect($return);
            }
            $ptRec = find_patient_record($st, $patient);
            if ($ptRec && is_provisional_patient($ptRec)) {
                flash('Provisional patients cannot be admitted until Complete Registration is finished.');
                redirect($return);
            }
            $mrnStr   = $ptRec ? patient_mrn_display($ptRec) : 'MRN-1001';
            $bedWard  = 'Medical Ward';
            $bedRate  = 2500.0;
            foreach ($st['beds'] as &$b) {
                if ($b['bed_id'] === $bedId) {
                    $b['status']        = 'Occupied';
                    $b['patient']       = $patient;
                    $b['mrn']           = $mrnStr;
                    $b['admitted_date'] = date('M d, Y');
                    $b['doctor']        = $doctor;
                    $bedWard            = $b['ward'];
                    $bedRate            = (float) ($b['rate'] ?? 2500.0);
                    break;
                }
            }
            unset($b);

            array_unshift($st['admissions'], [
                $patient,
                $bedId,
                $bedWard,
                date('M d, Y'),
                $doctor,
                $reason,
                'Admitted',
                'Pending',
                $mrnStr,
                '',
                '',
                [['author' => $doctor, 'time' => date('M d, Y · H:i'), 'note' => 'Admitted to ' . $bedId . ' (' . $bedWard . '). Diagnosis: ' . $reason]],
            ]);

            // Create initial inpatient bill using bed rate from Price List / Bed Setup
            $newBillNo = 'BILL-' . str_pad((string) (1001 + count($st['bills'])), 4, '0', STR_PAD_LEFT);
            $items = [
                ['Inpatient Room & Bed (' . $bedId . ' · ' . $bedWard . ')', '1', format_money($bedRate), format_money($bedRate)],
            ];
            array_unshift($st['bills'], normalize_bill_row([
                $patient,
                format_money($bedRate),
                'Unpaid',
                date('M d, Y'),
                $newBillNo,
                0.0,
                $items,
            ], 0));

            $logAudit('Admitted patient', 'admissions', $bedId);
            flash($patient . ' (' . $mrnStr . ') admitted to ' . $bedId . ' (' . $bedWard . ').');
            redirect(url(['page' => 'admissions']));

        case 'add_admission_note':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $note    = trim((string) ($_POST['progress_note'] ?? ''));
            if ($patient !== '' && $note !== '') {
                foreach ($st['admissions'] as &$adm) {
                    if (strcasecmp((string) $adm[0], $patient) === 0) {
                        if (!isset($adm[11]) || !is_array($adm[11])) {
                            $adm[11] = [];
                        }
                        array_unshift($adm[11], [
                            'author' => current_user_name('Doctor'),
                            'time'   => date('M d, Y · H:i'),
                            'note'   => $note,
                        ]);
                        break;
                    }
                }
                unset($adm);
                $logAudit('Added progress note', 'admissions', $patient);
                flash('Progress note recorded for ' . $patient . '.');
            }
            redirect(url(['page' => 'doctor-admissions', 'patient' => $patient]));

        case 'order_discharge':
            $patient   = trim((string) ($_POST['patient'] ?? ''));
            $finalDiag = trim((string) ($_POST['final_diagnosis'] ?? ''));
            $summary   = trim((string) ($_POST['discharge_summary'] ?? ''));
            foreach ($st['admissions'] as &$adm) {
                if (strcasecmp((string) $adm[0], $patient) === 0) {
                    $adm[5] = $finalDiag !== '' ? $finalDiag : ($adm[5] ?? '');
                    $adm[6] = 'For discharge';
                    $adm[9] = $summary;
                    break;
                }
            }
            unset($adm);
            $logAudit('Marked For discharge', 'admissions', $patient);
            flash($patient . ' marked "For discharge" with completed discharge summary.');
            redirect(url(['page' => 'doctor-admissions', 'patient' => $patient]));

        case 'clear_discharge_billing':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $cStatus = in_array($_POST['clearance_status'] ?? '', ['Cleared', 'Exception'], true) ? (string) $_POST['clearance_status'] : 'Cleared';
            $reason  = trim((string) ($_POST['reason'] ?? ''));
            foreach ($st['admissions'] as &$adm) {
                if (strcasecmp((string) $adm[0], $patient) === 0) {
                    $adm[7]  = $cStatus;
                    $adm[10] = $reason;
                    break;
                }
            }
            unset($adm);
            $logAudit('Billing clearance -> ' . $cStatus, 'admissions', $patient);
            flash('Discharge billing status for ' . $patient . ' set to ' . $cStatus . '.');
            redirect(url(['page' => 'discharge-clearance']));

        case 'discharge':
            $key       = trim((string) ($_POST['key'] ?? ''));
            $dType     = trim((string) ($_POST['discharge_type'] ?? 'Recovered / Routine'));
            $finalDiag = trim((string) ($_POST['final_diagnosis'] ?? 'Resolved'));
            $found     = null;
            foreach ($st['admissions'] as $r) {
                if (strcasecmp((string) $r[0], $key) === 0) {
                    $found = $r;
                    break;
                }
            }
            if (!$found) {
                flash('Admission not found.');
                redirect($return);
            }
            $bClear = (string) ($found[7] ?? 'Pending');
            if (!in_array($bClear, ['Cleared', 'Exception'], true)) {
                flash('Cannot finalize discharge yet: Cashier Billing Clearance for ' . $found[0] . ' is still Pending. Please have the Cashier mark the admission Cleared or Exception first.');
                redirect(url(['page' => 'admissions']));
            }

            $up = store_proof_image('proof', 'discharges');
            $proofPath = $up['path'] ?? null;

            $st['admissions'] = array_values(array_filter($st['admissions'], static fn ($r) => strcasecmp((string) $r[0], $key) !== 0));
            foreach ($st['beds'] as &$b) {
                if ($b['bed_id'] === $found[1] || strcasecmp((string) ($b['patient'] ?? ''), (string) $found[0]) === 0) {
                    $b['status']        = 'Vacant';
                    $b['patient']       = '';
                    $b['mrn']           = '';
                    $b['admitted_date'] = '';
                    $b['doctor']        = '';
                }
            }
            unset($b);

            $admTs    = strtotime((string) ($found[3] ?? 'today')) ?: time();
            $stayDays = max(1, (int) ceil((time() - $admTs) / 86400));
            $st['discharges'][] = [
                $found[0],
                $found[1],
                $found[2],
                date('M d, Y'),
                $proofPath,
                time(),
                $found[8] ?? 'MRN-1001',
                $found[4] ?? 'Attending Physician',
                $dType,
                $finalDiag,
                $found[3] ?? date('M d, Y'),
                $stayDays,
            ];
            $logAudit('Discharged patient', 'admissions', $found[1]);
            flash($found[0] . ' discharged (' . $dType . ') — Bed ' . $found[1] . ' is now Vacant.');
            redirect(url(['page' => 'admissions', 'view' => 'Discharged']));

        case 'set_bed_maintenance':
            $bedId  = trim((string) ($_POST['bed_id'] ?? ''));
            $target = trim((string) ($_POST['target_status'] ?? 'Maintenance'));
            $reason = trim((string) ($_POST['reason'] ?? 'Scheduled cleaning / maintenance'));
            foreach ($st['beds'] as &$b) {
                if ($b['bed_id'] === $bedId) {
                    if ($b['status'] === 'Occupied') {
                        flash('Occupied beds cannot be marked for maintenance until the patient is discharged.');
                        redirect($return);
                    }
                    $b['status']             = $target === 'Vacant' ? 'Vacant' : 'Maintenance';
                    $b['maintenance_reason'] = $target === 'Vacant' ? '' : $reason;
                    break;
                }
            }
            unset($b);
            $logAudit('Bed ' . $target, 'beds', $bedId);
            flash('Bed ' . $bedId . ' marked as ' . $target . '.');
            redirect($return);

        case 'save_doctor_schedule':
            $docName = trim((string) ($_POST['doctor_name'] ?? current_user_name('Doctor')));
            $days    = array_values((array) ($_POST['days'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']));
            $slots   = array_values((array) ($_POST['slots'] ?? ['09:00', '10:00', '11:00', '14:00']));
            $existingBlocked = $st['doctor_schedules'][$docName]['blocked_dates'] ?? [];
            $st['doctor_schedules'][$docName] = [
                'days'          => $days,
                'start_time'    => $slots[0] ?? '09:00',
                'end_time'      => end($slots) ?: '16:00',
                'slots'         => $slots,
                'blocked_dates' => $existingBlocked,
            ];
            $logAudit('Updated schedule', 'doctor_schedules', $docName);
            flash('Weekly clinic schedule updated for ' . $docName . '.');
            redirect(url(['page' => 'my-schedule']));

        case 'add_doctor_blocked_date':
            $docName = trim((string) ($_POST['doctor_name'] ?? current_user_name('Doctor')));
            $bDate   = trim((string) ($_POST['blocked_date'] ?? ''));
            $reason  = trim((string) ($_POST['reason'] ?? 'On Leave / Blocked'));
            if ($bDate !== '') {
                if (!isset($st['doctor_schedules'][$docName])) {
                    $st['doctor_schedules'][$docName] = ['days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], 'slots' => ['09:00', '10:00', '11:00', '14:00'], 'blocked_dates' => []];
                }
                $st['doctor_schedules'][$docName]['blocked_dates'][] = ['date' => $bDate, 'reason' => $reason ?: 'On Leave'];
                $logAudit('Blocked date', 'doctor_schedules', $bDate);
                flash('Blocked date ' . date('M d, Y', strtotime($bDate)) . ' added to schedule.');
            }
            redirect(url(['page' => 'my-schedule']));

        case 'remove_doctor_blocked_date':
            $docName = trim((string) ($_POST['doctor_name'] ?? current_user_name('Doctor')));
            $bDate   = trim((string) ($_POST['blocked_date'] ?? ''));
            if (isset($st['doctor_schedules'][$docName]['blocked_dates'])) {
                $st['doctor_schedules'][$docName]['blocked_dates'] = array_values(array_filter(
                    $st['doctor_schedules'][$docName]['blocked_dates'],
                    static fn ($item) => (is_array($item) ? ($item['date'] ?? '') : (string) $item) !== $bDate
                ));
                flash('Blocked date removed.');
            }
            redirect(url(['page' => 'my-schedule']));

        /* -------------------------------------------------------
         * DOCTOR CONSULTATION & PRICE LIST BILLING (Section 6)
         * ----------------------------------------------------- */
        case 'start_consultation':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $idInfo  = resolve_patient_identity($st, $patient);
            foreach ($st['appointments'] as &$appt) {
                if (matches_patient_identity($idInfo, $appt[1] ?? '', $appt[8] ?? null)
                    && in_array($appt[3], ['Scheduled', 'Checked-in', 'Confirmed', 'Waiting', 'Upcoming'], true)) {
                    $appt[3] = 'In consultation';
                }
            }
            unset($appt);
            redirect(url(['page' => 'my-appointments', 'detail' => 'new-record', 'patient' => urlencode($idInfo['name'])]));

        case 'save_record':
            $patientParam = str_replace('+', ' ', urldecode(trim((string) ($_POST['patient'] ?? ''))));
            $complaint    = trim((string) ($_POST['complaint'] ?? ''));
            $notes        = trim((string) ($_POST['notes'] ?? ''));
            $visitType    = trim((string) ($_POST['visit_type'] ?? 'Consultation'));
            $followUp     = trim((string) ($_POST['follow_up'] ?? ''));
            $recStatus    = in_array($_POST['record_status'] ?? '', ['Draft', 'Completed'], true) ? (string) $_POST['record_status'] : 'Completed';
            $editRecId    = trim((string) ($_POST['record_id'] ?? ''));

            $idInfo    = resolve_patient_identity($st, $patientParam, $_POST['patient_id'] ?? null);
            $patient   = $idInfo['name'];
            $patientId = $idInfo['id'];

            $icd10s    = array_map('trim', (array) ($_POST['icd10'] ?? []));
            $descs     = array_map('trim', (array) ($_POST['diagnosis'] ?? []));
            $diagnoses = [];
            foreach ($icd10s as $i => $code) {
                $desc = $descs[$i] ?? '';
                if ($code !== '' || $desc !== '') {
                    $diagnoses[] = [$code, $desc];
                }
            }

            $meds          = (array) ($_POST['medication'] ?? []);
            $doses         = array_map('trim', (array) ($_POST['dosage'] ?? []));
            $freqs         = (array) ($_POST['frequency'] ?? []);
            $durs          = array_map('trim', (array) ($_POST['duration'] ?? []));
            $prescriptions = [];
            foreach ($meds as $i => $med) {
                if ($med !== '' && $med !== 'Select medication') {
                    $prescriptions[] = [$med, $doses[$i] ?? '', $freqs[$i] ?? '', $durs[$i] ?? ''];
                }
            }

            $tests     = (array) ($_POST['test'] ?? []);
            $pris      = (array) ($_POST['priority'] ?? []);
            $labNotes  = array_map('trim', (array) ($_POST['lab_clinical_notes'] ?? []));
            $labOrders = [];
            foreach ($tests as $i => $test) {
                if ($test !== '' && $test !== 'Select test') {
                    $labOrders[] = [$test, $pris[$i] ?? 'Routine', $labNotes[$i] ?? ''];
                }
            }

            $recId      = $editRecId !== '' ? $editRecId : ('REC-' . strtoupper(bin2hex(random_bytes(3))));
            $doctorName = current_user_name('Doctor');
            $fmtFollow  = ($followUp !== '' && strtotime($followUp) !== false) ? date('M d, Y', strtotime($followUp)) : $followUp;

            $rec = [
                'id'            => $recId,
                'patient_id'    => $patientId,
                'patient_name'  => $patient,
                'doctor'        => $doctorName,
                'date'          => date('M d, Y'),
                'visit_type'    => $visitType,
                'complaint'     => $complaint,
                'notes'         => $notes,
                'follow_up'     => $fmtFollow,
                'diagnoses'     => $diagnoses,
                'prescriptions' => $prescriptions,
                'lab_orders'    => $labOrders,
                'status'        => $recStatus,
            ];

            if (!isset($st['records'][$patient])) {
                $st['records'][$patient] = [];
            }
            array_unshift($st['records'][$patient], $rec);

            foreach ($prescriptions as $pIdx => $rx) {
                $st['prescriptions'][] = [
                    'rx_code'         => 'RX-' . strtoupper(substr(md5($recId . '_' . $rx[0] . '_' . $pIdx), 0, 6)),
                    'record_code'     => $recId,
                    'patient_id'      => $patientId,
                    'patient_name'    => $patient,
                    'doctor_name'     => $doctorName,
                    'medication'      => $rx[0],
                    'dosage'          => $rx[1],
                    'frequency'       => $rx[2],
                    'duration'        => $rx[3],
                    'prescribed_date' => date('M d, Y'),
                    'status'          => 'Active',
                ];
            }

            foreach ($labOrders as $order) {
                $testId = strtoupper(substr(str_replace(' ', '', $order[0]), 0, 3)) . '-' . rand(1000, 9999);
                array_unshift($st['lab_results'], [
                    $testId, $patient, $order[0], 'Pending', date('M d, Y'), $patientId, $order[1] ?? 'Routine', $order[2] ?? '', $recId, $doctorName, '',
                ]);
            }

            foreach ($st['appointments'] as &$appt) {
                if (matches_patient_identity($idInfo, $appt[1] ?? '', $appt[8] ?? null)) {
                    $appt[3] = $recStatus === 'Draft' ? 'In consultation' : 'Completed';
                }
            }
            unset($appt);

            // Generate itemized charges using the hospital Price List ($st['price_list'])
            $consultFee = price_list_lookup($st, $visitType, 800.0);
            $billItems  = [
                ['Consultation Fee (' . $visitType . ')', '1', format_money($consultFee), format_money($consultFee)],
            ];
            $totalBill = $consultFee;

            foreach ($labOrders as $lo) {
                $tName  = (string) ($lo[0] ?? 'Lab Test');
                $tPrice = price_list_lookup($st, $tName, 450.0);
                $billItems[] = [$tName . ' (Lab / Diagnostic Order)', '1', format_money($tPrice), format_money($tPrice)];
                $totalBill  += $tPrice;
            }

            foreach ($prescriptions as $rx) {
                $mName  = (string) ($rx[0] ?? 'Medication');
                $mPrice = price_list_lookup($st, $mName, 300.0);
                $billItems[] = [$mName . ' ' . ($rx[1] ?? '') . ' (Pharmacy Prescription)', '1', format_money($mPrice), format_money($mPrice)];
                $totalBill  += $mPrice;
            }

            $billNo = 'BILL-' . str_pad((string) (1001 + count($st['bills'])), 4, '0', STR_PAD_LEFT);
            array_unshift($st['bills'], [
                $patient,
                format_money($totalBill),
                'Unpaid',
                date('M d, Y'),
                $billNo,
                0.0,
                $billItems,
            ]);

            $logAudit('Completed consultation', 'medical_records', '#' . $recId);
            flash('Consultation saved and Bill ' . $billNo . ' (' . format_money($totalBill) . ') generated from the Price List.');
            redirect(url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => urlencode($patient)]));

        /* -------------------------------------------------------
         * CASHIER MANUAL CHARGE & PAYMENTS (Section 7)
         * ----------------------------------------------------- */
        case 'add_manual_charge':
            $billNo = trim((string) ($_POST['bill_no'] ?? ''));
            $desc   = trim((string) ($_POST['item_desc'] ?? ''));
            $qty    = max(1, (int) ($_POST['item_qty'] ?? 1));
            $rate   = (float) ($_POST['item_rate'] ?? 0);
            $reason = trim((string) ($_POST['reason'] ?? ''));

            if ($billNo === '' || $desc === '' || $reason === '') {
                flash('Please provide a charge description, amount, and required reason.');
                redirect(url(['page' => 'unpaid-bills']));
            }

            foreach ($st['bills'] as $idx => $b) {
                $nb = normalize_bill_row($b, $idx);
                if (strcasecmp($nb['bill_no'], $billNo) === 0) {
                    $lineAmt     = round($qty * $rate, 2);
                    $nb['items'][] = [$desc . ' (Reason: ' . $reason . ')', (string) $qty, format_money($rate), format_money($lineAmt)];
                    $newTotal    = round($nb['total'] + $lineAmt, 2);
                    $newStatus   = ($newTotal - $nb['paid']) > 0.009 ? ($nb['paid'] > 0 ? 'Partial' : 'Unpaid') : 'Paid';
                    $st['bills'][$idx] = normalize_bill_row([
                        $nb['patient'],
                        format_money($newTotal),
                        $newStatus,
                        $nb['issued'],
                        $nb['bill_no'],
                        $nb['paid'],
                        $nb['items'],
                    ], $idx);
                    $logAudit('Added manual charge (' . $reason . ')', 'bills', $billNo);
                    flash('Manual charge "' . $desc . '" (' . format_money($lineAmt) . ') added to ' . $billNo . '.');
                    break;
                }
            }
            redirect(url(['page' => 'unpaid-bills']));

        case 'process_payment':
            $billNo  = trim((string) ($_POST['bill_no'] ?? ''));
            $amtPay  = parse_money($_POST['amount_to_pay'] ?? 0);
            $amtRecv = parse_money($_POST['amount_received'] ?? 0);
            $method  = trim((string) ($_POST['payment_method'] ?? 'Cash'));
            $refNo   = trim((string) ($_POST['reference_no'] ?? ''));
            if (!in_array($method, ['Cash', 'Card', 'HMO', 'PhilHealth'], true)) {
                $method = 'Cash';
            }

            $billIdx  = null;
            $normBill = null;
            foreach ($st['bills'] as $i => $b) {
                $nb = normalize_bill_row($b, $i);
                if (strcasecmp($nb['bill_no'], $billNo) === 0) {
                    $billIdx  = $i;
                    $normBill = $nb;
                    break;
                }
            }
            if ($billIdx === null || $normBill === null) {
                flash('Bill not found.');
                redirect($return);
            }
            $balanceDue = $normBill['balance'];
            if ($amtPay <= 0 || $amtPay > $balanceDue) {
                $amtPay = $balanceDue;
            }
            if ($amtRecv < $amtPay) {
                $amtRecv = $amtPay;
            }
            $changeDue  = max(0.0, round($amtRecv - $amtPay, 2));
            $newPaid    = min($normBill['total'], round($normBill['paid'] + $amtPay, 2));
            $newBalance = max(0.0, round($normBill['total'] - $newPaid, 2));
            $newStatus  = $newBalance <= 0.009 ? 'Paid' : 'Partial';

            $st['bills'][$billIdx] = normalize_bill_row([
                $normBill['patient'],
                format_money($normBill['total']),
                $newStatus,
                $normBill['issued'],
                $normBill['bill_no'],
                $newPaid,
                $normBill['items'],
            ], $billIdx);

            $maxOr = 1000;
            foreach ($st['payments'] ?? [] as $existingPy) {
                if (preg_match('/^OR-0*(\d+)$/i', (string) ($existingPy['receipt_no'] ?? ''), $om)) {
                    $maxOr = max($maxOr, (int) $om[1]);
                }
            }
            $receiptNo = 'OR-' . str_pad((string) ($maxOr + 1), 4, '0', STR_PAD_LEFT);
            array_unshift($st['payments'], [
                'receipt_no'      => $receiptNo,
                'bill_no'         => $normBill['bill_no'],
                'patient_name'    => $normBill['patient'],
                'amount_paid'     => $amtPay,
                'amount_received' => $amtRecv,
                'change_due'      => $changeDue,
                'payment_method'  => $method,
                'reference_no'    => $method === 'Cash' ? '—' : $refNo,
                'cashier_name'    => current_user_name($role),
                'paid_date'       => date('Y-m-d'),
                'paid_at'         => date('M d, Y · H:i'),
                'items'           => $normBill['items'],
            ]);
            $logAudit('Collected payment (' . $method . ')', 'payments', $receiptNo);
            flash('Payment of ' . format_money($amtPay) . ' via ' . $method . ' recorded (' . $receiptNo . ').');
            redirect(url(['page' => 'payment-history', 'detail' => 'receipt', 'or' => $receiptNo]));

        case 'create_bill':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $descs   = (array) ($_POST['item_desc'] ?? []);
            $qtys    = (array) ($_POST['item_qty'] ?? []);
            $rates   = (array) ($_POST['item_rate'] ?? []);
            $items   = [];
            $total   = 0.0;
            foreach ($descs as $k => $dRaw) {
                $desc = trim((string) $dRaw);
                $qty  = max(1, (int) ($qtys[$k] ?? 1));
                $rate = parse_money($rates[$k] ?? 0);
                if ($desc !== '' && $rate > 0) {
                    $lineAmt = round($qty * $rate, 2);
                    $total  += $lineAmt;
                    $items[] = [$desc, (string) $qty, format_money($rate), format_money($lineAmt)];
                }
            }
            if ($patient !== '' && $items) {
                $newBillNo = 'BILL-' . str_pad((string) (1001 + count($st['bills'])), 4, '0', STR_PAD_LEFT);
                array_unshift($st['bills'], normalize_bill_row([
                    $patient, format_money($total), 'Unpaid', date('M d, Y'), $newBillNo, 0.0, $items,
                ], 0));
                $logAudit('Created manual bill', 'bills', $newBillNo);
                flash('Bill ' . $newBillNo . ' (' . format_money($total) . ') created for ' . $patient . '.');
            }
            redirect($return);

        /* -------------------------------------------------------
         * ADMIN PORTAL ACTIONS (Section 8)
         * ----------------------------------------------------- */
        case 'save_user':
            $orig      = trim((string) ($_POST['orig'] ?? ''));
            $first     = trim((string) ($_POST['first_name'] ?? ''));
            $middle    = trim((string) ($_POST['middle_name'] ?? ''));
            $last      = trim((string) ($_POST['last_name'] ?? ''));
            $uRole     = trim((string) ($_POST['role'] ?? 'Doctor'));
            $email     = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone     = trim((string) ($_POST['phone'] ?? ''));
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $sex       = trim((string) ($_POST['sex'] ?? ''));
            $dept      = trim((string) ($_POST['department'] ?? ''));
            $spec      = trim((string) ($_POST['specialty'] ?? ''));
            $license   = trim((string) ($_POST['license'] ?? ''));

            // Specialty, Department, and PRC License are visible/required ONLY when Role = "Doctor"
            if ($uRole !== 'Doctor') {
                $dept    = '';
                $spec    = '';
                $license = '';
            }

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $email === '') {
                flash('Please enter the employee name and email.');
                redirect($return);
            }
            $prefix    = db_prefix_for_role($uRole);
            $newUserId = db_next_user_id($prefix);
            $tempPw    = bin2hex(random_bytes(16)); // Secure random initial hash; creating admin never sees user password

            if ($orig !== '') {
                foreach ($st['users'] as &$u) {
                    if (strcasecmp((string) $u[1], $orig) === 0) {
                        $u[0]  = $fullName;
                        $u[1]  = $email;
                        $u[2]  = $uRole;
                        $u[5]  = $dept;
                        $u[6]  = $spec;
                        $u[9]  = $license;
                        $u[10] = $birthdate;
                        $u[11] = $phone;
                        break;
                    }
                }
                unset($u);
                $logAudit('Updated user', 'users', $email);
                flash('Account updated for ' . $fullName . '.');
            } else {
                $created = db_create_account([
                    'user_id'        => $newUserId,
                    'username'       => $fullName,
                    'email'          => $email,
                    'password'       => $tempPw,
                    'full_name'      => $fullName,
                    'role'           => $uRole,
                    'status'         => 'Pending', // Inactive/pending until password set via secure email link
                    'department'     => $dept,
                    'specialty'      => $spec,
                    'license'        => $license,
                    'birthdate'      => $birthdate,
                    'phone'          => $phone,
                    'phone_verified' => 0,
                    'sex'            => $sex,
                ], true);

                $finalId   = (string) ($created['account']['user_id'] ?? $newUserId);
                $token     = bin2hex(random_bytes(24)); // Secure single-use token
                $expiresAt = time() + (24 * 3600);      // 24 hours validity
                db()->prepare('UPDATE accounts SET status = "Pending", force_password_change = 1, set_password_token = :tk, set_password_expires_at = :exp WHERE UPPER(user_id) = :uid')
                    ->execute([':tk' => $token, ':exp' => $expiresAt, ':uid' => strtoupper($finalId)]);

                $st = db_load_state();
                $_SESSION['state'] = &$st;

                $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $dir      = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
                $baseUrl  = $scheme . '://' . $host . $dir;
                $setPwUrl = $baseUrl . '/index.php?auth=login&set_password=1&uid=' . urlencode($finalId) . '&token=' . urlencode($token);

                $mailRes = send_account_activation_email($email, $fullName, $uRole, $finalId, $setPwUrl);
                $logAudit('Created ' . $uRole . ' account (Pending activation)', 'users', '#' . $finalId);

                if (!empty($mailRes['ok'])) {
                    flash('Created ' . $uRole . ' account for ' . $fullName . '. An activation email has been sent to ' . $email . '.');
                } else {
                    flash('Created ' . $uRole . ' account for ' . $fullName . '. Activation link ready (Status: ' . ($mailRes['error'] ?? 'Pending SMTP configuration') . ').');
                }
            }
            redirect($return);

        case 'resend_activation_email':
            $uid   = trim((string) ($_POST['user_id'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));

            $acc = null;
            if ($uid !== '') {
                $acc = db_find_account_by_id($uid);
            }
            if (!$acc && $email !== '') {
                $stmt = db()->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) LIMIT 1');
                $stmt->execute([':em' => $email]);
                $acc = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }

            if (!$acc) {
                flash('Account not found.');
                redirect($return);
            }

            $finalId   = (string) $acc['user_id'];
            $finalName = (string) $acc['full_name'];
            $finalRole = (string) $acc['role'];
            $destEmail = (string) $acc['email'];

            $token     = bin2hex(random_bytes(24));
            $expiresAt = time() + (24 * 3600); // 24-hr expiration
            db()->prepare('UPDATE accounts SET status = "Pending", force_password_change = 1, set_password_token = :tk, set_password_expires_at = :exp WHERE id = :id')
                ->execute([':tk' => $token, ':exp' => $expiresAt, ':id' => $acc['id']]);

            $st = db_load_state();
            $_SESSION['state'] = &$st;

            $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir      = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $baseUrl  = $scheme . '://' . $host . $dir;
            $setPwUrl = $baseUrl . '/index.php?auth=login&set_password=1&uid=' . urlencode($finalId) . '&token=' . urlencode($token);

            $mailRes = send_account_activation_email($destEmail, $finalName, $finalRole, $finalId, $setPwUrl);
            $logAudit('Resent activation email', 'accounts', '#' . $finalId);

            if (!empty($mailRes['ok'])) {
                flash('Activation email resent successfully to ' . $destEmail . '.');
            } else {
                flash('Activation link refreshed for ' . $destEmail . '. (Status: ' . ($mailRes['error'] ?? 'Simulated preview available') . ').');
            }
            redirect($return);

        case 'send_set_password_link':
            $uid   = trim((string) ($_POST['user_id'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $name  = trim((string) ($_POST['name'] ?? 'User'));
            $token = bin2hex(random_bytes(24));
            $expiresAt = time() + (24 * 3600);
            db()->prepare('UPDATE accounts SET force_password_change = 1, set_password_token = :tk, set_password_expires_at = :exp WHERE UPPER(user_id) = :uid')
                ->execute([':tk' => $token, ':exp' => $expiresAt, ':uid' => strtoupper($uid)]);

            $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir      = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $baseUrl  = $scheme . '://' . $host . $dir;
            $setPwUrl = $baseUrl . '/index.php?auth=login&set_password=1&uid=' . urlencode($uid) . '&token=' . urlencode($token);

            push_sim_message(
                'email',
                $email ?: $uid,
                'West Metro Medical Center — Password Reset / Setup Link',
                'A password setup link was generated for ' . $name . ' (' . $uid . '). Use the button below to choose a new password.',
                ['Account ID' => $uid],
                $setPwUrl,
                'Open Set-Password Screen'
            );
            $logAudit('Sent password setup link', 'users', '#' . $uid);
            flash('Set-Password link sent to ' . $name . ' (' . $uid . ').');
            redirect($return);

        case 'toggle_user':
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $stmt  = db()->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) LIMIT 1');
            $stmt->execute([':em' => $email]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($acc) {
                $targetRole   = (string) $acc['role'];
                $targetStatus = (string) $acc['status'];
                $targetUid    = (string) $acc['user_id'];
                $targetName   = (string) $acc['full_name'];

                // Block deactivating the only active Administrator
                if ($targetRole === 'Admin' && $targetStatus === 'Active') {
                    $activeAdminCount = (int) db()->query('SELECT COUNT(*) FROM accounts WHERE role = "Admin" AND status = "Active"')->fetchColumn();
                    if ($activeAdminCount <= 1) {
                        flash('Cannot deactivate the only active Administrator. You must create and activate a new Administrator first before deactivating this account.');
                        redirect($return);
                    }
                }

                $newStatus = ($targetStatus === 'Active' || $targetStatus === 'Pending') ? 'Disabled' : 'Active';
                db_toggle_account_by_email($email, $newStatus);

                // If deactivating an Admin (Turnover), record snapshot and preserve records in Archive
                if ($targetRole === 'Admin' && $newStatus === 'Disabled') {
                    $year = date('Y');
                    $arcCode = 'ARC-ADMIN-' . preg_replace('/[^0-9A-Z]/', '', strtoupper($targetUid)) . '-' . $year;

                    $chk = db()->prepare('SELECT id FROM archive_records WHERE archive_code = :ac LIMIT 1');
                    $chk->execute([':ac' => $arcCode]);
                    if (!$chk->fetchColumn()) {
                        db_archive_record($targetName . ' (' . $targetUid . ')', 'Admin Turnover', current_user_name('Admin'), [
                            'admin_id'       => $targetUid,
                            'admin_name'     => $targetName,
                            'admin_email'    => $email,
                            'year'           => $year,
                            'deactivated_at' => date('Y-m-d H:i:s'),
                            'deactivated_by' => current_user_name('Admin'),
                            'notes'          => 'Turnover archive: records managed under ' . $targetName . ' preserved in read-only status.',
                        ]);
                    }
                    $logAudit('Deactivated Admin for turnover', 'accounts', '#' . $targetUid);
                    flash('Administrator ' . $targetName . ' has been deactivated for turnover. Historical records are preserved in the read-only Archive.');
                } else {
                    $logAudit($newStatus === 'Active' ? 'Reactivated' : 'Deactivated', 'accounts', '#' . $targetUid);
                    flash($targetName . ' account ' . ($newStatus === 'Active' ? 'reactivated' : 'deactivated') . '.');
                }

                $st = db_load_state();
                $_SESSION['state'] = &$st;
            }
            redirect($return);

        case 'save_room_setup':
            $origBed  = trim((string) ($_POST['orig_bed_id'] ?? ''));
            $roomNo   = trim((string) ($_POST['room_no'] ?? ''));
            $bedCode  = trim((string) ($_POST['bed_code'] ?? 'Bed A'));
            $roomType = trim((string) ($_POST['room_type'] ?? 'Private'));
            $ward     = trim((string) ($_POST['ward'] ?? 'Medical Ward'));
            $floor    = trim((string) ($_POST['floor'] ?? '2nd Floor'));
            $rate     = (float) ($_POST['rate'] ?? 3200.0);

            $letter = strtoupper(substr(trim(str_replace('Bed', '', $bedCode)), 0, 1)) ?: 'A';
            $newId  = $origBed !== '' ? $origBed : ('RM-' . $roomNo . '-' . $letter);

            $updated = false;
            foreach ($st['beds'] as &$b) {
                if ($b['bed_id'] === $newId) {
                    $b['room_no']   = $roomNo;
                    $b['bed_code']  = $bedCode;
                    $b['room_type'] = $roomType;
                    $b['ward']      = $ward;
                    $b['floor']     = $floor;
                    $b['rate']      = $rate;
                    $updated        = true;
                    break;
                }
            }
            unset($b);
            if (!$updated) {
                $st['beds'][] = [
                    'bed_id'             => $newId,
                    'room_no'            => $roomNo,
                    'bed_code'           => $bedCode,
                    'ward'               => $ward,
                    'room_type'          => $roomType,
                    'floor'              => $floor,
                    'rate'               => $rate,
                    'status'             => 'Vacant',
                    'patient'            => '',
                    'mrn'                => '',
                    'admitted_date'      => '',
                    'doctor'             => '',
                    'maintenance_reason' => '',
                    'active'             => true,
                ];
            }
            $logAudit($updated ? 'Updated bed setup' : 'Added bed', 'beds', $newId);
            flash('Bed ' . $newId . ' saved (' . format_money($rate) . '/day).');
            redirect(url(['page' => 'rooms']));

        case 'toggle_bed_active':
            $bedId = trim((string) ($_POST['bed_id'] ?? ''));
            foreach ($st['beds'] as &$b) {
                if ($b['bed_id'] === $bedId) {
                    $b['active'] = empty($b['active']);
                    $logAudit($b['active'] ? 'Activated bed' : 'Deactivated bed', 'beds', $bedId);
                    flash('Bed ' . $bedId . ' ' . ($b['active'] ? 'activated' : 'deactivated') . '.');
                    break;
                }
            }
            unset($b);
            redirect(url(['page' => 'rooms']));

        case 'save_price_item':
            $id    = trim((string) ($_POST['id'] ?? ''));
            $cat   = trim((string) ($_POST['category'] ?? 'Consultation'));
            $name  = trim((string) ($_POST['name'] ?? ''));
            $price = (float) ($_POST['price'] ?? 0);
            if ($name !== '' && $price > 0) {
                if ($id !== '') {
                    foreach ($st['price_list'] as &$pl) {
                        if ($pl['id'] === $id) {
                            $pl['category'] = $cat;
                            $pl['name']     = $name;
                            $pl['price']    = $price;
                            break;
                        }
                    }
                    unset($pl);
                    $logAudit('Updated price item', 'price_list', $id);
                } else {
                    $newId = 'PL-' . (101 + count($st['price_list']));
                    $st['price_list'][] = ['id' => $newId, 'category' => $cat, 'name' => $name, 'price' => $price, 'active' => true];
                    $logAudit('Added price item', 'price_list', $newId);
                }
                flash('Price List item "' . $name . '" saved (' . format_money($price) . ').');
            }
            redirect(url(['page' => 'price-list']));

        case 'toggle_price_item':
            $id = trim((string) ($_POST['id'] ?? ''));
            foreach ($st['price_list'] as &$pl) {
                if ($pl['id'] === $id) {
                    $pl['active'] = empty($pl['active']);
                    $logAudit($pl['active'] ? 'Activated price item' : 'Deactivated price item', 'price_list', $id);
                    flash('Price item ' . $id . ' ' . ($pl['active'] ? 'activated' : 'deactivated') . '.');
                    break;
                }
            }
            unset($pl);
            redirect(url(['page' => 'price-list']));

        case 'export_archive_zip':
            if (($_SESSION['role'] ?? '') !== 'Admin') {
                redirect('index.php');
            }
            $scope      = (string) ($_POST['export_scope'] ?? 'selected');
            $selected   = (array) ($_POST['selected_files'] ?? []);
            $q          = trim((string) ($_POST['filter_q'] ?? ''));
            $from       = trim((string) ($_POST['filter_from'] ?? ''));
            $to         = trim((string) ($_POST['filter_to'] ?? ''));
            $categories = ['Patients', 'Billing', 'Appointments', 'Admissions', 'Medical_Records', 'Audit_Logs'];

            $zipFiles = [];
            if ($scope === 'selected') {
                if (empty($selected)) {
                    flash('Please select at least one category file to export.');
                    redirect('index.php?page=archive');
                }
                foreach ($selected as $item) {
                    // Format: Year__AdminId__Category
                    $parts = explode('__', (string) $item, 3);
                    if (count($parts) === 3) {
                        [$y, $aid, $cat] = $parts;
                        $csv = archive_generate_csv($cat, $st, $q ?: null, $from ?: null, $to ?: null);
                        $zipFiles["{$y}/{$aid}/{$cat}.csv"] = $csv;
                    }
                }
                $zipName = 'WestMetro_Archive_Selected_' . date('Ymd_His') . '.zip';
            } else {
                // Scope format: Year__AdminId
                $parts = explode('__', $scope, 2);
                $year  = $parts[0] ?? date('Y');
                $aid   = $parts[1] ?? 'A-0001';

                foreach ($categories as $cat) {
                    $csv = archive_generate_csv($cat, $st, $q ?: null, $from ?: null, $to ?: null);
                    $zipFiles["{$year}/{$aid}/{$cat}.csv"] = $csv;
                }
                $zipName = "WestMetro_Archive_{$year}_{$aid}_" . date('Ymd') . '.zip';
            }

            if (empty($zipFiles)) {
                flash('No files available for export.');
                redirect('index.php?page=archive');
            }

            $zipBinary = archive_build_pkzip($zipFiles);
            $logAudit('Exported archive ZIP package', 'archive', $zipName);

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . strlen($zipBinary));
            header('Pragma: no-cache');
            echo $zipBinary;
            exit;

        case 'save_crud':
            $type = trim((string) ($_POST['type'] ?? ''));
            $orig = trim((string) ($_POST['orig'] ?? ''));
            if ($type === 'departments') {
                $name   = trim((string) ($_POST['dept_name'] ?? ''));
                $head   = trim((string) ($_POST['dept_head'] ?? 'Unassigned'));
                $loc    = trim((string) ($_POST['dept_location'] ?? 'Main Wing'));
                $newRow = [$name, $head, '1', $loc];
                $idx    = null;
                foreach ($st['crud']['departments'] as $i => $r) {
                    if (($orig !== '' && $r[0] === $orig) || strcasecmp($r[0], $name) === 0) {
                        $idx = $i;
                        break;
                    }
                }
                if ($idx !== null) {
                    $st['crud']['departments'][$idx] = $newRow;
                } else {
                    $st['crud']['departments'][] = $newRow;
                }
                $logAudit('Saved department', 'departments', $name);
                flash('Department "' . $name . '" saved.');
            }
            redirect($return);

        case 'restore_archive':
            $arcId = (string) ($_POST['key'] ?? '');
            $st['archive'] = array_values(array_filter($st['archive'] ?? [], static fn ($r) => $r[0] !== $arcId));
            $logAudit('Restored', 'archive', $arcId);
            flash($arcId . ' restored.');
            redirect($return);
    }
    redirect($return);
}

/* ---------------------------------------------------------------
 * 2. Not signed in -> landing page, login screen, or patient activation screen
 * ------------------------------------------------------------- */
$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['Admin', 'Staff', 'Cashier', 'Doctor', 'Patient'], true)) {
    $authView = (string) ($_GET['auth'] ?? '');
    if (in_array($authView, ['register', 'activate'], true) || !empty($_SESSION['register_error']) || !empty($_SESSION['activate_error'])) {
        require __DIR__ . '/pages/register.php';
        exit;
    }
    $showLogin = $authView === 'login'
        || !empty($_SESSION['login_error'])
        || !empty($_SESSION['reset_sent']);
    require __DIR__ . ($showLogin ? '/pages/login.php' : '/pages/landing.php');
    exit;
}

/* ---------------------------------------------------------------
 * 3. Routing
 * ------------------------------------------------------------- */
$nav    = nav_for($role);
$routes = routes_for($role);
$page   = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($routes[$page])) {
    $page = 'dashboard';
}

$detail     = $_GET['detail'] ?? null;
$detailFile = $detail !== null ? (details_for($role)[$detail] ?? null) : null;
$modal      = isset($_GET['modal']) ? (string) $_GET['modal'] : null;

if ($modal === 'Book appointment' && in_array($role, ['Patient', 'Staff'], true)) {
    redirect(url(['page' => 'book', 'dept' => $_GET['dept'] ?? null, 'doc' => $_GET['doc'] ?? null, 'step' => isset($_GET['doc']) ? 2 : null]));
}

$state = &state();
$toast = take_flash();
$title = 'Dashboard';
foreach ($nav as [$label, , $slug]) {
    if ($slug === $page) {
        $title = $label;
    }
}
if ($page === 'book') {
    $title = 'Book appointment';
}
$navActive = $page === 'book' ? 'appointments' : $page;

/* ---------------------------------------------------------------
 * 4. Render
 * ------------------------------------------------------------- */
require __DIR__ . '/layouts/app_header.php';
require __DIR__ . '/' . ($detailFile ?? $routes[$page]);
require __DIR__ . '/layouts/app_footer.php';
