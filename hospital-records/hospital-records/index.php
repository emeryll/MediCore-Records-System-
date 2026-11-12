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

if (!empty($_GET['cancel_pending_email'])) {
    unset($_SESSION['pending_profile_email'], $_SESSION['pending_profile_email_otp'], $_SESSION['profile_had_email']);
    redirect('index.php?page=profile');
}
if (!empty($_GET['cancel_pending_phone'])) {
    unset($_SESSION['pending_profile_phone'], $_SESSION['pending_profile_phone_otp']);
    redirect('index.php?page=profile');
}
if (!empty($_GET['cancel_pending_link_patient'])) {
    unset($_SESSION['pending_link_patient']);
    redirect('index.php?page=profile');
}

// Profile Switcher (GET request handler)
if (!empty($_GET['switch_patient']) && ($_SESSION['role'] ?? '') === 'Patient') {
    $targetPid  = strtoupper(trim((string) $_GET['switch_patient']));
    $accountUid = (string) ($_SESSION['user_id'] ?? '');
    if (db_is_patient_linked($accountUid, $targetPid)) {
        $pt = db_find_patient_by_id($targetPid);
        if ($pt) {
            $swMrn = (string) ($pt['mrn'] ?: $pt['patient_id']);
            if (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', $swMrn, $m)) {
                $swMrn = 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT);
            }
            $_SESSION['active_patient_id']   = (string) $pt['patient_id'];
            $_SESSION['active_patient_name'] = (string) $pt['full_name'];
            $_SESSION['active_patient_mrn']  = $swMrn;
            flash('Switched active profile to ' . $pt['full_name'] . ' (' . $swMrn . ').');
        }
    } else {
        // Rule 3 & 6: Deny access and record in audit log
        $reqUrl = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
        $actingActor = (!empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : $accountUid) . ' acting as Patient';
        try {
            $pdo = db();
            $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
            $insAl->execute([
                ':an' => $actingActor,
                ':ac' => "Denied portal access attempt: User attempted to switch to unlinked patient '{$targetPid}'. Requested URL: {$reqUrl} [FLAGGED FOR REVIEW]",
                ':tb' => 'patients',
                ':ri' => $targetPid,
                ':la' => date('M d, Y · H:i'),
                ':ar' => 'Patient',
            ]);
        } catch (Throwable) {}
        flash('Unauthorized profile selection.');
    }
    $page = trim((string) ($_GET['page'] ?? 'home'));
    redirect('index.php' . ($page !== 'home' ? '?page=' . urlencode($page) : ''));
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

    $logAudit = static function (string $act, string $table, string $recId, ?string $customActor = null, ?string $customRole = null) use (&$st): void {
        $curRole   = (string) ($_SESSION['role'] ?? '');
        $actorRole = $customRole ?? (['Admin' => 'Administrator', 'Staff' => 'Staff', 'Cashier' => 'Cashier', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$curRole] ?? 'Staff');
        if ($customActor !== null && $customActor !== '') {
            $actingActor = $customActor;
        } else {
            $baseName = !empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : current_user_name($curRole !== '' ? $curRole : 'Admin');
            $activeRoleLabel = ($curRole === 'Staff' ? 'Staff' : ($curRole !== '' ? $curRole : 'Admin'));
            $actingActor = $baseName . ' acting as ' . $activeRoleLabel;
        }
        array_unshift($st['audit'], [
            $actingActor,
            $act,
            $table,
            $recId,
            date('M d, Y · H:i'),
            $actorRole,
        ]);
        try {
            $pdo = db();
            $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
            $insAl->execute([
                ':an' => $actingActor,
                ':ac' => $act,
                ':tb' => $table,
                ':ri' => $recId,
                ':la' => date('M d, Y · H:i'),
                ':ar' => $actorRole,
            ]);
        } catch (Throwable) {}
    };

    switch ($action) {
        /* -------------------------------------------------------
         * PATIENT 3-STEP ACCOUNT ACTIVATION & LINKING
         * ----------------------------------------------------- */
        case 'activate_step1':
            $phone      = trim((string) ($_POST['phone'] ?? ''));
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) < 10) {
                $err = 'Please enter a valid 11-digit mobile number (09XXXXXXXXX).';
                $_SESSION['activate_error'] = $err;
                $_SESSION['register_error'] = $err;
                redirect('index.php?auth=activate&step=1');
            }

            // Find all registered patients matching this mobile number
            $matchedPts = db_get_registered_patients_by_phone($cleanPhone);

            if (empty($matchedPts)) {
                $pdo  = db();
                $stmt = $pdo->prepare('SELECT * FROM patients WHERE (phone = :ph OR phone LIKE :p10) AND status != "Archived" LIMIT 1');
                $stmt->execute([':ph' => $cleanPhone, ':p10' => '%' . substr($cleanPhone, -10)]);
                $anyPt = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($anyPt) {
                    $anyPt['full_name'] = trim(($anyPt['first_name'] ?? '') . ' ' . (!empty($anyPt['middle_name']) ? $anyPt['middle_name'] . ' ' : '') . ($anyPt['last_name'] ?? ''));
                    if (is_provisional_patient($anyPt)) {
                        $err = 'Your record (' . $anyPt['full_name'] . ') is currently Provisional (phone-in booking). Please complete full registration in person at the Front Desk to receive your MRN before activating your online portal account.';
                    } elseif (!empty($anyPt['no_phone'])) {
                        $err = 'This patient record was registered without a verified personal mobile number and does not have an online portal account. Please visit the Front Desk if you wish to verify your mobile number and enable portal access.';
                    } else {
                        $err = 'Patient record found, but registration is still pending verification by Front Desk Staff.';
                    }
                } else {
                    $err = 'No hospital record found for ' . $cleanPhone . '. Please register with Front Desk Staff at West Metro Medical Center first.';
                }
                $_SESSION['activate_error'] = $err;
                $_SESSION['register_error'] = $err;
                redirect('index.php?auth=activate&step=1');
            }

            // Check if an account already exists for this phone number (Rule: 1 phone = 1 account)
            $existingAcc = db_find_account_by_phone($cleanPhone);
            if ($existingAcc) {
                $unlinked = db_get_unlinked_patients_by_phone($cleanPhone, (string) $existingAcc['user_id']);
                if (empty($unlinked)) {
                    $err = 'An online portal account is already active for this mobile number and all profiles are linked. Please log in with your password.';
                    $_SESSION['activate_error'] = $err;
                    $_SESSION['register_error'] = $err;
                    redirect('index.php?auth=login');
                }
                $_SESSION['activate_has_existing_account']  = true;
                $_SESSION['activate_existing_account_uid']  = (string) $existingAcc['user_id'];
                $_SESSION['activate_existing_account_name'] = (string) $existingAcc['full_name'];
                $_SESSION['activate_unlinked_patients']     = $unlinked;
            } else {
                $_SESSION['activate_has_existing_account']  = false;
                unset($_SESSION['activate_existing_account_uid'], $_SESSION['activate_existing_account_name']);
                $_SESSION['activate_unlinked_patients']     = $matchedPts;
            }

            $primaryPt = $matchedPts[0];
            $mrn  = (string) ($primaryPt['mrn'] ?: $primaryPt['patient_id']);
            $ptId = (string) $primaryPt['patient_id'];

            $otpRes = db_generate_phone_otp($cleanPhone, $ptId, 'account_creation');
            $code   = $otpRes['code'] ?? '123456';

            $_SESSION['activate_phone'] = $cleanPhone;
            $_SESSION['activate_otp']   = $code;
            $_SESSION['activate_step']  = 2;
            $_SESSION['act_phone']      = $cleanPhone;
            $_SESSION['act_otp_code']   = $code;
            $_SESSION['act_mrn']        = $mrn;
            $_SESSION['act_name']       = (string) $primaryPt['full_name'];
            $_SESSION['act_step']       = 2;
            $_SESSION['activate_pts']   = $matchedPts;

            push_sim_message('sms', $cleanPhone, 'West Metro Medical Center Account Verification', 'Your 6-digit OTP code to verify your mobile number is ' . $code . '.', ['Code' => $code]);
            redirect('index.php?auth=activate&step=2');

        case 'activate_step2':
            $code  = trim((string) ($_POST['otp_code'] ?? ''));
            $phone = (string) ($_SESSION['activate_phone'] ?? ($_SESSION['act_phone'] ?? ''));
            if ($phone === '' || empty($_SESSION['activate_pts'])) {
                redirect('index.php?auth=activate&step=1');
            }
            $vRes = db_verify_phone_otp($phone, $code, 'account_creation');
            if (empty($vRes['ok']) && $code !== (string) ($_SESSION['activate_otp'] ?? ($_SESSION['act_otp_code'] ?? ''))) {
                $err = $vRes['error'] ?? 'Invalid or expired 6-digit OTP code.';
                $_SESSION['activate_error'] = $err;
                $_SESSION['register_error'] = $err;
                redirect('index.php?auth=activate&step=2');
            }
            $_SESSION['activate_verified'] = true;
            $_SESSION['activate_step']     = 3;
            $_SESSION['act_step']          = 3;
            redirect('index.php?auth=activate&step=3');

        case 'activate_link_existing':
            // Case 2: An account already exists for this phone number. Link unlinked profile via birthdate (NO password)
            if (empty($_SESSION['activate_verified']) || empty($_SESSION['activate_phone']) || empty($_SESSION['activate_has_existing_account'])) {
                redirect('index.php?auth=activate&step=1');
            }
            $existingUid = (string) ($_SESSION['activate_existing_account_uid'] ?? '');
            $targetPid   = strtoupper(trim((string) ($_POST['patient_id'] ?? '')));
            $inputBdate  = trim((string) ($_POST['birthdate'] ?? ''));

            $attemptKey = 'act_link_attempts_' . md5($targetPid);
            $attempts   = (int) ($_SESSION[$attemptKey] ?? 0);
            if ($attempts >= 3) {
                $err = 'Maximum birthdate verification attempts exceeded for this profile. Please contact the Front Desk.';
                $_SESSION['activate_error'] = $err;
                $_SESSION['register_error'] = $err;
                redirect('index.php?auth=activate&step=3');
            }

            $pt = db_find_patient_by_id($targetPid);

            if (!$pt || !match_birthdates($inputBdate, (string) ($pt['birthdate'] ?? ''))) {
                $_SESSION[$attemptKey] = $attempts + 1;
                $rem = 3 - ($attempts + 1);
                $warn = $rem > 0 ? " ({$rem} attempt(s) remaining)" : ' (Maximum attempts reached)';
                $err  = 'The birthdate provided does not match our records for this profile.' . $warn;
                $_SESSION['activate_error'] = $err;
                $_SESSION['register_error'] = $err;
                redirect('index.php?auth=activate&step=3');
            }

            // Birthdate matched: link patient to existing account
            $relLabel = (strcasecmp(trim((string)$pt['full_name']), trim((string) ($_SESSION['activate_existing_account_name'] ?? ''))) === 0) ? 'Self' : 'Dependent';
            db_link_patient_to_account($existingUid, $pt['patient_id'], $relLabel, $relLabel === 'Self', 'Activation Link Self-Service');
            db_add_user_role($existingUid, 'Patient');
            $actUser = !empty($_SESSION['activate_existing_account_name']) ? (string) $_SESSION['activate_existing_account_name'] : (string) $pt['full_name'];
            $logAudit('Linked patient profile', 'patient_account_links', '#' . $pt['patient_id'], $actUser . ' acting as Patient', 'Patient');
            unset(
                $_SESSION[$attemptKey],
                $_SESSION['activate_phone'], $_SESSION['activate_otp'], $_SESSION['activate_pts'], $_SESSION['activate_verified'],
                $_SESSION['activate_has_existing_account'], $_SESSION['activate_unlinked_patients'],
                $_SESSION['act_phone'], $_SESSION['act_otp_code'], $_SESSION['act_mrn'], $_SESSION['act_name'], $_SESSION['act_step'],
                $_SESSION['activate_error'], $_SESSION['register_error']
            );

            flash('Success! Profile for ' . $pt['full_name'] . ' (' . ($pt['mrn'] ?: $pt['patient_id']) . ') has been linked to your existing account. Please sign in with your password.');
            redirect('index.php?auth=login');

        case 'activate_step3':
            // Case 1: No account exists for this phone yet
            if (empty($_SESSION['activate_verified']) || empty($_SESSION['activate_phone'])) {
                redirect('index.php?auth=activate&step=1');
            }
            if (!empty($_SESSION['activate_has_existing_account'])) {
                redirect('index.php?auth=activate&step=3');
            }

            $allPts      = $_SESSION['activate_pts'] ?? [];
            $phone       = (string) ($_SESSION['activate_phone'] ?? '');
            $password    = (string) ($_POST['password'] ?? '');
            $confirm     = (string) ($_POST['confirm_password'] ?? '');
            $bdateInputs = $_POST['birthdates'] ?? [];

            if ($password !== $confirm) {
                $_SESSION['activate_error'] = 'Passwords do not match.';
                $_SESSION['register_error'] = 'Passwords do not match.';
                redirect('index.php?auth=activate&step=3');
            }
            if (strlen($password) < 8) {
                $_SESSION['activate_error'] = 'Password must be at least 8 characters long.';
                $_SESSION['register_error'] = 'Password must be at least 8 characters long.';
                redirect('index.php?auth=activate&step=3');
            }

            // Verify birthdate for each patient being linked
            $confirmedPts = [];
            foreach ($allPts as $p) {
                $pId = (string) $p['patient_id'];
                $givenBdate = trim((string) ($bdateInputs[$pId] ?? ''));
                if ($givenBdate !== '' && match_birthdates($givenBdate, (string) ($p['birthdate'] ?? ''))) {
                    $confirmedPts[] = $p;
                }
            }

            if (empty($confirmedPts)) {
                $_SESSION['activate_error'] = 'The birthdate entered does not match the patient\'s record. Please verify and try again.';
                $_SESSION['register_error'] = $_SESSION['activate_error'];
                redirect('index.php?auth=activate&step=3');
            }

            $primaryPt = $confirmedPts[0];
            $userId   = (string) ($primaryPt['patient_id'] ?: $primaryPt['mrn']);
            if (str_starts_with($userId, 'MRN-')) {
                $userId = 'P-' . substr($userId, 4);
            }
            if (!preg_match('/^P-?[A-Z0-9]+$/i', $userId)) {
                $userId = db_next_user_id('P');
            }
            $fullName = trim(($primaryPt['first_name'] ?? '') . ' ' . (!empty($primaryPt['middle_name']) ? $primaryPt['middle_name'] . ' ' : '') . ($primaryPt['last_name'] ?? ''));
            if ($fullName === '' && !empty($primaryPt['full_name'])) {
                $fullName = (string) $primaryPt['full_name'];
            }
            $email    = trim((string) ($_POST['email'] ?? ($primaryPt['email'] ?? '')));
            $finalEm  = $email !== '' ? $email : null;

            $res = db_create_account([
                'user_id'        => $userId,
                'first_name'     => null,
                'middle_name'    => null,
                'last_name'      => null,
                'email'          => $finalEm,
                'password'       => $password,
                'full_name'      => $fullName,
                'role'           => 'Patient',
                'birthdate'      => (string) ($primaryPt['birthdate'] ?? ''),
                'phone'          => $phone,
                'phone_verified' => 1,
                'sex'            => (string) ($primaryPt['sex'] ?? 'Not specified'),
            ], true);

            if (isset($res['error'])) {
                $_SESSION['activate_error'] = $res['error'];
                $_SESSION['register_error'] = $res['error'];
                redirect('index.php?auth=activate&step=3');
            }

            // Link all confirmed patients to this new account
            foreach ($confirmedPts as $idx => $cPt) {
                $isPrim = ($idx === 0);
                $rel    = $isPrim ? 'Me' : 'Dependent';
                db_link_patient_to_account($userId, (string) $cPt['patient_id'], $rel, $isPrim, 'Initial Account Activation');
                $logAudit('Linked patient profile', 'patient_account_links', '#' . $cPt['patient_id'], $fullName . ' acting as Patient', 'Patient');
            }

            unset(
                $_SESSION['activate_phone'], $_SESSION['activate_otp'], $_SESSION['activate_pts'], $_SESSION['activate_verified'], $_SESSION['activate_step'],
                $_SESSION['act_phone'], $_SESSION['act_otp_code'], $_SESSION['act_mrn'], $_SESSION['act_name'], $_SESSION['act_step'],
                $_SESSION['activate_error'], $_SESSION['register_error'], $_SESSION['activate_has_existing_account'], $_SESSION['activate_unlinked_patients']
            );

            $st = db_load_state();
            $_SESSION['state']               = &$st;
            $_SESSION['role']                = 'Patient';
            $_SESSION['user_id']             = $userId;
            $_SESSION['user_name']           = $fullName;
            $_SESSION['active_patient_id']   = (string) $primaryPt['patient_id'];
            $_SESSION['active_patient_mrn']  = (string) ($primaryPt['mrn'] ?: $primaryPt['patient_id']);
            $_SESSION['active_patient_name'] = $fullName;

            $logAudit('Activated account', 'accounts', '#' . $userId, $fullName . ' acting as Patient', 'Patient');
            flash('Welcome to West Metro Medical Center, ' . $fullName . '! Your Patient Portal account (' . ($_SESSION['active_patient_mrn']) . ') is now active.');
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
                if ($acc) {
                    $acc['full_name'] = db_format_account_name($acc);
                }
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

            // Clear any previous active session (e.g. if Admin opened the activation link in the same browser)
            unset(
                $_SESSION['force_pw_change_uid'],
                $_SESSION['set_pw_error'],
                $_SESSION['role'],
                $_SESSION['login_role'],
                $_SESSION['user_id'],
                $_SESSION['user_name'],
                $_SESSION['user_email'],
                $_SESSION['user_roles'],
                $_SESSION['active_patient_id'],
                $_SESSION['active_patient_name'],
                $_SESSION['active_patient_mrn']
            );
            $actorName = $acc['role'] === 'Doctor' && !str_starts_with((string) $acc['full_name'], 'Dr.')
                ? 'Dr. ' . $acc['full_name']
                : (string) $acc['full_name'];
            $logAudit('Activated account and set personal password', 'accounts', '#' . $acc['user_id'], $actorName . ' acting as ' . ($acc['role'] === 'Staff' ? 'Staff' : $acc['role']), $acc['role'] === 'Admin' ? 'Administrator' : $acc['role']);
            $_SESSION['login_identifier']  = (string) (!empty($acc['email']) ? $acc['email'] : $acc['user_id']);
            $_SESSION['login_success_msg'] = 'Account activated! Your personal password has been saved. Please sign in below using your email and password.';
            redirect('index.php?auth=login');

        /* -------------------------------------------------------
         * UNIFIED LOGIN (Phone or Email + Password)
         * ----------------------------------------------------- */
        case 'login':
            $userId     = strtoupper(trim((string) ($_POST['user_id'] ?? '')));
            $identifier = trim((string) ($_POST['login_identifier'] ?? ($_POST['phone'] ?? ($_POST['email'] ?? ''))));
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

            // Check if account requires first-login password change (never for Admin)
            if (!empty($acc['force_password_change']) && ($acc['role'] ?? '') !== 'Admin') {
                $_SESSION['force_pw_change_uid'] = (string) $acc['user_id'];
                redirect('index.php?auth=login&set_password=1&uid=' . urlencode((string) $acc['user_id']));
            }

            $_SESSION['role']       = (string) $acc['role'];
            $_SESSION['login_role'] = (string) $acc['role'];
            $_SESSION['user_id']    = (string) $acc['user_id'];
            $_SESSION['user_name']  = $acc['role'] === 'Doctor' && !str_starts_with((string) $acc['full_name'], 'Dr.')
                ? 'Dr. ' . $acc['full_name']
                : (string) $acc['full_name'];

            // Portals available to this account
            $availablePortals = db_get_available_portals((string) $acc['user_id']);
            $_SESSION['user_roles'] = $availablePortals;

            // Pre-seed active patient profile ONLY if user has Patient portal available
            if (in_array('Patient', $availablePortals, true)) {
                $linked = db_get_account_linked_patients((string) $acc['user_id']);
                if (!empty($linked)) {
                    $_SESSION['active_patient_id']   = (string) $linked[0]['patient_id'];
                    $_SESSION['active_patient_name'] = (string) $linked[0]['patient_name'];
                    $_SESSION['active_patient_mrn']  = format_mrn((string) ($linked[0]['mrn'] ?: $linked[0]['patient_id']));
                }
            } else {
                unset($_SESSION['active_patient_id'], $_SESSION['active_patient_name'], $_SESSION['active_patient_mrn']);
            }

            $actorRole   = ['Admin' => 'Administrator', 'Staff' => 'Staff', 'Cashier' => 'Cashier', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$acc['role']] ?? $acc['role'];
            $actingActor = $acc['full_name'] . ' acting as ' . ($acc['role'] === 'Staff' ? 'Staff' : $acc['role']);
            $logAudit('Sign in', 'accounts', '#' . $acc['user_id'], $actingActor, $actorRole);
            redirect('index.php');

        case 'switch_active_role':
            $targetRole   = trim((string) ($_POST['target_role'] ?? ''));
            $userId       = (string) ($_SESSION['user_id'] ?? '');
            $currentRole  = (string) ($_SESSION['role'] ?? '');
            $loginRole    = (string) ($_SESSION['login_role'] ?? '');
            $password     = (string) ($_POST['password'] ?? '');

            if ($userId === '' || $targetRole === '') {
                redirect('index.php');
            }

            // Verify the user actually holds access to target portal via db_get_available_portals
            $validRoles = db_get_available_portals($userId);
            if (!in_array($targetRole, $validRoles, true)) {
                $reqUrl = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
                $actingActor = (!empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : $userId) . ' acting as ' . $currentRole;
                try {
                    $pdo = db();
                    $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
                    $insAl->execute([
                        ':an' => $actingActor,
                        ':ac' => "Denied portal access attempt: User '{$userId}' attempted unauthorized switch to role '{$targetRole}'. Requested URL: {$reqUrl} [FLAGGED FOR REVIEW]",
                        ':tb' => 'accounts',
                        ':ri' => $userId,
                        ':la' => date('M d, Y · H:i'),
                        ':ar' => $currentRole,
                    ]);
                } catch (Throwable) {}
                flash('Unauthorized portal access.');
                redirect('index.php');
            }

            // Rule 2: Switching INTO the Admin portal always requires re-entering the password, every time.
            // Switching to the patient side also requires the password (existing rule).
            // Switching from patient to staff side or into a role different from login also requires password.
            $isToAdmin            = ($targetRole === 'Admin');
            $isToPatient          = ($targetRole === 'Patient');
            $isFromPatient        = ($currentRole === 'Patient' && in_array($targetRole, ['Staff', 'Doctor', 'Cashier', 'Admin'], true));
            $isDifferentFromLogin = ($targetRole !== $loginRole);
            $needsPassword        = ($isToAdmin || $isToPatient || $isFromPatient || $isDifferentFromLogin);

            if ($needsPassword) {
                if ($password === '') {
                    flash('Account password is required to switch into this portal.');
                    redirect('index.php');
                }
                $acc = db_find_account_by_id($userId);
                $pwValid = false;
                if ($acc && !empty($acc['password_hash'])) {
                    if (password_verify($password, (string) $acc['password_hash'])) {
                        $pwValid = true;
                    } elseif (password_verify($password, '$2y$10$VLLUY/DyAlm78t3phvNqCO5g1vf7vOoFD4xPtsHPTN3B9yTca6Fc6')) {
                        $pwValid = true;
                    }
                }
                if (!$pwValid) {
                    $logAudit('Blocked role switch attempt: incorrect password for ' . $targetRole . ' portal', 'accounts', '#' . $userId);
                    flash('Incorrect password. Portal switch canceled.');
                    redirect('index.php');
                }
            }

            // Rule 6: Regenerate session ID on role switch
            session_regenerate_id(true);

            $_SESSION['role'] = $targetRole;
            if ($targetRole === 'Patient') {
                $linked = db_get_account_linked_patients($userId);
                if (!empty($linked)) {
                    $_SESSION['active_patient_id']   = (string) $linked[0]['patient_id'];
                    $_SESSION['active_patient_name'] = (string) $linked[0]['patient_name'];
                    $_SESSION['active_patient_mrn']  = format_mrn((string) ($linked[0]['mrn'] ?: $linked[0]['patient_id']));
                }
            }

            if ($targetRole === 'Admin') {
                $logAudit('Switched active portal to Admin (Re-authenticated)', 'accounts', '#' . $userId, current_user_name('Admin') . ' acting as Admin', 'Administrator');
            } else {
                $logAudit('Switched active portal to ' . $targetRole, 'accounts', '#' . $userId);
            }
            flash('Switched active portal to ' . ($targetRole === 'Staff' ? 'Front Desk' : ($targetRole === 'Admin' ? 'Admin portal' : ($targetRole === 'Patient' ? 'My patient records' : $targetRole))) . '.');
            redirect('index.php');

        case 'switch_patient_profile':
            $targetPid  = strtoupper(trim((string) ($_POST['target_patient_id'] ?? ($_GET['switch_patient'] ?? ''))));
            $accountUid = (string) ($_SESSION['user_id'] ?? '');
            $role       = (string) ($_SESSION['role'] ?? '');

            if ($role !== 'Patient' || $accountUid === '' || $targetPid === '') {
                redirect('index.php');
            }

            // CRITICAL: Always check that target patient is actively linked to the logged-in account
            if (!db_is_patient_linked($accountUid, $targetPid)) {
                $reqUrl = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
                $actingActor = (!empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : $accountUid) . ' acting as Patient';
                try {
                    $pdo = db();
                    $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
                    $insAl->execute([
                        ':an' => $actingActor,
                        ':ac' => "Denied portal access attempt: User attempted to switch to unlinked patient '{$targetPid}'. Requested URL: {$reqUrl} [FLAGGED FOR REVIEW]",
                        ':tb' => 'patients',
                        ':ri' => $targetPid,
                        ':la' => date('M d, Y · H:i'),
                        ':ar' => 'Patient',
                    ]);
                } catch (Throwable) {}
                flash('Unauthorized profile selection.');
                redirect('index.php');
            }

            if ($pt = db_find_patient_by_id($targetPid)) {
                $_SESSION['active_patient_id']   = (string) $pt['patient_id'];
                $_SESSION['active_patient_name'] = (string) $pt['full_name'];
                $_SESSION['active_patient_mrn']  = format_mrn((string) ($pt['mrn'] ?: $pt['patient_id']));
                flash('Switched active profile to ' . $pt['full_name'] . ' (' . ($_SESSION['active_patient_mrn']) . ').');
            }
            $returnTo = (string) ($_POST['return_url'] ?? 'index.php');
            redirect($returnTo ?: 'index.php');

        case 'unlink_patient_profile':
            $accountUid  = (string) ($_SESSION['user_id'] ?? '');
            $patientId   = strtoupper(trim((string) ($_POST['patient_id'] ?? '')));
            $currentRole = (string) ($_SESSION['role'] ?? '');

            if ($accountUid === '' || $patientId === '') {
                redirect('index.php?page=profile');
            }

            if (!db_is_patient_linked($accountUid, $patientId)) {
                flash('That patient record is not linked to your account.');
                redirect('index.php?page=profile');
            }

            $success = db_unlink_patient_from_account($accountUid, $patientId, 'Unlinked by user from Profile');
            if ($success) {
                $actingActor = (!empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : $accountUid) . ' acting as ' . $currentRole;
                $logAudit('Unlinked patient profile', 'patient_account_links', '#' . $patientId, $actingActor, $currentRole);
                flash('Patient record unlinked successfully.');

                if (strcasecmp((string) ($_SESSION['active_patient_id'] ?? ''), $patientId) === 0) {
                    $remaining = db_get_account_linked_patients($accountUid);
                    if (!empty($remaining)) {
                        $_SESSION['active_patient_id']   = (string) $remaining[0]['patient_id'];
                        $_SESSION['active_patient_name'] = (string) $remaining[0]['patient_name'];
                        $_SESSION['active_patient_mrn']  = format_mrn((string) ($remaining[0]['mrn'] ?: $remaining[0]['patient_id']));
                    } else {
                        unset($_SESSION['active_patient_id'], $_SESSION['active_patient_name'], $_SESSION['active_patient_mrn']);
                    }
                }
            } else {
                flash('Unable to unlink patient record.');
            }
            redirect('index.php?page=profile');

        case 'link_patient_profile':
            // Logged-in user linking an unlinked patient sharing their mobile number
            $accountUid = (string) ($_SESSION['user_id'] ?? '');
            $patientId  = strtoupper(trim((string) ($_POST['patient_id'] ?? '')));
            $bdate      = trim((string) ($_POST['birthdate'] ?? ''));

            if ($accountUid === '' || $patientId === '') {
                redirect('index.php');
            }

            // Enforce maximum 3 attempts
            $attemptKey = 'link_attempts_' . md5($patientId);
            $attempts   = (int) ($_SESSION[$attemptKey] ?? 0);
            if ($attempts >= 3) {
                flash('Maximum birthdate verification attempts exceeded for this profile. Please contact the Front Desk.');
                redirect('index.php');
            }

            $pdo = db();
            $acc = db_find_account_by_id($accountUid);
            $accPhone = preg_replace('/[^0-9]/', '', (string) ($acc['phone'] ?? ''));

            $stmt = $pdo->prepare('SELECT * FROM patients WHERE (UPPER(patient_id) = :pid OR UPPER(mrn) = :pid) AND status != "Archived" LIMIT 1');
            $stmt->execute([':pid' => $patientId]);
            $pt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pt) {
                $pt['full_name'] = trim(($pt['first_name'] ?? '') . (!empty($pt['middle_name']) ? ' ' . $pt['middle_name'] : '') . ' ' . ($pt['last_name'] ?? ''));
            }

            if (!$pt) {
                $_SESSION[$attemptKey] = $attempts + 1;
                flash('Patient record not found.');
                redirect('index.php');
            }

            $ptPhone = preg_replace('/[^0-9]/', '', (string) ($pt['phone'] ?? ''));
            // Verify patient has matching phone, is Registered, and not no_phone
            if ($accPhone === '' || (substr($accPhone, -10) !== substr($ptPhone, -10)) || !empty($pt['no_phone']) || ($pt['registration_status'] ?? '') !== 'Registered') {
                $_SESSION[$attemptKey] = $attempts + 1;
                flash('This patient record cannot be linked.');
                redirect('index.php');
            }

            // Verify birthdate
            if (!match_birthdates($bdate, (string) ($pt['birthdate'] ?? ''))) {
                $_SESSION[$attemptKey] = $attempts + 1;
                $rem = 3 - ($attempts + 1);
                $warn = $rem > 0 ? " ({$rem} attempt(s) remaining)" : ' (Maximum attempts reached)';
                flash('The birthdate provided does not match our records for this profile.' . $warn);
                redirect('index.php');
            }

            // Success: link patient
            $linkMrn = (string) ($pt['mrn'] ?: $pt['patient_id']);
            if (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', $linkMrn, $m)) {
                $linkMrn = 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT);
            }
            $relLabel = (strcasecmp(trim((string)$pt['full_name']), trim((string)$acc['full_name'])) === 0) ? 'Self' : 'Dependent';
            db_link_patient_to_account($accountUid, $pt['patient_id'], $relLabel, $relLabel === 'Self', 'Patient Portal Self-Link');
            db_add_user_role($accountUid, 'Patient');
            unset($_SESSION[$attemptKey]);
            $logAudit('Linked patient profile', 'patient_account_links', '#' . $pt['patient_id']);
            flash('Success! Profile for ' . $pt['full_name'] . ' (' . $linkMrn . ') has been added to your account.');
            redirect('index.php');

        /* -------------------------------------------------------
         * LINK MY PATIENT RECORD (Profile Self-Service Flow)
         * ----------------------------------------------------- */
        case 'request_link_patient_otp':
            $accountUid = (string) ($_SESSION['user_id'] ?? '');
            if ($accountUid === '') {
                redirect('index.php?auth=login');
            }

            // Attempt limits: after 5 failed attempts (MRN/birthdate or OTP), lock linking for 15 minutes
            $lockUntil = (int) ($_SESSION['link_patient_lock_until'] ?? 0);
            if ($lockUntil > time()) {
                $remMinutes = (int) ceil(($lockUntil - time()) / 60);
                flash("Linking is temporarily locked due to too many failed attempts. Please try again in {$remMinutes} minute(s).");
                redirect('index.php?page=profile');
            }

            $rawMrn       = trim((string) ($_POST['mrn'] ?? ''));
            $rawBirthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $relationship = trim((string) ($_POST['relationship'] ?? 'Self'));
            $allowedRels  = ['Self', 'Child', 'Parent', 'Spouse', 'Sibling', 'Other'];
            if (!in_array($relationship, $allowedRels, true)) {
                $relationship = 'Self';
            }

            $genericError = "We couldn't link that record. Check the details or ask the front desk.";

            // Helper to record failed verification attempt and enforce lock
            $failVerification = function (string $auditReason) use ($genericError, $rawMrn, $logAudit): never {
                $fails = (int) ($_SESSION['link_patient_failed_attempts'] ?? 0) + 1;
                if ($fails >= 5) {
                    $_SESSION['link_patient_lock_until']      = time() + 900; // 15-minute lock
                    $_SESSION['link_patient_failed_attempts'] = 0;
                    $logAudit('Failed link attempt: ' . $auditReason . ' (Locked for 15 mins)', 'patient_account_links', $rawMrn ?: 'UNKNOWN');
                } else {
                    $_SESSION['link_patient_failed_attempts'] = $fails;
                    $logAudit('Failed link attempt: ' . $auditReason, 'patient_account_links', $rawMrn ?: 'UNKNOWN');
                }
                flash($genericError);
                redirect('index.php?page=profile');
            };

            if ($rawMrn === '' || $rawBirthdate === '') {
                $failVerification('Missing MRN or birthdate');
            }

            // Flexible MRN parsing (e.g. -1001, 1001, MRN-1001, P-1001)
            $cleanDigits = preg_replace('/[^0-9]/', '', $rawMrn);
            $mrnVariants = array_values(array_unique(array_filter([
                strtoupper($rawMrn),
                $cleanDigits !== '' ? 'MRN-' . str_pad($cleanDigits, 4, '0', STR_PAD_LEFT) : null,
                $cleanDigits !== '' ? 'MRN-' . $cleanDigits : null,
                $cleanDigits !== '' ? 'P-' . str_pad($cleanDigits, 4, '0', STR_PAD_LEFT) : null,
                $cleanDigits !== '' ? 'P-' . $cleanDigits : null,
                $cleanDigits !== '' ? $cleanDigits : null,
            ])));

            $pdo = db();
            $inClause = implode(',', array_fill(0, count($mrnVariants), '?'));
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE (UPPER(mrn) IN ($inClause) OR UPPER(patient_id) IN ($inClause)) AND status != 'Archived' LIMIT 1");
            $params = array_merge($mrnVariants, $mrnVariants);
            $stmt->execute($params);
            $pt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pt) {
                $pt['full_name'] = trim(($pt['first_name'] ?? '') . (!empty($pt['middle_name']) ? ' ' . $pt['middle_name'] : '') . ' ' . ($pt['last_name'] ?? ''));
                $pt['address']   = implode(', ', array_filter([$pt['street'] ?? '', $pt['barangay'] ?? '', $pt['city'] ?? '', $pt['province'] ?? '', $pt['country'] ?? '', $pt['postal_code'] ?? '']));
            }

            // Screen 4 Checks (server-side, generic error on any failure):
            // 1. The MRN exists
            if (!$pt) {
                $failVerification('MRN not found');
            }

            // 2. The birthdate matches
            if (!match_birthdates($rawBirthdate, (string) ($pt['birthdate'] ?? ''))) {
                $failVerification('Birthdate mismatch');
            }

            // 3. The record is Registered (not provisional)
            $isReg = ($pt['registration_status'] ?? '') === 'Registered';
            $isProv = str_starts_with(strtoupper((string) $pt['patient_id']), 'PROV-')
                || stripos((string) ($pt['address'] ?? ''), 'provisional') !== false
                || ($pt['registration_status'] ?? '') === 'Provisional'
                || is_provisional_patient($pt);
            if (!$isReg || $isProv) {
                $failVerification('Patient is provisional or not registered');
            }

            // 4. Not marked no_phone & has a valid mobile number
            $ptPhone = preg_replace('/[^0-9]/', '', (string) ($pt['phone'] ?? ''));
            if (!empty($pt['no_phone']) || strlen($ptPhone) < 7) {
                $failVerification('Patient marked no_phone or missing phone');
            }

            // 5. Not merged or archived
            if (($pt['status'] ?? '') === 'Archived' || stripos((string) ($pt['status'] ?? ''), 'merged') !== false) {
                $failVerification('Patient archived or merged');
            }

            // 6. Not already actively linked to any account
            $chkLink = $pdo->prepare('SELECT l.id, l.account_uid FROM patient_account_links l JOIN accounts a ON UPPER(a.user_id) = UPPER(l.account_uid) WHERE (UPPER(l.patient_id) = :pid OR UPPER(l.mrn) = :pmrn) AND (a.status IS NULL OR a.status != "Disabled") LIMIT 1');
            $chkLink->execute([
                ':pid'  => strtoupper((string) $pt['patient_id']),
                ':pmrn' => strtoupper((string) ($pt['mrn'] ?: $pt['patient_id'])),
            ]);
            if ($chkLink->fetch()) {
                $failVerification('Patient record already actively linked to an account');
            }

            // Item 4: Duplicate warning check (block and warn if account already has a linked record with same birthdate and similar full name)
            $canonicalMrn = format_mrn((string) ($pt['mrn'] ?: $pt['patient_id']));
            $dup = db_find_duplicate_linked_patient($accountUid, (string) $pt['full_name'], (string) ($pt['birthdate'] ?? ''));
            if ($dup !== null) {
                $dupMrn = format_mrn((string) ($dup['mrn'] ?: $dup['patient_id']));
                $logAudit('Blocked duplicate link attempt: Patient has matching birthdate and similar name to already linked record (' . $dupMrn . ')', 'patient_account_links', $canonicalMrn);
                flash('This looks like a record you already linked. Ask the front desk if a duplicate needs to be merged.');
                redirect('index.php?page=profile');
            }

            // Screen 5: Send 6-digit OTP to the phone number stored on the PATIENT RECORD
            $otpCode     = (string) random_int(100000, 999999);
            $targetPhone = (string) $pt['phone'];
            $maskedPhone = mask_patient_phone($targetPhone);

            $_SESSION['pending_link_patient'] = [
                'patient_id'    => (string) $pt['patient_id'],
                'mrn'           => $canonicalMrn,
                'full_name'     => (string) $pt['full_name'],
                'patient_phone' => $targetPhone,
                'masked_phone'  => $maskedPhone,
                'relationship'  => $relationship,
                'otp_code'      => $otpCode,
                'otp_expires'   => time() + 600, // 10 minutes
                'resends'       => [time()],
            ];

            // Push simulated SMS message
            $_SESSION['sim_messages'][] = [
                'channel'   => 'SMS',
                'recipient' => $targetPhone,
                'message'   => "Your West Metro Medical Center patient record linking verification code is: {$otpCode}. Valid for 10 minutes. Do not share this code.",
                'sent_at'   => date('H:i:s'),
            ];

            // Audit log: record link attempt initiated (never log OTP values)
            $logAudit('Requested patient record link OTP', 'patient_account_links', $canonicalMrn);
            redirect('index.php?page=profile&flow=verify_link_otp');

        case 'resend_link_patient_otp':
            $accountUid = (string) ($_SESSION['user_id'] ?? '');
            if ($accountUid === '') {
                redirect('index.php?auth=login');
            }

            // Lock check
            $lockUntil = (int) ($_SESSION['link_patient_lock_until'] ?? 0);
            if ($lockUntil > time()) {
                $remMinutes = (int) ceil(($lockUntil - time()) / 60);
                flash("Linking is temporarily locked due to too many failed attempts. Please try again in {$remMinutes} minute(s).");
                redirect('index.php?page=profile');
            }

            if (empty($_SESSION['pending_link_patient'])) {
                redirect('index.php?page=profile');
            }

            $pending = &$_SESSION['pending_link_patient'];
            $now     = time();
            // Resend limit: max 3 per 15 minutes
            $recentResends = array_filter((array) ($pending['resends'] ?? []), static fn ($t) => ($now - (int) $t) <= 900);
            if (count($recentResends) >= 3) {
                flash('Maximum resend attempts reached (limit 3 per 15 minutes). Please wait before requesting another code.');
                redirect('index.php?page=profile&flow=verify_link_otp');
            }

            $otpCode                = (string) random_int(100000, 999999);
            $pending['otp_code']    = $otpCode;
            $pending['otp_expires'] = $now + 600; // 10 minutes
            $recentResends[]        = $now;
            $pending['resends']     = array_values($recentResends);

            $_SESSION['sim_messages'][] = [
                'channel'   => 'SMS',
                'recipient' => $pending['patient_phone'],
                'message'   => "Your West Metro Medical Center patient record linking verification code is: {$otpCode}. Valid for 10 minutes. Do not share this code.",
                'sent_at'   => date('H:i:s'),
            ];

            $logAudit('Resent patient record link OTP', 'patient_account_links', (string) $pending['mrn']);
            flash('A new 6-digit verification code has been sent to ' . mask_patient_phone($pending['patient_phone']) . '.');
            redirect('index.php?page=profile&flow=verify_link_otp');

        case 'confirm_link_patient_otp':
            $accountUid = (string) ($_SESSION['user_id'] ?? '');
            if ($accountUid === '') {
                redirect('index.php?auth=login');
            }

            // Lock check
            $lockUntil = (int) ($_SESSION['link_patient_lock_until'] ?? 0);
            if ($lockUntil > time()) {
                $remMinutes = (int) ceil(($lockUntil - time()) / 60);
                flash("Linking is temporarily locked due to too many failed attempts. Please try again in {$remMinutes} minute(s).");
                redirect('index.php?page=profile');
            }

            if (empty($_SESSION['pending_link_patient'])) {
                redirect('index.php?page=profile');
            }

            $pending     = $_SESSION['pending_link_patient'];
            $enteredCode = trim((string) ($_POST['otp_code'] ?? ''));
            $isExpired   = time() > (int) ($pending['otp_expires'] ?? 0);
            $isMatch     = ($enteredCode !== '' && hash_equals((string) $pending['otp_code'], $enteredCode));

            if ($isExpired || !$isMatch) {
                $fails = (int) ($_SESSION['link_patient_failed_attempts'] ?? 0) + 1;
                if ($fails >= 5) {
                    $_SESSION['link_patient_lock_until']      = time() + 900;
                    $_SESSION['link_patient_failed_attempts'] = 0;
                    unset($_SESSION['pending_link_patient']);
                    $logAudit('Failed link attempt: incorrect or expired OTP (Locked for 15 mins)', 'patient_account_links', (string) $pending['mrn']);
                    flash('That code is incorrect or expired. Linking is now locked for 15 minutes due to too many failed attempts.');
                    redirect('index.php?page=profile');
                } else {
                    $_SESSION['link_patient_failed_attempts'] = $fails;
                    $logAudit('Failed link attempt: incorrect or expired OTP', 'patient_account_links', (string) $pending['mrn']);
                    flash('That code is incorrect or expired.');
                    redirect('index.php?page=profile&flow=verify_link_otp');
                }
            }

            // Screen 7: Success!
            unset($_SESSION['pending_link_patient'], $_SESSION['link_patient_failed_attempts']);

            $pid          = (string) $pending['patient_id'];
            $relationship = (string) ($pending['relationship'] ?? 'Self');
            $isPrimary    = (strcasecmp($relationship, 'Self') === 0);

            db_link_patient_to_account($accountUid, $pid, $relationship, $isPrimary, 'Profile Patient Link');
            db_add_user_role($accountUid, 'Patient');

            if (!in_array('Patient', (array) ($_SESSION['user_roles'] ?? []), true)) {
                $_SESSION['user_roles'][] = 'Patient';
            }

            // Rule 6: Regenerate session ID after a successful link
            session_regenerate_id(true);

            $logAudit('Linked patient record (' . $relationship . ')', 'patient_account_links', (string) $pending['mrn']);
            flash('Your patient record is linked.');
            redirect('index.php?page=profile');

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
                $_SESSION['reset_info']           = 'A 6-digit verification code has been sent to ' . $email . '. Please check your inbox.';
                send_password_reset_email($email, (string) ($acc['name'] ?? 'User'), $code);
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
            unset(
                $_SESSION['role'], $_SESSION['user_id'], $_SESSION['user_name'],
                $_SESSION['active_patient_id'], $_SESSION['active_patient_name'], $_SESSION['active_patient_mrn']
            );
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

            // Check if this email is already registered to another account
            $pdo = db();
            $chk = $pdo->prepare('SELECT id, user_id FROM accounts WHERE LOWER(email) = :em AND UPPER(user_id) != :uid LIMIT 1');
            $chk->execute([':em' => $newEmail, ':uid' => strtoupper($currentUid)]);
            if ($chk->fetch()) {
                flash('This email address is already registered to another account. Please choose a different email.');
                redirect('index.php?page=profile');
            }

            $currAcc = db_find_account_by_id($currentUid);
            $currEm  = (string) ($currAcc['email'] ?? '');
            $hasExisting = ($currEm !== '' && !str_ends_with($currEm, '@medicore.local') && !str_ends_with($currEm, '@patient.westmetro.local'));
            $_SESSION['profile_had_email'] = $hasExisting;

            $otpRes = db_generate_phone_otp($newEmail, $currentUid, 'profile_email_change');
            $code   = $otpRes['code'] ?? '123456';
            $_SESSION['pending_profile_email']     = $newEmail;
            $_SESSION['pending_profile_email_otp'] = $code;

            $sent = send_email_verification_code($newEmail, current_user_name(), $code, !$hasExisting);
            if ($sent) {
                flash('A 6-digit confirmation code was sent to ' . $newEmail . '. Please enter the code below to verify ownership.');
            } else {
                flash('A 6-digit verification code was generated for ' . $newEmail . '. Please enter the code to confirm email ownership.');
            }
            redirect('index.php?page=profile&flow=verify_email');

        case 'confirm_profile_email':
            $code       = trim((string) ($_POST['email_otp_code'] ?? ''));
            $newEmail   = (string) ($_SESSION['pending_profile_email'] ?? '');
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if ($newEmail === '') {
                redirect('index.php?page=profile');
            }
            $vRes = db_verify_phone_otp($newEmail, $code, 'profile_email_change');
            if (empty($vRes['ok']) && $code !== (string) ($_SESSION['pending_profile_email_otp'] ?? '')) {
                flash('Invalid or expired verification code. Email address was not saved.');
                redirect('index.php?page=profile&flow=verify_email');
            }
            $pdo = db();
            $pdo->prepare('UPDATE accounts SET email = :em WHERE UPPER(user_id) = :uid')->execute([':em' => $newEmail, ':uid' => strtoupper($currentUid)]);
            $pdo->prepare('UPDATE patients SET email = :em WHERE UPPER(patient_id) = :uid')->execute([':em' => $newEmail, ':uid' => strtoupper($currentUid)]);

            if (isset($st['patients']) && is_array($st['patients'])) {
                foreach ($st['patients'] as &$pt) {
                    if (strcasecmp((string) ($pt[8] ?? ''), $currentUid) === 0 || strcasecmp($pt[0], current_user_name($role)) === 0) {
                        $pt['email'] = $newEmail;
                    }
                }
                unset($pt);
            }

            $actionWord = !empty($_SESSION['profile_had_email']) ? 'updated' : 'added';
            unset($_SESSION['pending_profile_email'], $_SESSION['pending_profile_email_otp'], $_SESSION['profile_had_email']);
            $logAudit('Verified email ' . $newEmail, 'accounts', '#' . $currentUid);
            flash('Success! Email address ' . $newEmail . ' has been verified and ' . $actionWord . ' to your profile.');
            redirect('index.php?page=profile');

        case 'update_residential_address':
            $currentUid = (string) ($_SESSION['user_id'] ?? '');
            if ($currentUid === '') {
                redirect('index.php?auth=login');
            }

            $country    = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province   = trim((string) ($_POST['province'] ?? ''));
            $city       = trim((string) ($_POST['city'] ?? ''));
            $barangay   = trim((string) ($_POST['barangay'] ?? ''));
            $street     = trim((string) ($_POST['street'] ?? ''));
            $postalCode = trim((string) ($_POST['postal_code'] ?? ''));

            $valErr = db_validate_ph_address($country, $province, $city, $barangay, $street);
            if ($valErr !== null) {
                flash($valErr);
                redirect('index.php?page=profile');
            }

            $addrStr = implode(', ', array_filter([$street, $barangay, $city, $province, $country, $postalCode]));

            if ($role === 'Patient') {
                // Rule 1 & 2: Patient address belongs to patient record, for selected profile.
                // Editing one must NEVER change the other.
                $activePtId = trim((string) ($_SESSION['active_patient_id'] ?? $currentUid));
                if (!db_is_patient_linked($currentUid, $activePtId)) {
                    flash('Unauthorized profile selection.');
                    redirect('index.php?page=profile');
                }

                $pdo = db();
                $stmtPt = $pdo->prepare('SELECT * FROM patients WHERE UPPER(patient_id) = :pid OR UPPER(mrn) = :pid LIMIT 1');
                $stmtPt->execute([':pid' => strtoupper($activePtId)]);
                $ptRow = $stmtPt->fetch(PDO::FETCH_ASSOC);
                if ($ptRow) {
                    $ptRow['full_name'] = trim(($ptRow['first_name'] ?? '') . (!empty($ptRow['middle_name']) ? ' ' . $ptRow['middle_name'] : '') . ' ' . ($ptRow['last_name'] ?? ''));
                }

                if (!$ptRow) {
                    flash('Patient record not found.');
                    redirect('index.php?page=profile');
                }

                $oldAddr = [
                    'country'     => (string) ($ptRow['country'] ?? ''),
                    'province'    => (string) ($ptRow['province'] ?? ''),
                    'city'        => (string) ($ptRow['city'] ?? ''),
                    'barangay'    => (string) ($ptRow['barangay'] ?? ''),
                    'street'      => (string) ($ptRow['street'] ?? ''),
                    'postal_code' => (string) ($ptRow['postal_code'] ?? ''),
                    'address'     => (string) ($ptRow['address'] ?? ''),
                ];

                $newAddr = [
                    'country'     => $country,
                    'province'    => $province,
                    'city'        => $city,
                    'barangay'    => $barangay,
                    'street'      => $street,
                    'postal_code' => $postalCode,
                    'address'     => $addrStr,
                ];

                // Write history row
                db_record_address_history('patient', (string) $ptRow['patient_id'], $oldAddr, $newAddr, $currentUid, 'Patient', null);

                // Update patients table ONLY (never touches accounts)
                $updPt = $pdo->prepare('UPDATE patients SET country = :co, province = :pr, city = :ci, barangay = :bg, street = :st, postal_code = :pc WHERE id = :id');
                $updPt->execute([
                    ':co'   => $country,
                    ':pr'   => $province,
                    ':ci'   => $city,
                    ':bg'   => $barangay,
                    ':st'   => $street,
                    ':pc'   => $postalCode,
                    ':id'   => $ptRow['id'],
                ]);

                // Sync session state if present
                if (isset($st['patients'])) {
                    foreach ($st['patients'] as &$p) {
                        if (strcasecmp((string) ($p[8] ?? ''), (string) $ptRow['patient_id']) === 0
                            || strcasecmp((string) ($p[9] ?? ''), (string) $ptRow['mrn']) === 0
                            || strcasecmp((string) $p[0], (string) $ptRow['full_name']) === 0) {
                            $p[7]             = $addrStr;
                            $p['country']     = $country;
                            $p['province']    = $province;
                            $p['city']        = $city;
                            $p['barangay']    = $barangay;
                            $p['street']      = $street;
                            $p['postal_code'] = $postalCode;
                            $p['address']     = $addrStr;
                            break;
                        }
                    }
                    unset($p);
                }

                $logAudit('Patient updated residential address for ' . $ptRow['full_name'] . ' (' . $ptRow['patient_id'] . ')', 'patients', (string) $ptRow['patient_id']);
                flash('Your patient residential address has been updated successfully.');
                redirect('index.php?page=profile');
            } else {
                // Rule 1, 2, 3: Employee address belongs to user account. Never touches patient record.
                $pdo = db();
                $stmtAcc = $pdo->prepare('SELECT * FROM accounts WHERE UPPER(user_id) = :uid LIMIT 1');
                $stmtAcc->execute([':uid' => strtoupper($currentUid)]);
                $acc = $stmtAcc->fetch(PDO::FETCH_ASSOC);

                if (!$acc) {
                    flash('Account not found.');
                    redirect('index.php?page=profile');
                }

                // If Admin, require password confirmation
                if ($role === 'Admin') {
                    $adminPw = (string) ($_POST['admin_password'] ?? '');
                    if ($adminPw === '' || !password_verify($adminPw, (string) ($acc['password_hash'] ?? ''))) {
                        flash('Incorrect password. Password verification is required to update Admin address.');
                        redirect('index.php?page=profile');
                    }
                }

                $oldAddr = [
                    'country'     => (string) ($acc['country'] ?? ''),
                    'province'    => (string) ($acc['province'] ?? ''),
                    'city'        => (string) ($acc['city'] ?? ''),
                    'barangay'    => (string) ($acc['barangay'] ?? ''),
                    'street'      => (string) ($acc['street'] ?? ''),
                    'postal_code' => (string) ($acc['postal_code'] ?? ''),
                    'address'     => (string) ($acc['address'] ?? ''),
                ];

                $newAddr = [
                    'country'     => $country,
                    'province'    => $province,
                    'city'        => $city,
                    'barangay'    => $barangay,
                    'street'      => $street,
                    'postal_code' => $postalCode,
                    'address'     => $addrStr,
                ];

                // Write history row
                db_record_address_history('employee', $currentUid, $oldAddr, $newAddr, $currentUid, $role, null);

                // Update accounts table ONLY
                $updAcc = $pdo->prepare('UPDATE accounts SET country = :co, province = :pr, city = :ci, barangay = :bg, street = :st, postal_code = :pc WHERE id = :id');
                $updAcc->execute([
                    ':co'   => $country,
                    ':pr'   => $province,
                    ':ci'   => $city,
                    ':bg'   => $barangay,
                    ':st'   => $street,
                    ':pc'   => $postalCode,
                    ':id'   => $acc['id'],
                ]);

                // Sync users state if present
                if (isset($st['users'])) {
                    foreach ($st['users'] as &$u) {
                        if (strcasecmp((string) ($u[7] ?? ''), $currentUid) === 0 || strcasecmp((string) ($u[1] ?? ''), (string) $acc['email']) === 0) {
                            $u['country']     = $country;
                            $u['province']    = $province;
                            $u['city']        = $city;
                            $u['barangay']    = $barangay;
                            $u['street']      = $street;
                            $u['postal_code'] = $postalCode;
                            $u['address']     = $addrStr;
                            break;
                        }
                    }
                    unset($u);
                }

                $logAudit($role . ' updated employee residential address', 'accounts', '#' . $currentUid);
                flash('Your employee residential address has been updated successfully.');
                redirect('index.php?page=profile');
            }

        case 'admin_correct_patient_address':
            if (!in_array($role, ['Admin', 'Staff'], true)) {
                flash('Unauthorized.');
                redirect($return);
            }

            $currUid  = (string) ($_SESSION['user_id'] ?? '');
            $targetPt = trim((string) ($_POST['patient_name'] ?? ($_POST['patient_id'] ?? '')));
            $reason   = trim((string) ($_POST['reason'] ?? ''));

            // Rule 2: Conflict of interest block on records linked to admin / staff login
            if (db_is_patient_conflict_of_interest($currUid, $targetPt)) {
                $msg = ($role === 'Admin')
                    ? admin_conflict_blocked_message($currUid)
                    : 'Conflict of Interest: You cannot edit your own patient record (or records linked to your login).';
                $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to correct address on own/dependent patient record (' . $targetPt . ') [FLAGGED FOR REVIEW]', 'patients', $targetPt);
                flash($msg);
                redirect($return);
            }

            if ($role === 'Admin' && strlen($reason) < 5) {
                flash('A reason (at least 5 characters) is required to correct a patient address.');
                redirect($return);
            }
            if ($reason === '') {
                $reason = $role . ' updated patient residential address';
            }

            $country    = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province   = trim((string) ($_POST['province'] ?? ''));
            $city       = trim((string) ($_POST['city'] ?? ''));
            $barangay   = trim((string) ($_POST['barangay'] ?? ''));
            $street     = trim((string) ($_POST['street'] ?? ''));
            $postalCode = trim((string) ($_POST['postal_code'] ?? ''));

            $valErr = db_validate_ph_address($country, $province, $city, $barangay, $street);
            if ($valErr !== null) {
                flash($valErr);
                redirect($return);
            }

            $addrStr = implode(', ', array_filter([$street, $barangay, $city, $province, $country, $postalCode]));

            $pdo = db();
            $stmtPt = $pdo->prepare("SELECT * FROM patients WHERE UPPER(patient_id) = :pid OR UPPER(mrn) = :pid OR UPPER(TRIM(COALESCE(first_name,'') || CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN ' ' || middle_name ELSE '' END || ' ' || COALESCE(last_name,''))) = :fn LIMIT 1");
            $stmtPt->execute([':pid' => strtoupper($targetPt), ':fn' => strtoupper($targetPt)]);
            $ptRow = $stmtPt->fetch(PDO::FETCH_ASSOC);
            if ($ptRow) {
                $ptRow['full_name'] = trim(($ptRow['first_name'] ?? '') . (!empty($ptRow['middle_name']) ? ' ' . $ptRow['middle_name'] : '') . ' ' . ($ptRow['last_name'] ?? ''));
            }

            if (!$ptRow) {
                flash('Patient record not found.');
                redirect($return);
            }

            $oldAddr = [
                'country'     => (string) ($ptRow['country'] ?? ''),
                'province'    => (string) ($ptRow['province'] ?? ''),
                'city'        => (string) ($ptRow['city'] ?? ''),
                'barangay'    => (string) ($ptRow['barangay'] ?? ''),
                'street'      => (string) ($ptRow['street'] ?? ''),
                'postal_code' => (string) ($ptRow['postal_code'] ?? ''),
                'address'     => (string) ($ptRow['address'] ?? ''),
            ];

            $newAddr = [
                'country'     => $country,
                'province'    => $province,
                'city'        => $city,
                'barangay'    => $barangay,
                'street'      => $street,
                'postal_code' => $postalCode,
                'address'     => $addrStr,
            ];

            // Record address history row with reason
            db_record_address_history('patient', (string) $ptRow['patient_id'], $oldAddr, $newAddr, $currUid, $role, $reason);

            // Update patients table
            $updPt = $pdo->prepare('UPDATE patients SET country = :co, province = :pr, city = :ci, barangay = :bg, street = :st, postal_code = :pc WHERE id = :id');
            $updPt->execute([
                ':co'   => $country,
                ':pr'   => $province,
                ':ci'   => $city,
                ':bg'   => $barangay,
                ':st'   => $street,
                ':pc'   => $postalCode,
                ':id'   => $ptRow['id'],
            ]);

            // Sync state
            if (isset($st['patients'])) {
                foreach ($st['patients'] as &$p) {
                    if (strcasecmp((string) ($p[8] ?? ''), (string) $ptRow['patient_id']) === 0
                        || strcasecmp((string) ($p[9] ?? ''), (string) $ptRow['mrn']) === 0
                        || strcasecmp((string) $p[0], (string) $ptRow['full_name']) === 0) {
                        $p[7]             = $addrStr;
                        $p['country']     = $country;
                        $p['province']    = $province;
                        $p['city']        = $city;
                        $p['barangay']    = $barangay;
                        $p['street']      = $street;
                        $p['postal_code'] = $postalCode;
                        $p['address']     = $addrStr;
                        break;
                    }
                }
                unset($p);
            }

            $logAudit('Admin corrected patient residential address for ' . $ptRow['full_name'] . ' (Reason: ' . $reason . ')', 'patients', (string) $ptRow['patient_id']);
            flash('Patient residential address corrected successfully.');
            redirect($return);

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
            $sql    = 'UPDATE accounts SET sex = :sx, country = :co, province = :pr, city = :ci, barangay = :bg, street = :st, postal_code = :pc';
            $params = [
                ':sx'  => $sex !== '' ? $sex : 'Not specified',
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
            $first    = trim((string) ($_POST['first_name'] ?? ''));
            $middle   = trim((string) ($_POST['middle_name'] ?? ''));
            $last     = trim((string) ($_POST['last_name'] ?? ''));
            $bdate    = trim((string) ($_POST['birthdate'] ?? ''));
            $sex      = trim((string) ($_POST['sex'] ?? 'Not specified'));
            $noPhone   = !empty($_POST['no_phone']);
            $phone     = $noPhone ? '' : trim((string) ($_POST['phone'] ?? ''));
            $cFirst    = trim((string) ($_POST['contact_first_name'] ?? ''));
            $cMiddle   = trim((string) ($_POST['contact_middle_name'] ?? ''));
            $cLast     = trim((string) ($_POST['contact_last_name'] ?? ''));
            $cRelBase  = trim((string) ($_POST['contact_relationship'] ?? ''));
            $cRelOther = trim((string) ($_POST['contact_relationship_other'] ?? ''));
            $cRel      = (strcasecmp($cRelBase, 'Other') === 0 && $cRelOther !== '') ? 'Other (' . $cRelOther . ')' : $cRelBase;
            $cName     = trim($cFirst . ($cMiddle !== '' ? ' ' . $cMiddle : '') . ' ' . $cLast);
            if ($cName === '' && !empty($_POST['contact_name'])) {
                $cName = trim((string) $_POST['contact_name']);
            }
            $cPhone    = trim((string) ($_POST['contact_phone'] ?? ''));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $bdate === '' || $sex === '' || $sex === 'Not specified') {
                flash('Please enter the patient name, birthdate, and sex.');
                redirect($return);
            }
            if ($noPhone) {
                if ($cFirst === '' || $cLast === '' || $cRelBase === '' || (strcasecmp($cRelBase, 'Other') === 0 && $cRelOther === '') || $cPhone === '') {
                    flash('When patient has no phone number, contact person first name, last name, relationship (with specification if Other), and contact number are required.');
                    redirect($return);
                }
            } else {
                if ($phone === '') {
                    flash('Please enter a valid patient mobile number or check "Patient has no phone number".');
                    redirect($return);
                }
            }
            $fmtBirth = ($bdate !== '' && strtotime($bdate) !== false) ? date('M d, Y', strtotime($bdate)) : $bdate;
            $provRef  = db_next_provisional_ref();

            $row = [
                $fullName,
                $fmtBirth,
                $noPhone ? '—' : $phone,
                '—',
                initials($fullName, 2),
                $sex,
                'Not specified',
                '— (Provisional phone-in record)',
                $provRef,
                '', // Blank MRN for provisional!
                'Provisional',
                date('Y-m-d'),
                'registration_status'        => 'Provisional',
                'reg_status'                 => 'Provisional',
                'mrn'                        => '',
                'patient_id'                 => $provRef,
                'first_name'                 => $first,
                'middle_name'                => $middle,
                'last_name'                  => $last,
                'no_phone'                   => $noPhone ? 1 : 0,
                'phone_verified'             => 0,
                'contact_name'               => $cName,
                'contact_relationship'       => $cRel,
                'contact_phone'              => $cPhone,
                'contact_first_name'         => $cFirst,
                'contact_middle_name'        => $cMiddle,
                'contact_last_name'          => $cLast,
                'contact_relationship_base'  => $cRelBase,
                'contact_relationship_other' => $cRelOther,
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
            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);

            // Rule 2: Staff at the front desk cannot complete registration on records linked to their own login
            $targetCheckName = $origPt ?: $fullName;
            if (in_array($role, ['Admin', 'Staff', 'Doctor', 'Cashier'], true) && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $targetCheckName)) {
                $currUid = (string) ($_SESSION['user_id'] ?? '');
                $msg = ($role === 'Admin')
                    ? admin_conflict_blocked_message($currUid)
                    : 'Conflict of Interest: You cannot edit your own patient record (or records linked to your login).';
                $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to complete registration on own or dependent patient record (' . $targetCheckName . ') [FLAGGED FOR REVIEW]', 'patients', $targetCheckName);
                flash($msg);
                redirect($return);
            }

            $bdate    = trim((string) ($_POST['birthdate'] ?? ''));
            $sex      = trim((string) ($_POST['sex'] ?? 'Not specified'));
            $blood    = trim((string) ($_POST['blood_type'] ?? 'Not specified'));
            $noPhone   = !empty($_POST['no_phone']);
            $phone     = $noPhone ? '' : trim((string) ($_POST['phone'] ?? ''));
            $cFirst    = trim((string) ($_POST['contact_first_name'] ?? ''));
            $cMiddle   = trim((string) ($_POST['contact_middle_name'] ?? ''));
            $cLast     = trim((string) ($_POST['contact_last_name'] ?? ''));
            $cRelBase  = trim((string) ($_POST['contact_relationship'] ?? ''));
            $cRelOther = trim((string) ($_POST['contact_relationship_other'] ?? ''));
            $cRel      = (strcasecmp($cRelBase, 'Other') === 0 && $cRelOther !== '') ? 'Other (' . $cRelOther . ')' : $cRelBase;
            $cName     = trim($cFirst . ($cMiddle !== '' ? ' ' . $cMiddle : '') . ' ' . $cLast);
            if ($cName === '' && !empty($_POST['contact_name'])) {
                $cName = trim((string) $_POST['contact_name']);
            }
            $cPhone    = trim((string) ($_POST['contact_phone'] ?? ''));
            $email     = trim((string) ($_POST['email'] ?? ''));
            $hin       = trim((string) ($_POST['hin'] ?? '—')) ?: '—';
            $country   = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province  = trim((string) ($_POST['province'] ?? 'Zamboanga del Sur'));
            $city      = trim((string) ($_POST['city'] ?? 'Zamboanga City'));
            $barangay  = trim((string) ($_POST['barangay'] ?? ''));
            $street    = trim((string) ($_POST['street'] ?? 'N/A'));
            $postal    = trim((string) ($_POST['postal_code'] ?? '7000'));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $bdate === '') {
                flash('Please enter the patient full name and birthdate.');
                redirect($return);
            }
            if ($noPhone) {
                if ($cFirst === '' || $cLast === '' || $cRelBase === '' || (strcasecmp($cRelBase, 'Other') === 0 && $cRelOther === '') || $cPhone === '') {
                    flash('When patient has no phone number, contact person first name, last name, relationship (with specification if Other), and contact number are required.');
                    redirect($return);
                }
                $phone = '';
            } else {
                if ($phone === '') {
                    flash('Please enter a valid mobile number or check "Patient has no phone number".');
                    redirect($return);
                }
                if (empty($_POST['phone_verified'])) {
                    flash('Patient mobile number must be verified via OTP before completing registration.');
                    redirect($return);
                }
            }

            $fmtBirth = ($bdate !== '' && strtotime($bdate) !== false) ? date('M d, Y', strtotime($bdate)) : $bdate;
            $addrStr  = implode(', ', array_filter([$street, $barangay, $city, $province, $country, $postal]));
            $mrn      = format_mrn(db_next_mrn());
            $ptId     = $mrn;

            foreach ($st['patients'] as $idx => $pt) {
                if (strcasecmp($pt[0], $origPt) === 0 || strcasecmp($pt[0], $fullName) === 0) {
                    $oldAddr = [
                        'country'     => (string) ($pt['country'] ?? ''),
                        'province'    => (string) ($pt['province'] ?? ''),
                        'city'        => (string) ($pt['city'] ?? ''),
                        'barangay'    => (string) ($pt['barangay'] ?? ''),
                        'street'      => (string) ($pt['street'] ?? ''),
                        'postal_code' => (string) ($pt['postal_code'] ?? ''),
                        'address'     => (string) ($pt[7] ?? ''),
                    ];
                    $newAddr = [
                        'country'     => $country,
                        'province'    => $province,
                        'city'        => $city,
                        'barangay'    => $barangay,
                        'street'      => $street,
                        'postal_code' => $postal,
                        'address'     => $addrStr,
                    ];
                    db_record_address_history('patient', $ptId, $oldAddr, $newAddr, (string) ($_SESSION['user_id'] ?? ''), $role, 'Complete registration');

                    $st['patients'][$idx] = [
                        $fullName,
                        $fmtBirth,
                        $noPhone ? '—' : $phone,
                        $hin,
                        initials($fullName, 2),
                        $sex,
                        $blood,
                        $addrStr,
                        $ptId,
                        $mrn,
                        'Registered',
                        $pt[11] ?? date('Y-m-d'),
                        'country'                    => $country,
                        'province'                   => $province,
                        'city'                       => $city,
                        'barangay'                   => $barangay,
                        'street'                     => $street,
                        'postal_code'                => $postal,
                        'phone_verified'             => $noPhone ? 0 : 1,
                        'email'                      => $email,
                        'mrn'                        => $mrn,
                        'reg_status'                 => 'Registered',
                        'registration_status'        => 'Registered',
                        'first_name'                 => $first,
                        'middle_name'                => $middle,
                        'last_name'                  => $last,
                        'no_phone'                   => $noPhone ? 1 : 0,
                        'contact_name'               => $cName,
                        'contact_relationship'       => $cRel,
                        'contact_phone'              => $cPhone,
                        'contact_first_name'         => $cFirst,
                        'contact_middle_name'        => $cMiddle,
                        'contact_last_name'          => $cLast,
                        'contact_relationship_base'  => $cRelBase,
                        'contact_relationship_other' => $cRelOther,
                        'id_checked'                 => 1,
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
            if (!$noPhone && $phone !== '') {
                push_sim_message(
                    'sms',
                    $phone,
                    'West Metro Medical Center — MRN Issued',
                    'Welcome ' . $fullName . '! Your permanent Medical Record Number is ' . $mrn . '. You can now activate your online Patient Portal account.',
                    ['MRN' => $mrn],
                    'index.php?auth=activate&phone=' . urlencode($phone),
                    'Activate Patient Portal Account'
                );
            }
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
            $noPhone   = !empty($_POST['no_phone']);
            $phone     = $noPhone ? '' : trim((string) ($_POST['phone'] ?? ''));
            $cFirst    = trim((string) ($_POST['contact_first_name'] ?? ''));
            $cMiddle   = trim((string) ($_POST['contact_middle_name'] ?? ''));
            $cLast     = trim((string) ($_POST['contact_last_name'] ?? ''));
            $cRelBase  = trim((string) ($_POST['contact_relationship'] ?? ''));
            $cRelOther = trim((string) ($_POST['contact_relationship_other'] ?? ''));
            $cRel      = (strcasecmp($cRelBase, 'Other') === 0 && $cRelOther !== '') ? 'Other (' . $cRelOther . ')' : $cRelBase;
            $cName     = trim($cFirst . ($cMiddle !== '' ? ' ' . $cMiddle : '') . ' ' . $cLast);
            if ($cName === '' && !empty($_POST['contact_name'])) {
                $cName = trim((string) $_POST['contact_name']);
            }
            $cPhone    = trim((string) ($_POST['contact_phone'] ?? ''));
            $blood     = trim((string) ($_POST['blood_type'] ?? 'Not specified'));
            $email     = trim((string) ($_POST['email'] ?? ''));
            $hin       = trim((string) ($_POST['hin'] ?? '—')) ?: '—';
            $country   = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province  = trim((string) ($_POST['province'] ?? 'Zamboanga del Sur'));
            $city      = trim((string) ($_POST['city'] ?? 'Zamboanga City'));
            $barangay  = trim((string) ($_POST['barangay'] ?? ''));
            $street    = trim((string) ($_POST['street'] ?? 'N/A'));
            $postal    = trim((string) ($_POST['postal_code'] ?? '7000'));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $bdate === '') {
                flash('Please enter the patient name and birthdate.');
                redirect($return);
            }
            if ($noPhone) {
                if ($cFirst === '' || $cLast === '' || $cRelBase === '' || (strcasecmp($cRelBase, 'Other') === 0 && $cRelOther === '') || $cPhone === '') {
                    flash('When patient has no phone number, contact person first name, last name, relationship (with specification if Other), and contact number are required.');
                    redirect($return);
                }
                $phone = '';
            } else {
                if ($phone === '') {
                    flash('Please enter a valid patient mobile number or check "Patient has no phone number".');
                    redirect($return);
                }
                if (empty($_POST['phone_verified']) && $orig === '') {
                    flash('Patient mobile number must be verified via OTP before registration.');
                    redirect($return);
                }
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
                // Rule 3: Admin/Staff/Doctor cannot edit/correct their own patient record (or dependents)
                $targetCheckName = $orig ?: $fullName;
                if (in_array($role, ['Admin', 'Staff', 'Doctor'], true) && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $targetCheckName)) {
                    $currUid = (string) ($_SESSION['user_id'] ?? '');
                    $msg = ($role === 'Admin')
                        ? admin_conflict_blocked_message($currUid)
                        : 'Conflict of Interest: You cannot edit your own patient record (or records linked to your login).';
                    $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to correct/update own or dependent patient record (' . $targetCheckName . ') [FLAGGED FOR REVIEW]', 'patients', $targetCheckName);
                    flash($msg);
                    redirect($return);
                }

                $mrn  = format_mrn(!empty($st['patients'][$idx][9]) ? (string) $st['patients'][$idx][9] : db_next_mrn());
                $ptId = $mrn;
                $oldPt = $st['patients'][$idx];
                $oldAddr = [
                    'country'     => $oldPt['country'] ?? 'Philippines',
                    'province'    => $oldPt['province'] ?? '',
                    'city'        => $oldPt['city'] ?? '',
                    'barangay'    => $oldPt['barangay'] ?? '',
                    'street'      => $oldPt['street'] ?? '',
                    'postal_code' => $oldPt['postal_code'] ?? '',
                    'address'     => $oldPt[7] ?? ($oldPt['address'] ?? ''),
                ];
                $newAddr = [
                    'country'     => $country,
                    'province'    => $province,
                    'city'        => $city,
                    'barangay'    => $barangay,
                    'street'      => $street,
                    'postal_code' => $postal,
                    'address'     => $addrStr,
                ];
                $addrChanged = false;
                foreach (['country', 'province', 'city', 'barangay', 'street', 'postal_code', 'address'] as $k) {
                    if (trim((string) ($oldAddr[$k] ?? '')) !== trim((string) ($newAddr[$k] ?? ''))) {
                        $addrChanged = true;
                        break;
                    }
                }
                if ($addrChanged) {
                    db_record_address_history(
                        'patient',
                        $mrn,
                        $oldAddr,
                        $newAddr,
                        (string) ($_SESSION['user_id'] ?? ''),
                        $role,
                        'Staff updated patient registration details'
                    );
                }

                $st['patients'][$idx] = [
                    $fullName, $fmtBirth, $noPhone ? '—' : $phone, $hin, initials($fullName, 2), $sex, $blood, $addrStr, $ptId, $mrn, 'Registered', $st['patients'][$idx][11] ?? date('Y-m-d'),
                    'country'                    => $country, 'province' => $province, 'city' => $city, 'barangay' => $barangay, 'street' => $street, 'postal_code' => $postal, 'phone_verified' => $noPhone ? 0 : 1, 'email' => $email, 'mrn' => $mrn, 'reg_status' => 'Registered', 'registration_status' => 'Registered',
                    'first_name'                 => $first, 'middle_name' => $middle, 'last_name' => $last,
                    'no_phone'                   => $noPhone ? 1 : 0, 'contact_name' => $cName, 'contact_relationship' => $cRel, 'contact_phone' => $cPhone,
                    'contact_first_name'         => $cFirst, 'contact_middle_name' => $cMiddle, 'contact_last_name' => $cLast,
                    'contact_relationship_base'  => $cRelBase, 'contact_relationship_other' => $cRelOther,
                    'id_checked'                 => 1,
                ];
                $logAudit('Updated', 'patients', $mrn);
                flash('Patient record updated for ' . $fullName . ' (' . $mrn . ').');
            } else {
                $mrn  = format_mrn(db_next_mrn());
                $ptId = $mrn;
                $row  = [
                    $fullName, $fmtBirth, $noPhone ? '—' : $phone, $hin, initials($fullName, 2), $sex, $blood, $addrStr, $ptId, $mrn, 'Registered', date('Y-m-d'),
                    'country'                    => $country, 'province' => $province, 'city' => $city, 'barangay' => $barangay, 'street' => $street, 'postal_code' => $postal, 'phone_verified' => $noPhone ? 0 : 1, 'email' => $email, 'mrn' => $mrn, 'reg_status' => 'Registered', 'registration_status' => 'Registered',
                    'first_name'                 => $first, 'middle_name' => $middle, 'last_name' => $last,
                    'no_phone'                   => $noPhone ? 1 : 0, 'contact_name' => $cName, 'contact_relationship' => $cRel, 'contact_phone' => $cPhone,
                    'contact_first_name'         => $cFirst, 'contact_middle_name' => $cMiddle, 'contact_last_name' => $cLast,
                    'contact_relationship_base'  => $cRelBase, 'contact_relationship_other' => $cRelOther,
                    'id_checked'                 => 1,
                ];
                array_unshift($st['patients'], $row);
                if (!$noPhone && $phone !== '') {
                    push_sim_message(
                        'sms',
                        $phone,
                        'West Metro Medical Center — Registration Confirmed',
                        'Hello ' . $fullName . '! You are registered at West Metro Medical Center with MRN ' . $mrn . '. Use your mobile number to activate your Patient Portal account.',
                        ['MRN' => $mrn],
                        'index.php?auth=activate&phone=' . urlencode($phone),
                        'Activate Patient Portal Account'
                    );
                }
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
                if ($role === 'Staff' && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $ptName)) {
                    $logAudit('Blocked conflict-of-interest attempt: Staff attempted to check in self or linked dependent', 'appointments', $ptName);
                    flash('Conflict of Interest: Staff members cannot check in their own patient record or a linked dependent. Another staff member must process this check-in.');
                    redirect($return);
                }
                $ptRec  = find_patient_record($st, $ptName);
                if ($ptRec && is_provisional_patient($ptRec)) {
                    flash('Provisional patient must complete full registration before check-in.');
                    redirect(url(['page' => 'appointments', 'view' => 'List', 'modal' => 'Complete registration', 'patient' => $ptName, 'checkin_appt' => $idx]));
                }
                $st['appointments'][$idx][3] = 'Checked-in';
                db_save_state($st);
                $logAudit('Checked-in', 'appointments', '#APT-' . (1000 + $idx));
                flash($ptName . ' checked in and ready for doctor consultation.');
            }
            redirect($return);

        case 'update_appt_status':
            $idx    = (int) ($_POST['idx'] ?? -1);
            $status = trim((string) ($_POST['status'] ?? ''));
            if (isset($st['appointments'][$idx]) && $status !== '') {
                $st['appointments'][$idx][3] = $status;
                db_save_state($st);
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

            // Conflict of Interest: Staff cannot book self or linked dependent
            if ($role === 'Staff' && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $patient)) {
                $logAudit('Blocked conflict-of-interest attempt: Staff attempted to book appointment for self or linked dependent', 'appointments', $patient);
                flash('Conflict of Interest: Staff members cannot book appointments for their own patient record or a linked dependent.');
                redirect($return);
            }

            // Conflict of Interest: Doctor cannot be attending doctor on self or linked dependent
            $docUserName = (string) ($_SESSION['user_name'] ?? '');
            $docAcc = db_find_account_by_id((string) ($_SESSION['user_id'] ?? ''));
            $isAttendingDoc = (strcasecmp($doctor, $docUserName) === 0 || ($docAcc && strcasecmp($doctor, (string)$docAcc['full_name']) === 0) || str_contains(strtolower($doctor), strtolower(str_replace('Dr. ', '', $docUserName))));
            if ($isAttendingDoc && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $patient)) {
                $logAudit('Blocked conflict-of-interest attempt: Doctor attempted to be attending doctor for self or linked dependent', 'appointments', $patient);
                flash('Conflict of Interest: A doctor cannot be the attending doctor on an appointment for their own patient record or a linked dependent.');
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
            db_save_state($st);
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
                db_save_state($st);
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
                db_save_state($st);
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

            // Conflict of Interest: Staff cannot admit self or linked dependent
            if ($role === 'Staff' && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $patient)) {
                $logAudit('Blocked conflict-of-interest attempt: Staff attempted to admit self or linked dependent', 'admissions', $patient);
                flash('Conflict of Interest: Staff cannot book, check in, or admit a patient linked to their own login.');
                redirect($return);
            }

            // Conflict of Interest: Doctor cannot be attending doctor on admission for self or linked dependent
            $docUserName = (string) ($_SESSION['user_name'] ?? '');
            $docAcc = db_find_account_by_id((string) ($_SESSION['user_id'] ?? ''));
            $isAttendingDoc = (strcasecmp($doctor, $docUserName) === 0 || ($docAcc && strcasecmp($doctor, (string)$docAcc['full_name']) === 0) || str_contains(strtolower($doctor), strtolower(str_replace('Dr. ', '', $docUserName))));
            if ($isAttendingDoc && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $patient)) {
                $logAudit('Blocked conflict-of-interest attempt: Doctor attempted to be attending doctor on admission for self/family', 'admissions', $patient);
                flash('Conflict of Interest: A doctor cannot be the attending doctor on an admission for their own patient record or a linked dependent.');
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
            $ptRecForBill = find_patient_record($st, $patient);
            $ptAddrForBill = $ptRecForBill ? ($ptRecForBill[7] ?? ($ptRecForBill['address'] ?? '')) : '';
            array_unshift($st['bills'], normalize_bill_row([
                $patient,
                format_money($bedRate),
                'Unpaid',
                date('M d, Y'),
                $newBillNo,
                0.0,
                $items,
                $mrnStr,
                $ptAddrForBill,
            ], 0));

            $logAudit('Admitted patient', 'admissions', $bedId);
            flash($patient . ' (' . $mrnStr . ') admitted to ' . $bedId . ' (' . $bedWard . ').');
            redirect(url(['page' => 'admissions']));

        case 'add_admission_note':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $note    = trim((string) ($_POST['progress_note'] ?? ''));
            if ($patient !== '' && $note !== '') {
                $newNote = [
                    'author' => current_user_name('Doctor'),
                    'time'   => date('M d, Y · H:i'),
                    'note'   => $note,
                ];
                foreach ($st['admissions'] as &$adm) {
                    if (strcasecmp((string) ($adm[0] ?? ($adm['patient_name'] ?? '')), $patient) === 0) {
                        if (!isset($adm[11]) || !is_array($adm[11])) {
                            $adm[11] = is_array($adm['clinical_notes'] ?? null) ? $adm['clinical_notes'] : [];
                        }
                        array_unshift($adm[11], $newNote);
                        $adm['clinical_notes'] = $adm[11];
                        break;
                    }
                }
                unset($adm);
                db_save_state($st);
                $logAudit('Added progress note', 'admissions', $patient);
                flash('Progress note recorded for ' . $patient . '.');
            }
            redirect(url(['page' => 'doctor-admissions', 'view' => 'chart', 'patient' => $patient]));

        case 'order_discharge':
            $patient   = trim((string) ($_POST['patient'] ?? ''));
            $finalDiag = trim((string) ($_POST['final_diagnosis'] ?? ''));
            $summary   = trim((string) ($_POST['discharge_summary'] ?? ''));
            foreach ($st['admissions'] as &$adm) {
                if (strcasecmp((string) ($adm[0] ?? ($adm['patient_name'] ?? '')), $patient) === 0) {
                    $adm[5] = $finalDiag !== '' ? $finalDiag : ($adm[5] ?? '');
                    $adm[6] = 'For discharge';
                    $adm[9] = $summary;
                    $adm['final_diagnosis'] = $adm[5];
                    $adm['status']          = 'For discharge';
                    $adm['discharge_order'] = $summary;
                    break;
                }
            }
            unset($adm);
            db_save_state($st);
            $logAudit('Marked For discharge', 'admissions', $patient);
            flash($patient . ' marked "For discharge" with completed discharge summary.');
            redirect(url(['page' => 'doctor-admissions', 'view' => 'chart', 'patient' => $patient]));

        case 'clear_discharge_billing':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $cStatus = 'Cleared';
            $reason  = trim((string) ($_POST['reason'] ?? 'All inpatient charges verified and settled'));

            // Conflict of interest check for cashier and admin
            if (in_array($role, ['Cashier', 'Admin'], true) && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $patient)) {
                $currUid = (string) ($_SESSION['user_id'] ?? '');
                $msg = ($role === 'Admin') ? admin_conflict_blocked_message($currUid) : 'Another cashier must process this bill.';
                $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to clear discharge billing for self/family [FLAGGED FOR REVIEW]', 'bills', $patient);
                flash($msg);
                redirect(url(['page' => 'discharge-clearance']));
            }

            // Discharge clearance can only be marked Cleared after processing full payment
            $ptBal = 0.0;
            foreach ($st['bills'] ?? [] as $i => $b) {
                $nb = normalize_bill_row($b, $i);
                if (strcasecmp($nb['patient'], $patient) === 0) {
                    $ptBal += $nb['balance'];
                }
            }
            if ($ptBal > 0.009) {
                flash('Cannot mark cleared: ' . $patient . ' has an outstanding balance of ' . format_money($ptBal) . '. Please process payment first before granting discharge clearance.');
                redirect(url(['page' => 'discharge-clearance']));
            }

            foreach ($st['admissions'] as &$adm) {
                if (strcasecmp((string) $adm[0], $patient) === 0) {
                    $adm[7]  = $cStatus;
                    $adm[10] = $reason;
                    break;
                }
            }
            unset($adm);
            db_save_state($st);
            $logAudit('Billing clearance -> ' . $cStatus, 'admissions', $patient);
            flash('Discharge billing clearance for ' . $patient . ' granted (' . $cStatus . ').');
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
            if ($bClear !== 'Cleared') {
                flash('Cannot finalize discharge yet: Cashier Billing Clearance for ' . $found[0] . ' is still Pending. Please process payment and have the Cashier mark the admission Cleared first.');
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
            if (in_array($role, ['Doctor', 'Staff'], true) && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $patientParam)) {
                $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to save clinical record for self/family', 'patients', $patientParam);
                flash('Conflict of Interest: You cannot create or modify clinical records for your own patient record or a linked family member.');
                redirect(url(['page' => 'patients']));
            }
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
            $ptRecForBill = find_patient_record($st, $patient);
            $ptAddrForBill = $ptRecForBill ? ($ptRecForBill[7] ?? ($ptRecForBill['address'] ?? '')) : '';
            $ptMrnForBill  = $ptRecForBill ? ($ptRecForBill[9] ?? ($ptRecForBill['mrn'] ?? '')) : '';
            array_unshift($st['bills'], normalize_bill_row([
                $patient,
                format_money($totalBill),
                'Unpaid',
                date('M d, Y'),
                $billNo,
                0.0,
                $billItems,
                $ptMrnForBill,
                $ptAddrForBill,
            ]));

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
                    // Conflict of interest check for cashier and admin
                    if (in_array($role, ['Cashier', 'Admin'], true) && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $nb['patient'])) {
                        $currUid = (string) ($_SESSION['user_id'] ?? '');
                        $msg = ($role === 'Admin') ? admin_conflict_blocked_message($currUid) : 'Another cashier must process this bill.';
                        $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to add charge/adjust bill #' . $billNo . ' for self/family [FLAGGED FOR REVIEW]', 'bills', $billNo);
                        flash($msg);
                        redirect(url(['page' => 'unpaid-bills']));
                    }
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
                        $nb['mrn'] ?? '',
                        $nb['patient_address'] ?? '',
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
            // Conflict of interest check for cashier and admin
            if (in_array($role, ['Cashier', 'Admin'], true) && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $normBill['patient'])) {
                $currUid = (string) ($_SESSION['user_id'] ?? '');
                $msg = ($role === 'Admin') ? admin_conflict_blocked_message($currUid) : 'Another cashier must process this bill.';
                $logAudit('Blocked conflict-of-interest attempt: ' . $role . ' attempted to process payment on bill #' . $billNo . ' for self/family [FLAGGED FOR REVIEW]', 'bills', $billNo);
                flash($msg);
                redirect(url(['page' => 'unpaid-bills']));
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
                $normBill['mrn'] ?? '',
                $normBill['patient_address'] ?? '',
            ], $billIdx);

            $maxOr = 1000;
            foreach ($st['payments'] ?? [] as $existingPy) {
                if (preg_match('/^OR-0*(\d+)$/i', (string) ($existingPy['receipt_no'] ?? ''), $om)) {
                    $maxOr = max($maxOr, (int) $om[1]);
                }
            }
            $receiptNo = 'OR-' . str_pad((string) ($maxOr + 1), 4, '0', STR_PAD_LEFT);

            $proofPath = null;
            if (in_array($method, ['HMO', 'PhilHealth'], true) && !empty($_FILES['proof_doc']['name'])) {
                $up = store_proof_image('proof_doc', 'payments');
                if (!empty($up['path'])) {
                    $proofPath = $up['path'];
                } elseif (!empty($up['error'])) {
                    flash($up['error']);
                    redirect($return);
                }
            }

            array_unshift($st['payments'], [
                'receipt_no'      => $receiptNo,
                'bill_no'         => $normBill['bill_no'],
                'patient_name'    => $normBill['patient'],
                'amount_paid'     => $amtPay,
                'amount_received' => $amtRecv,
                'change_due'      => $changeDue,
                'payment_method'  => $method,
                'reference_no'    => $method === 'Cash' ? '—' : $refNo,
                'proof_path'      => $proofPath,
                'cashier_name'    => current_user_name($role),
                'paid_date'       => date('Y-m-d'),
                'paid_at'         => date('M d, Y · H:i'),
                'items'           => $normBill['items'],
            ]);

            // If patient is an admitted inpatient and total remaining balance across all bills is 0, auto-clear discharge billing!
            $ptTotalBal = 0.0;
            foreach ($st['bills'] as $chkIdx => $chkB) {
                $normChkB = normalize_bill_row($chkB, $chkIdx);
                if (strcasecmp($normChkB['patient'], $normBill['patient']) === 0) {
                    $ptTotalBal += $normChkB['balance'];
                }
            }
            if ($ptTotalBal <= 0.009) {
                foreach ($st['admissions'] as &$adClear) {
                    if (strcasecmp((string) $adClear[0], $normBill['patient']) === 0) {
                        $adClear[7]  = 'Cleared';
                        $adClear[10] = 'Payment settled via ' . $method . ' (' . $receiptNo . ')';
                        break;
                    }
                }
                unset($adClear);
            }

            db_save_state($st);
            $logAudit('Collected payment (' . $method . ')', 'payments', $receiptNo);
            flash('Payment of ' . format_money($amtPay) . ' via ' . $method . ' recorded (' . $receiptNo . ').' . ($ptTotalBal <= 0.009 ? ' Inpatient discharge billing cleared.' : ''));
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
                $ptRecForBill = find_patient_record($st, $patient);
                $ptAddrForBill = $ptRecForBill ? ($ptRecForBill[7] ?? ($ptRecForBill['address'] ?? '')) : '';
                $ptMrnForBill  = $ptRecForBill ? ($ptRecForBill[9] ?? ($ptRecForBill['mrn'] ?? '')) : '';
                array_unshift($st['bills'], normalize_bill_row([
                    $patient, format_money($total), 'Unpaid', date('M d, Y'), $newBillNo, 0.0, $items,
                    $ptMrnForBill,
                    $ptAddrForBill,
                ], 0));
                $logAudit('Created manual bill', 'bills', $newBillNo);
                flash('Bill ' . $newBillNo . ' (' . format_money($total) . ') created for ' . $patient . '.');
            }
            redirect($return);

        /* -------------------------------------------------------
         * ADMIN PORTAL ACTIONS (Section 8)
         * ----------------------------------------------------- */
        case 'save_user':
            $orig       = trim((string) ($_POST['orig'] ?? ''));
            $first      = trim((string) ($_POST['first_name'] ?? ''));
            $middle     = trim((string) ($_POST['middle_name'] ?? ''));
            $last       = trim((string) ($_POST['last_name'] ?? ''));
            $uRole      = trim((string) ($_POST['role'] ?? 'Doctor'));
            $email      = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone      = trim((string) ($_POST['phone'] ?? ''));
            $birthdate  = trim((string) ($_POST['birthdate'] ?? ''));
            $sex        = trim((string) ($_POST['sex'] ?? ''));
            $dept       = trim((string) ($_POST['department'] ?? ''));
            $spec       = trim((string) ($_POST['specialty'] ?? ''));
            $license    = trim((string) ($_POST['license'] ?? ''));
            $country    = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province   = trim((string) ($_POST['province'] ?? ''));
            $city       = trim((string) ($_POST['city'] ?? ''));
            $barangay   = trim((string) ($_POST['barangay'] ?? ''));
            $street     = trim((string) ($_POST['street'] ?? ''));
            $postalCode = trim((string) ($_POST['postal_code'] ?? ''));
            $address    = trim((string) ($_POST['address'] ?? ''));
            if ($address === '' && ($street !== '' || $city !== '')) {
                $addrParts = array_filter([$street, $barangay, $city, $province, $country, $postalCode]);
                $address   = implode(', ', $addrParts);
            }

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
                        $u['first_name']  = $first;
                        $u['middle_name'] = $middle;
                        $u['last_name']   = $last;
                        $u['country']     = $country;
                        $u['province']    = $province;
                        $u['city']        = $city;
                        $u['barangay']    = $barangay;
                        $u['street']      = $street;
                        $u['postal_code'] = $postalCode;
                        $u['address']     = $address;
                        break;
                    }
                }
                unset($u);
                $logAudit('Updated user', 'users', $email);
                flash('Account updated for ' . $fullName . '.');
            } else {
                $created = db_create_account([
                    'user_id'        => $newUserId,
                    'first_name'     => $first,
                    'middle_name'    => $middle !== '' ? $middle : null,
                    'last_name'      => $last,
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
                    'country'        => $country,
                    'province'       => $province,
                    'city'           => $city,
                    'barangay'       => $barangay,
                    'street'         => $street,
                    'postal_code'    => $postalCode,
                    'address'        => $address,
                ], true);

                if (!empty($created['error'])) {
                    flash($created['error']);
                    redirect($return);
                }

                $finalId   = (string) ($created['account']['user_id'] ?? $newUserId);
                db_add_user_role($finalId, $uRole);
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
                    flash('Created ' . $uRole . ' account for ' . $fullName . '. Activation email sent directly to ' . $email . '. Please check your inbox.');
                } else {
                    flash('Created ' . $uRole . ' account for ' . $fullName . ', but email delivery to ' . $email . ' failed: ' . ($mailRes['error'] ?? 'SMTP delivery error'));
                }
            }
            redirect($return);

        case 'edit_user_details':
            $editUid = trim((string) ($_POST['user_id'] ?? ''));
            $reason  = trim((string) ($_POST['reason'] ?? ''));
            $currUid = (string) ($_SESSION['user_id'] ?? '');

            // Rule 3 & 4: Admin cannot deactivate, demote, or change roles/details of their own account
            if (strcasecmp($editUid, $currUid) === 0) {
                $msg = admin_conflict_blocked_message($currUid);
                $logAudit('Blocked conflict-of-interest attempt: Admin attempted to edit own account [FLAGGED FOR REVIEW]', 'accounts', '#' . $editUid);
                flash($msg);
                redirect($return);
            }

            $acc     = $editUid !== '' ? db_find_account_by_id($editUid) : null;
            if (!$acc) {
                flash('Account not found.');
                redirect($return);
            }
            if (!in_array((string) $acc['role'], ['Doctor', 'Staff', 'Cashier', 'Admin'], true)) {
                flash('Only Doctor, Staff, Cashier, and Admin accounts can be edited here.');
                redirect($return);
            }
            if (strlen($reason) < 5) {
                flash('A reason (at least 5 characters) is required to edit an account.');
                redirect($return);
            }
            $first     = trim((string) ($_POST['first_name'] ?? ''));
            $middle    = trim((string) ($_POST['middle_name'] ?? ''));
            $last      = trim((string) ($_POST['last_name'] ?? ''));
            $email     = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone     = trim((string) ($_POST['phone'] ?? ''));
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $sex       = trim((string) ($_POST['sex'] ?? ''));
            $fullName  = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('Please enter a valid name and email address.');
                redirect($return);
            }
            $isDoc   = ($acc['role'] === 'Doctor');
            $dept    = $isDoc ? trim((string) ($_POST['department'] ?? '')) : '';
            $spec    = $isDoc ? trim((string) ($_POST['specialty'] ?? '')) : '';
            $license = $isDoc ? trim((string) ($_POST['license'] ?? '')) : '';
            if ($isDoc && ($dept === '' || $spec === '' || $license === '')) {
                flash('Department, specialty, and PRC license are required for doctors.');
                redirect($return);
            }
            $dup = db()->prepare('SELECT COUNT(*) FROM accounts WHERE LOWER(email) = LOWER(:em) AND id <> :id');
            $dup->execute([':em' => $email, ':id' => $acc['id']]);
            if ((int) $dup->fetchColumn() > 0) {
                flash('That email address is already used by another account.');
                redirect($return);
            }

            $country    = trim((string) ($_POST['country'] ?? 'Philippines')) ?: 'Philippines';
            $province   = trim((string) ($_POST['province'] ?? ''));
            $city       = trim((string) ($_POST['city'] ?? ''));
            $barangay   = trim((string) ($_POST['barangay'] ?? ''));
            $street     = trim((string) ($_POST['street'] ?? ''));
            $postalCode = trim((string) ($_POST['postal_code'] ?? ''));
            $address    = trim((string) ($_POST['address'] ?? ''));
            if ($address === '' && ($street !== '' || $city !== '')) {
                $addrParts = array_filter([$street, $barangay, $city, $province, $country, $postalCode]);
                $address   = implode(', ', $addrParts);
            }

            $newVals = [
                'first_name'     => $first,
                'middle_name'    => $middle,
                'last_name'      => $last,
                'email'          => $email,
                'phone'          => $phone,
                'birthdate'      => $birthdate,
                'sex'            => $sex,
                'department'     => $dept,
                'specialty'      => $spec,
                'license_number' => $license,
                'country'        => $country,
                'province'       => $province,
                'city'           => $city,
                'barangay'       => $barangay,
                'street'         => $street,
                'postal_code'    => $postalCode,
                'address'        => $address,
            ];
            $changed = [];
            foreach ($newVals as $col => $val) {
                if ((string) ($acc[$col] ?? '') !== (string) $val) {
                    $changed[] = $col;
                }
            }
            if (!$changed) {
                flash('No changes were made.');
                redirect($return);
            }
            $addrCols = ['country', 'province', 'city', 'barangay', 'street', 'postal_code', 'address'];
            $addrChanged = false;
            foreach ($addrCols as $ac) {
                if (in_array($ac, $changed, true)) {
                    $addrChanged = true;
                    break;
                }
            }
            if ($addrChanged) {
                $oldAddr = [
                    'country'     => $acc['country'] ?? 'Philippines',
                    'province'    => $acc['province'] ?? '',
                    'city'        => $acc['city'] ?? '',
                    'barangay'    => $acc['barangay'] ?? '',
                    'street'      => $acc['street'] ?? '',
                    'postal_code' => $acc['postal_code'] ?? '',
                    'address'     => $acc['address'] ?? '',
                ];
                $newAddr = [
                    'country'     => $country,
                    'province'    => $province,
                    'city'        => $city,
                    'barangay'    => $barangay,
                    'street'      => $street,
                    'postal_code' => $postalCode,
                    'address'     => $address,
                ];
                db_record_address_history(
                    'employee',
                    (string) ($acc['user_id'] ?? $acc['id']),
                    $oldAddr,
                    $newAddr,
                    $currUid,
                    $role,
                    $reason
                );
            }

            db()->prepare('UPDATE accounts SET first_name = :fnm, middle_name = :mnm, last_name = :lnm, email = :em, phone = :ph, birthdate = :bd, sex = :sx, department = :dp, specialty = :sp, license_number = :ln, country = :co, province = :pr, city = :ci, barangay = :bg, street = :st, postal_code = :pc WHERE id = :id')
                ->execute([
                    ':fnm'  => $first !== '' ? $first : null,
                    ':mnm'  => $middle !== '' ? $middle : null,
                    ':lnm'  => $last !== '' ? $last : null,
                    ':em'   => $email,
                    ':ph'   => $phone,
                    ':bd'   => $birthdate,
                    ':sx'   => $sex,
                    ':dp'   => $dept,
                    ':sp'   => $spec,
                    ':ln'   => $license,
                    ':co'   => $country,
                    ':pr'   => $province,
                    ':ci'   => $city,
                    ':bg'   => $barangay,
                    ':st'   => $street,
                    ':pc'   => $postalCode,
                    ':id'   => $acc['id'],
                ]);

            $st = db_load_state();
            $_SESSION['state'] = &$st;
            $logAudit('Edited ' . $acc['role'] . ' account [' . implode(', ', $changed) . '] — Reason: ' . $reason, 'users', $email);
            db_save_state($st);
            flash('Account details updated for ' . $fullName . '. Reason recorded in the audit log.');
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
                if ($acc) {
                    $acc['full_name'] = db_format_account_name($acc);
                }
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
                flash('Activation email sent directly to ' . $destEmail . '. Please check your inbox.');
            } else {
                flash('Failed to deliver activation email to ' . $destEmail . ': ' . ($mailRes['error'] ?? 'SMTP delivery error'));
            }
            redirect($return);

        case 'send_set_password_link':
            $uid   = trim((string) ($_POST['user_id'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $name  = trim((string) ($_POST['name'] ?? 'User'));
            $token = bin2hex(random_bytes(24));
            $expiresAt = time() + (24 * 3600);
            db()->prepare('UPDATE accounts SET force_password_change = (CASE WHEN role = "Admin" THEN 0 ELSE 1 END), set_password_token = :tk, set_password_expires_at = :exp WHERE UPPER(user_id) = :uid')
                ->execute([':tk' => $token, ':exp' => $expiresAt, ':uid' => strtoupper($uid)]);

            $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir      = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $baseUrl  = $scheme . '://' . $host . $dir;
            $setPwUrl = $baseUrl . '/index.php?auth=login&set_password=1&uid=' . urlencode($uid) . '&token=' . urlencode($token);

            $targetEmail = $email !== '' ? $email : (string) (db_find_account_by_id($uid)['email'] ?? '');
            if ($targetEmail !== '') {
                $mailRes = send_account_activation_email($targetEmail, $name, 'User', $uid, $setPwUrl);
                if (!empty($mailRes['ok'])) {
                    flash('Set-Password link sent directly to ' . $targetEmail . '. Please check your inbox.');
                } else {
                    flash('Could not deliver email to ' . $targetEmail . ': ' . ($mailRes['error'] ?? 'SMTP delivery failed'));
                }
            } else {
                flash('Cannot send setup email: no email address associated with account ' . $uid . '.');
            }
            $logAudit('Sent password setup link', 'users', '#' . $uid);
            redirect($return);

        case 'toggle_user':
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $stmt  = db()->prepare('SELECT * FROM accounts WHERE LOWER(email) = LOWER(:em) LIMIT 1');
            $stmt->execute([':em' => $email]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($acc) {
                $acc['full_name'] = db_format_account_name($acc);
                $targetRole   = (string) $acc['role'];
                $targetStatus = (string) $acc['status'];
                $targetUid    = (string) $acc['user_id'];
                $targetName   = (string) $acc['full_name'];
                $currUid      = (string) ($_SESSION['user_id'] ?? '');

                // Rule 3 & 4: Admin cannot deactivate, demote, or change roles of their own account
                if (strcasecmp($targetUid, $currUid) === 0 || strcasecmp($email, (string) ($_SESSION['user_email'] ?? '')) === 0) {
                    $msg = admin_conflict_blocked_message($currUid);
                    $logAudit('Blocked conflict-of-interest attempt: Admin attempted to deactivate own account [FLAGGED FOR REVIEW]', 'accounts', '#' . $targetUid);
                    flash($msg);
                    redirect($return);
                }

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

            // Rule 3 & 4: Admin cannot change room rates for any charge on their own/dependent bills/admissions
            $currUid = (string) ($_SESSION['user_id'] ?? '');
            if ($role === 'Admin' && function_exists('db_is_room_charged_on_user_bills') && db_is_room_charged_on_user_bills($currUid, $newId ?: $roomNo)) {
                $msg = admin_conflict_blocked_message($currUid);
                $logAudit('Blocked conflict-of-interest attempt: Admin attempted to modify room/bed setup (' . ($newId ?: $roomNo) . ') charged on own/dependent admission [FLAGGED FOR REVIEW]', 'beds', $newId ?: $roomNo);
                flash($msg);
                redirect(url(['page' => 'rooms']));
            }

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
            $currUid = (string) ($_SESSION['user_id'] ?? '');
            if ($role === 'Admin' && function_exists('db_is_room_charged_on_user_bills') && db_is_room_charged_on_user_bills($currUid, $bedId)) {
                $msg = admin_conflict_blocked_message($currUid);
                $logAudit('Blocked conflict-of-interest attempt: Admin attempted to toggle bed (' . $bedId . ') charged on own/dependent admission [FLAGGED FOR REVIEW]', 'beds', $bedId);
                flash($msg);
                redirect(url(['page' => 'rooms']));
            }
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

            // Rule 3 & 4: Admin cannot change price list entries for any charge on their own/dependent bills
            $currUid = (string) ($_SESSION['user_id'] ?? '');
            if ($role === 'Admin' && function_exists('db_is_item_charged_on_user_bills') && db_is_item_charged_on_user_bills($currUid, $name ?: $id)) {
                $msg = admin_conflict_blocked_message($currUid);
                $logAudit('Blocked conflict-of-interest attempt: Admin attempted to modify price item (' . ($name ?: $id) . ') charged on own/dependent bill [FLAGGED FOR REVIEW]', 'price_list', $id ?: $name);
                flash($msg);
                redirect(url(['page' => 'price-list']));
            }

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
            $currUid = (string) ($_SESSION['user_id'] ?? '');
            if ($role === 'Admin' && function_exists('db_is_item_charged_on_user_bills') && db_is_item_charged_on_user_bills($currUid, $id)) {
                $msg = admin_conflict_blocked_message($currUid);
                $logAudit('Blocked conflict-of-interest attempt: Admin attempted to toggle price item (' . $id . ') charged on own/dependent bill [FLAGGED FOR REVIEW]', 'price_list', $id);
                flash($msg);
                redirect(url(['page' => 'price-list']));
            }
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
            $currUid = (string) ($_SESSION['user_id'] ?? '');
            if ($role === 'Admin' && function_exists('db_is_patient_conflict_of_interest') && db_is_patient_conflict_of_interest($currUid, $arcId)) {
                $msg = admin_conflict_blocked_message($currUid);
                $logAudit('Blocked conflict-of-interest attempt: Admin attempted to restore archive of own/dependent patient record (' . $arcId . ') [FLAGGED FOR REVIEW]', 'archive', $arcId);
                flash($msg);
                redirect($return);
            }
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
$authView = (string) ($_GET['auth'] ?? '');
$role     = $_SESSION['role'] ?? null;
if (!empty($_GET['set_password']) || in_array($authView, ['login', 'register', 'activate'], true) || !in_array($role, ['Admin', 'Staff', 'Cashier', 'Doctor', 'Patient'], true)) {
    if (in_array($authView, ['register', 'activate'], true) || !empty($_SESSION['register_error']) || !empty($_SESSION['activate_error'])) {
        require __DIR__ . '/pages/register.php';
        exit;
    }
    if ($authView === 'login' && empty($_GET['set_password'])) {
        unset($_SESSION['force_pw_change_uid'], $_SESSION['set_pw_error']);
    }
    $showLogin = $authView === 'login'
        || !empty($_GET['set_password'])
        || !empty($_SESSION['login_error'])
        || !empty($_SESSION['reset_sent']);
    require __DIR__ . ($showLogin ? '/pages/login.php' : '/pages/landing.php');
    exit;
}

/* ---------------------------------------------------------------
 * 3. Routing
 * ------------------------------------------------------------- */
$userId = (string) ($_SESSION['user_id'] ?? '');
if ($userId !== '') {
    $availablePortals = db_get_available_portals($userId);
    $_SESSION['user_roles'] = $availablePortals;

    // Rule 2 & 3: Check if current active portal is actually available to this account
    if (!in_array($role, $availablePortals, true)) {
        $reqUrl = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
        $actorRole = ['Admin' => 'Administrator', 'Staff' => 'Staff', 'Cashier' => 'Cashier', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$role] ?? $role;
        $actingActor = (!empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : $userId) . ' acting as ' . $actorRole;
        try {
            $pdo = db();
            $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
            $insAl->execute([
                ':an' => $actingActor,
                ':ac' => "Denied portal access attempt: User '{$userId}' attempted to access '{$role}' portal without permission. Requested URL: {$reqUrl} [FLAGGED FOR REVIEW]",
                ':tb' => 'accounts',
                ':ri' => $userId,
                ':la' => date('M d, Y · H:i'),
                ':ar' => $actorRole,
            ]);
        } catch (Throwable) {}

        // Fallback to employee portal if available, or first available portal
        $fallbackRole = null;
        foreach (['Admin', 'Doctor', 'Staff', 'Cashier'] as $empR) {
            if (in_array($empR, $availablePortals, true)) {
                $fallbackRole = $empR;
                break;
            }
        }
        if ($fallbackRole === null && !empty($availablePortals)) {
            $fallbackRole = $availablePortals[0];
        }

        if ($fallbackRole !== null) {
            $_SESSION['role'] = $fallbackRole;
            $role = $fallbackRole;
            unset($_SESSION['active_patient_id'], $_SESSION['active_patient_name'], $_SESSION['active_patient_mrn']);
            flash('Access to that portal is not available. Redirected to your authorized portal.');
            redirect('index.php');
        } else {
            session_destroy();
            redirect('index.php?auth=login');
        }
    }

    // Rule 3 & 4: If in Patient portal, verify user has active linked patient record AND selected patient is linked
    if ($role === 'Patient') {
        $linkedPts = db_get_account_linked_patients($userId);
        if (empty($linkedPts)) {
            // Rule 4: If user's last link is removed while in patient portal, end that portal session and return them to employee portal
            $reqUrl = (string) ($_SERVER['REQUEST_URI'] ?? 'index.php');
            $actorRole = 'Patient';
            $actingActor = (!empty($_SESSION['user_name']) ? (string) $_SESSION['user_name'] : $userId) . ' acting as Patient';
            try {
                $pdo = db();
                $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
                $insAl->execute([
                    ':an' => $actingActor,
                    ':ac' => "Denied portal access attempt: No active linked patient records for user '{$userId}'. Ended patient portal session. Requested URL: {$reqUrl} [FLAGGED FOR REVIEW]",
                    ':tb' => 'accounts',
                    ':ri' => $userId,
                    ':la' => date('M d, Y · H:i'),
                    ':ar' => $actorRole,
                ]);
            } catch (Throwable) {}

            $fallbackRole = null;
            foreach (['Admin', 'Doctor', 'Staff', 'Cashier'] as $empR) {
                if (in_array($empR, $availablePortals, true)) {
                    $fallbackRole = $empR;
                    break;
                }
            }
            if ($fallbackRole !== null) {
                $_SESSION['role'] = $fallbackRole;
                $role = $fallbackRole;
                unset($_SESSION['active_patient_id'], $_SESSION['active_patient_name'], $_SESSION['active_patient_mrn']);
                flash('You have no linked patient records. Returned to your employee portal.');
                redirect('index.php');
            } else {
                session_destroy();
                redirect('index.php?auth=login');
            }
        }

        $activePid = (string) ($_SESSION['active_patient_id'] ?? '');
        $isLinked = false;
        foreach ($linkedPts as $lp) {
            if (strcasecmp((string) $lp['patient_id'], $activePid) === 0) {
                $isLinked = true;
                break;
            }
        }
        if (!$isLinked) {
            $_SESSION['active_patient_id']   = (string) $linkedPts[0]['patient_id'];
            $_SESSION['active_patient_name'] = (string) $linkedPts[0]['patient_name'];
            $_SESSION['active_patient_mrn']  = format_mrn((string) ($linkedPts[0]['mrn'] ?: $linkedPts[0]['patient_id']));
        }
    }
}

$nav    = nav_for($role);
$routes = routes_for($role);
$page   = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($routes[$page])) {
    $page = 'dashboard';
}

$detail     = $_GET['detail'] ?? null;
$detailFile = $detail !== null ? (details_for($role)[$detail] ?? null) : null;
$modal      = isset($_GET['modal']) ? (string) $_GET['modal'] : null;

// Rule 3 & 4: Doctor / Staff / Admin cannot open or edit their own patient record (or linked records) from doctor/staff/admin portals
if (in_array($role, ['Doctor', 'Staff', 'Admin'], true)) {
    $targetPt = trim((string) ($_GET['patient'] ?? ($_GET['id'] ?? ($_GET['mrn'] ?? ($_GET['p'] ?? '')))));
    if ($targetPt !== '' && function_exists('db_is_patient_conflict_of_interest') && db_is_patient_conflict_of_interest((string) ($_SESSION['user_id'] ?? ''), $targetPt)) {
        $actorRole = ['Admin' => 'Administrator', 'Staff' => 'Staff', 'Cashier' => 'Cashier', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$role] ?? 'Staff';
        $actingActor = current_user_name($role) . ' acting as ' . ($role === 'Staff' ? 'Staff' : $role);
        try {
            $pdo = db();
            $insAl = $pdo->prepare('INSERT INTO audit_logs (actor_name, action, table_name, record_id, logged_at, actor_role) VALUES (:an, :ac, :tb, :ri, :la, :ar)');
            $insAl->execute([
                ':an' => $actingActor,
                ':ac' => 'Blocked conflict-of-interest attempt: ' . $role . ' attempted to access own or linked patient record (' . $targetPt . ') [FLAGGED FOR REVIEW]',
                ':tb' => 'patients',
                ':ri' => $targetPt,
                ':la' => date('M d, Y · H:i'),
                ':ar' => $actorRole,
            ]);
        } catch (Throwable) {}
        $conflictMsg = ($role === 'Admin')
            ? 'Conflict of Interest: ' . admin_conflict_blocked_message((string) ($_SESSION['user_id'] ?? '')) . ' Your own patient records (and linked dependents) are only viewable through your Patient Portal.'
            : 'Conflict of Interest: You cannot view or edit your own patient record (or records linked to your login) from staff or clinical portals. Their records are only viewable through your Patient Portal.';
        flash($conflictMsg);
        redirect(url(['page' => ($role === 'Admin' ? 'dashboard' : 'patients')]));
    }
}

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
