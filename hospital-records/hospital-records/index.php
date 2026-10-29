<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/icons.php';
require __DIR__ . '/includes/components.php';
require __DIR__ . '/includes/booking.php';

/* ---------------------------------------------------------------
 * 1. POST actions (login, logout, state changes) -> redirect (PRG)
 * ------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if (!csrf_valid() && !in_array($action, ['login', 'register_account', 'forgot'], true)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back and try again.');
    }

    $return = safe_return($_POST['return'] ?? null);
    $st     = &state();
    $role   = $_SESSION['role'] ?? 'Admin';

    $logAudit = static function (string $act, string $table, string $recId) use (&$st, $role): void {
        $actorRole = ['Admin' => 'Administrator', 'Staff' => 'Front desk', 'Doctor' => 'Doctor', 'Patient' => 'Patient'][$role] ?? 'Staff';
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
        case 'register_account':
            $userId    = strtoupper(trim((string) ($_POST['user_id'] ?? '')));
            $first     = trim((string) ($_POST['first_name'] ?? ''));
            $last      = trim((string) ($_POST['last_name'] ?? ''));
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $username  = strtolower(trim((string) ($_POST['username'] ?? '')));
            $email     = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password  = (string) ($_POST['password'] ?? '');
            $confirm   = (string) ($_POST['confirm_password'] ?? '');
            $dept      = trim((string) ($_POST['department'] ?? ''));
            $spec      = trim((string) ($_POST['specialty'] ?? ''));

            $_SESSION['reg_old'] = [
                'user_id'    => $userId,
                'first_name' => $first,
                'last_name'  => $last,
                'birthdate'  => $birthdate,
                'username'   => $username,
                'email'      => $email,
                'department' => $dept,
                'specialty'  => $spec,
            ];

            if ($password !== $confirm) {
                $_SESSION['register_error'] = 'Password and confirmation do not match.';
                redirect('index.php?auth=register');
            }

            $fullName = trim($first . ' ' . $last);
            $res = db_create_account([
                'user_id'   => $userId,
                'username'  => $username,
                'email'     => $email,
                'password'  => $password,
                'full_name' => $fullName,
                'birthdate' => $birthdate,
                'department'=> $dept,
                'specialty' => $spec,
            ]);

            if (isset($res['error'])) {
                $_SESSION['register_error'] = $res['error'];
                redirect('index.php?auth=register');
            }

            $acc = $res['account'];
            unset($_SESSION['reg_old'], $_SESSION['register_error']);

            // Reload state from DB so $st['users'] includes the newly created account
            $st = db_load_state();
            $_SESSION['state'] = &$st;

            // If this is a Patient (P-ID), link or create their patient record in $st['patients'] with their birthday
            if ($acc['role'] === 'Patient') {
                $fmtBdate = format_birthdate($acc['birthdate'] ?? $birthdate);
                $ptExists = false;
                foreach ($st['patients'] as &$pt) {
                    if (strcasecmp((string) ($pt[8] ?? ''), $acc['user_id']) === 0 || strcasecmp($pt[0], $acc['full_name']) === 0) {
                        $pt[0] = $acc['full_name'];
                        if ($fmtBdate !== '—') {
                            $pt[1] = $fmtBdate;
                        }
                        $pt[8] = $acc['user_id'];
                        $ptExists = true;
                        break;
                    }
                }
                unset($pt);
                if (!$ptExists) {
                    array_unshift($st['patients'], [
                        $acc['full_name'],
                        $fmtBdate,
                        '—',
                        'HIN-' . rand(100, 999) . '-' . rand(100, 999),
                        initials($acc['full_name'], 2),
                        'Not specified',
                        'Not specified',
                        '—',
                        $acc['user_id'],
                    ]);
                }
            }

            array_unshift($st['audit'], [
                $acc['full_name'],
                'Created',
                'accounts',
                '#' . $acc['user_id'],
                date('M d, Y · H:i'),
                $acc['user_role_label'],
            ]);

            $_SESSION['role']      = $acc['role'];
            $_SESSION['user_id']   = $acc['user_id'];
            $_SESSION['user_name'] = $acc['role'] === 'Doctor' && !str_starts_with($acc['full_name'], 'Dr.')
                ? 'Dr. ' . $acc['full_name']
                : $acc['full_name'];
            flash('Account created! Signed in as ' . $_SESSION['user_name'] . ' (' . $acc['user_id'] . ')');
            redirect('index.php');

        case 'login':
            $userId    = strtoupper(trim((string) ($_POST['user_id'] ?? '')));
            $username  = strtolower(trim((string) ($_POST['username'] ?? '')));
            $password  = (string) ($_POST['password'] ?? '');
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $_SESSION['login_user_id']  = $userId;
            $_SESSION['login_username'] = $username;

            $verify = db_verify_login($userId, $username, $password, $birthdate);
            if (isset($verify['error'])) {
                $_SESSION['login_error'] = $verify['error'];
                redirect('index.php?auth=login');
            }

            $acc = $verify['account'];
            unset($_SESSION['login_user_id'], $_SESSION['login_username']);
            $_SESSION['role']      = (string) $acc['role'];
            $_SESSION['user_id']   = (string) $acc['user_id'];
            $_SESSION['user_name'] = $acc['role'] === 'Doctor' && !str_starts_with((string) $acc['full_name'], 'Dr.')
                ? 'Dr. ' . $acc['full_name']
                : (string) $acc['full_name'];

            if ($acc['role'] === 'Patient') {
                $fmtBdate = format_birthdate((string) ($acc['birthdate'] ?? $birthdate));
                $ptExists = false;
                foreach ($st['patients'] as &$pt) {
                    if (strcasecmp((string) ($pt[8] ?? ''), (string) $acc['user_id']) === 0 || strcasecmp($pt[0], (string) $acc['full_name']) === 0) {
                        if ($fmtBdate !== '—') {
                            $pt[1] = $fmtBdate;
                        }
                        $pt[8] = (string) $acc['user_id'];
                        $ptExists = true;
                        break;
                    }
                }
                unset($pt);
                if (!$ptExists) {
                    array_unshift($st['patients'], [
                        (string) $acc['full_name'],
                        $fmtBdate,
                        '—',
                        'HIN-' . rand(100, 999) . '-' . rand(100, 999),
                        initials((string) $acc['full_name'], 2),
                        'Not specified',
                        'Not specified',
                        '—',
                        (string) $acc['user_id'],
                    ]);
                }
            }
            redirect('index.php');

        case 'forgot':
            $_SESSION['reset_sent'] = true;
            redirect('index.php?auth=login');

        case 'logout':
            unset($_SESSION['role'], $_SESSION['user_id'], $_SESSION['user_name']);
            header('Location: index.php');
            exit;

        case 'notify':
            flash((string) ($_POST['message'] ?? 'Done'));
            redirect($return);

        case 'toggle_user':
            $email = (string) ($_POST['email'] ?? '');
            foreach ($st['users'] as &$u) {
                if ($u[1] === $email) {
                    $u[3] = $u[3] === 'Active' ? 'Disabled' : 'Active';
                    db_toggle_account_by_email($email, $u[3]);
                    $logAudit($u[3] === 'Active' ? 'Updated' : 'Disabled', 'users', '#' . ($u[7] ?? 'USR'));
                    flash($u[0] . ' account ' . strtolower($u[3]));
                }
            }
            unset($u);
            redirect($return);

        case 'save_user':
            $orig      = trim((string) ($_POST['orig'] ?? ''));
            $first     = trim((string) ($_POST['first_name'] ?? ''));
            $middle    = trim((string) ($_POST['middle_name'] ?? ''));
            $last      = trim((string) ($_POST['last_name'] ?? ''));
            $email     = trim((string) ($_POST['email'] ?? ''));
            $username  = strtolower(trim((string) ($_POST['username'] ?? '')));
            $password  = (string) ($_POST['password'] ?? '');
            $uRole     = trim((string) ($_POST['role'] ?? 'Doctor'));
            $dept      = trim((string) ($_POST['department'] ?? ''));
            $spec      = trim((string) ($_POST['specialty'] ?? ''));
            $license   = trim((string) ($_POST['license'] ?? ''));
            $birthdate = trim((string) ($_POST['birthdate'] ?? ''));
            $active    = isset($_POST['active']) ? 'Active' : 'Disabled';

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '' || $email === '') {
                flash('User not saved: please enter name and email');
                redirect($return);
            }
            if (strcasecmp($uRole, 'Administrator') === 0 || strcasecmp($uRole, 'Admin') === 0) {
                flash('There can only be one Administrator in the system');
                redirect($return);
            }

            $idx = null;
            foreach ($st['users'] as $i => $u) {
                if (($orig !== '' && $u[1] === $orig) || strcasecmp($u[1], $email) === 0) {
                    $idx = $i;
                    break;
                }
            }

            $rolePrefix = db_prefix_for_role($uRole);
            $uRoleNorm  = match ($rolePrefix) {
                'D'     => 'Doctor',
                'C'     => 'Cashier',
                'P'     => 'Patient',
                default => 'Staff',
            };

            if ($idx !== null) {
                if ($username === '') {
                    $username = $st['users'][$idx][8] ?? strtolower(explode('@', $email)[0]);
                }
                $upd = db_update_user_account($orig ?: $email, $fullName, $email, $username, $uRoleNorm, $active, $dept, $spec, $license, $password, $birthdate);
                if (isset($upd['error'])) {
                    flash('User not saved: ' . $upd['error']);
                    redirect($return);
                }
                $updatedId = (string) ($upd['user_id'] ?? ($st['users'][$idx][7] ?? db_next_user_id($rolePrefix)));
                $fmtBdate  = format_birthdate((string) ($upd['birthdate'] ?? $birthdate));
                $st = db_load_state();
                $_SESSION['state'] = &$st;
                if ($uRoleNorm === 'Patient' && $fmtBdate !== '—') {
                    foreach ($st['patients'] as &$pt) {
                        if (strcasecmp((string) ($pt[8] ?? ''), $updatedId) === 0 || strcasecmp($pt[0], $fullName) === 0) {
                            $pt[1] = $fmtBdate;
                            break;
                        }
                    }
                    unset($pt);
                }
                $logAudit('Updated', 'users', '#' . $updatedId);
                flash('User "' . $fullName . '" (' . $updatedId . ') updated');
            } else {
                if ($password === '' || mb_strlen($password) < 8) {
                    flash('User not saved: please enter a password of at least 8 characters');
                    redirect($return);
                }
                if ($username === '') {
                    $username = strtolower(explode('@', $email)[0]);
                }
                $newUserId = db_next_user_id($rolePrefix);
                $created   = db_create_account([
                    'user_id'   => $newUserId,
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $password,
                    'full_name' => $fullName,
                    'role'      => $uRoleNorm,
                    'status'    => $active,
                    'department'=> $dept,
                    'specialty' => $spec,
                    'license'   => $license,
                    'birthdate' => $birthdate,
                ], true);
                if (isset($created['error'])) {
                    flash('User not saved: ' . $created['error']);
                    redirect($return);
                }
                $finalUserId = (string) ($created['account']['user_id'] ?? $newUserId);
                $fmtBdate    = format_birthdate((string) ($created['account']['birthdate'] ?? $birthdate));
                $st = db_load_state();
                $_SESSION['state'] = &$st;
                if ($uRoleNorm === 'Patient') {
                    $ptExists = false;
                    foreach ($st['patients'] as &$pt) {
                        if (strcasecmp((string) ($pt[8] ?? ''), $finalUserId) === 0 || strcasecmp($pt[0], $fullName) === 0) {
                            if ($fmtBdate !== '—') {
                                $pt[1] = $fmtBdate;
                            }
                            $pt[8]    = $finalUserId;
                            $ptExists = true;
                            break;
                        }
                    }
                    unset($pt);
                    if (!$ptExists) {
                        array_unshift($st['patients'], [$fullName, $fmtBdate, '—', 'HIN-' . rand(100, 999) . '-' . rand(100, 999), initials($fullName, 2), 'Not specified', 'Not specified', '—', $finalUserId]);
                    }
                }
                $logAudit('Created', 'users', '#' . $finalUserId);
                flash('User "' . $fullName . '" added · Assigned ID: ' . $finalUserId . ' · Username: ' . $username);
            }
            redirect($return);

        case 'delete_user':
            $email = trim((string) ($_POST['email'] ?? ''));
            if ($email !== '') {
                $arc = db_archive_user_account($email, current_user_name($role));
                if ($arc !== null) {
                    $st = db_load_state();
                    $_SESSION['state'] = &$st;
                    $logAudit('Archived', 'users', $arc[0]);
                    flash($arc[1] . ' moved to Archive (' . $arc[0] . ')');
                }
            }
            redirect($return);

        case 'register_patient':
            $orig   = trim((string) ($_POST['orig'] ?? ''));
            $first  = trim((string) ($_POST['first_name'] ?? ''));
            $middle = trim((string) ($_POST['middle_name'] ?? ''));
            $last   = trim((string) ($_POST['last_name'] ?? ''));
            $bdate  = trim((string) ($_POST['birthdate'] ?? ''));
            $sex    = trim((string) ($_POST['sex'] ?? ''));
            $phone  = trim((string) ($_POST['phone'] ?? ''));
            $blood  = trim((string) ($_POST['blood_type'] ?? ''));
            $addr   = trim((string) ($_POST['address'] ?? ''));
            $hin    = trim((string) ($_POST['insurance'] ?? ''));

            $fullName = trim($first . ($middle !== '' ? ' ' . $middle : '') . ' ' . $last);
            if ($fullName === '') {
                flash('Please enter the patient\'s name');
                redirect($return);
            }

            $formattedBirth = $bdate !== '' && strtotime($bdate) !== false
                ? date('M d, Y', strtotime($bdate))
                : ($orig !== '' ? $bdate : date('M d, Y', strtotime('-30 years')));
            $formattedPhone = $phone !== '' ? $phone : '(415) 555-' . rand(1000, 9999);
            $formattedHin   = $hin !== '' ? $hin : 'HIN-' . rand(100, 999) . '-' . rand(100, 999);
            $formattedSex   = ($sex !== '' && $sex !== 'Select sex') ? $sex : 'Not specified';
            // Blood type is optional
            $formattedBlood = ($blood !== '' && !str_starts_with($blood, 'Optional') && $blood !== 'Select blood type') ? $blood : 'Not specified';
            $formattedAddr  = $addr !== '' ? $addr : 'San Francisco, CA';
            $avatar         = initials($fullName, 2);

            $idx = null;
            foreach ($st['patients'] as $i => $pt) {
                if (($orig !== '' && $pt[0] === $orig) || strcasecmp($pt[0], $fullName) === 0) {
                    $idx = $i;
                    break;
                }
            }

            if ($idx !== null) {
                $ptId = $st['patients'][$idx][8] ?? db_next_user_id('P');
                $row  = [$fullName, $formattedBirth, $formattedPhone, $formattedHin, $avatar, $formattedSex, $formattedBlood, $formattedAddr, $ptId];
                $st['patients'][$idx] = $row;
                $logAudit('Updated', 'patients', '#' . $ptId);
                flash('Patient record updated for ' . $fullName . ' (Assigned ID: ' . $ptId . ')');
            } else {
                $reqPtId = strtoupper(trim((string) ($_POST['patient_id'] ?? '')));
                $ptId    = (preg_match('/^P-\d{4,}$/', $reqPtId) && !in_array($reqPtId, array_column($st['patients'], 8), true))
                    ? $reqPtId
                    : db_next_user_id('P');
                $row  = [$fullName, $formattedBirth, $formattedPhone, $formattedHin, $avatar, $formattedSex, $formattedBlood, $formattedAddr, $ptId];
                array_unshift($st['patients'], $row);
                foreach ($st['roles'] as &$rr) {
                    if ($rr[0] === 'Patient') {
                        $rr[2]++;
                    }
                }
                unset($rr);
                $logAudit('Created', 'patients', '#' . $ptId);
                flash($fullName . ' registered · Assigned Patient ID: ' . $ptId . ' (for account creation)');
            }
            redirect($return);

        case 'book_appointment':
            $patient   = trim((string) ($_POST['patient'] ?? ''));
            $dept      = trim((string) ($_POST['department'] ?? 'General Medicine'));
            $doctor    = trim((string) ($_POST['doctor'] ?? ''));
            $dateRaw   = trim((string) ($_POST['date'] ?? ''));
            $slot      = trim((string) ($_POST['slot'] ?? '09:00'));
            $visitType = trim((string) ($_POST['visit_type'] ?? 'Consultation'));
            $complaint = trim((string) ($_POST['complaint'] ?? 'Routine consultation'));

            if ($role === 'Patient') {
                $patient = current_user_name('Patient');
            }
            if ($patient === '' || $patient === 'Select patient') {
                flash('Please select a patient for the appointment');
                redirect($return);
            }
            if ($doctor === '') {
                flash('Please choose a doctor for the appointment');
                redirect($return);
            }
            $doctorFound = false;
            foreach (doctors_data() as $dd) {
                if (strcasecmp($dd[0], $doctor) === 0) {
                    $doctor      = $dd[0];
                    $dept        = $dd[1];
                    $doctorFound = true;
                }
            }
            if (!$doctorFound) {
                flash('Please choose a doctor from the list');
                redirect(url(['page' => 'book']));
            }

            $ymd     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateRaw) ? $dateRaw : '';
            $slot    = normalize_slot($slot);
            $resIdx  = isset($_POST['reschedule']) ? (int) $_POST['reschedule'] : -1;
            $docRow  = doctor_by_name($doctor);
            $ptParam = $role === 'Patient' ? null : $patient;

            if ($resIdx >= 0) {
                $cur = $st['appointments'][$resIdx] ?? null;
                if ($cur === null
                    || !in_array($cur[3], BOOKABLE_STATUSES, true)
                    || ($role === 'Patient' && $cur[1] !== $patient)) {
                    flash('This appointment can no longer be rescheduled');
                    redirect(url(['page' => 'appointments']));
                }
            }
            $stepTwo = url(['page' => 'book', 'step' => 2, 'doc' => $doctor, 'date' => $ymd, 'month' => substr($ymd, 0, 7),
                'patient' => $ptParam, 'reschedule' => $resIdx >= 0 ? $resIdx : null]);

            if ($ymd !== '' && patient_has_slot($st, $patient, $ymd, $slot, $resIdx)) {
                flash('You already have an appointment on ' . date('M d, Y', strtotime($ymd)) . ' at ' . slot_label($slot) . '. Please choose a different time.');
                redirect($stepTwo);
            }
            if ($ymd === '' || !slot_available($st, $docRow, $ymd, $slot, $resIdx, $patient)) {
                flash('Sorry, that time is no longer available. Please choose another slot.');
                redirect($stepTwo);
            }
            if ($complaint === '') {
                flash('Please enter the reason for your visit');
                redirect(str_replace('step=2', 'step=3', $stepTwo) . '&time=' . urlencode($slot));
            }
            $dateFmt = date('M d, Y', strtotime($ymd));

            if ($resIdx >= 0) {
                $st['appointments'][$resIdx][0] = $slot;
                $st['appointments'][$resIdx][4] = $complaint;
                $st['appointments'][$resIdx][7] = $dateFmt;
                $logAudit('Updated', 'appointments', '#APT-' . rand(1000, 9999));
                flash('Appointment rescheduled to ' . $dateFmt . ' at ' . slot_label($slot));
                redirect(url(['page' => 'book', 'step' => 4, 'appt' => $resIdx, 'rescheduled' => 1, 'patient' => $ptParam]));
            }

            array_unshift($st['appointments'], [$slot, $patient, $visitType, 'Confirmed', $complaint, $doctor, $dept, $dateFmt]);
            $logAudit('Created', 'appointments', '#APT-' . rand(1000, 9999));
            flash('Appointment booked with ' . $doctor . ' on ' . $dateFmt . ' at ' . slot_label($slot));
            redirect(url(['page' => 'book', 'step' => 4, 'appt' => 0, 'patient' => $ptParam]));

        case 'update_appointment':
            $idx       = (int) ($_POST['idx'] ?? -1);
            $step      = trim((string) ($_POST['step'] ?? ''));
            $slot      = trim((string) ($_POST['slot'] ?? ''));
            $complaint = trim((string) ($_POST['complaint'] ?? ''));

            if (isset($st['appointments'][$idx])) {
                $curr = $st['appointments'][$idx][3];
                // Completed and Cancelled are permanently locked
                if (!in_array($curr, ['Completed', 'Cancelled'], true)) {
                    if ($slot !== '') {
                        $st['appointments'][$idx][0] = $slot;
                    }
                    if ($complaint !== '') {
                        $st['appointments'][$idx][4] = $complaint;
                    }
                    if ($step === 'advance' || $step === 'in_progress' || $step === 'completed') {
                        if ($step === 'in_progress' && in_array($curr, ['Confirmed', 'Waiting', 'Upcoming'], true)) {
                            $st['appointments'][$idx][3] = 'In progress';
                        } elseif ($step === 'completed' && $curr === 'In progress') {
                            $st['appointments'][$idx][3] = 'Completed';
                        } elseif ($step === 'advance') {
                            if (in_array($curr, ['Confirmed', 'Waiting', 'Upcoming'], true)) {
                                $st['appointments'][$idx][3] = 'In progress';
                            } elseif ($curr === 'In progress') {
                                $st['appointments'][$idx][3] = 'Completed';
                            }
                        }
                    } elseif ($step === 'cancel' && in_array($curr, ['Confirmed', 'Waiting', 'Upcoming'], true)) {
                        $st['appointments'][$idx][3] = 'Cancelled';
                    }
                }
                $ptName = $st['appointments'][$idx][1];
                $newSt  = $st['appointments'][$idx][3];
                $logAudit('Updated', 'appointments', '#APT-' . rand(1000, 9999));
                flash('Appointment for ' . $ptName . ' — ' . $newSt);
            }
            redirect($return);

        case 'save_crud':
            $type = trim((string) ($_POST['type'] ?? ''));
            $orig = trim((string) ($_POST['orig'] ?? ''));

            if ($type === 'departments') {
                $name = trim((string) ($_POST['dept_name'] ?? ''));
                $head = trim((string) ($_POST['dept_head'] ?? 'Unassigned'));
                $loc  = trim((string) ($_POST['dept_location'] ?? '1st Floor'));
                if ($name === '') {
                    flash('Please enter a department name');
                    redirect($return);
                }
                $newRow = [$name, $head !== '' ? $head : 'Unassigned', '0', $loc !== '' ? $loc : 'Main Wing'];
            } elseif ($type === 'medications') {
                $name = trim((string) ($_POST['med_name'] ?? ''));
                $cat  = trim((string) ($_POST['med_category'] ?? 'Antibiotic'));
                $str  = trim((string) ($_POST['med_strength'] ?? '500 mg'));
                $stk  = trim((string) ($_POST['med_stock'] ?? '100 units'));
                if ($name === '') {
                    flash('Please enter a medication name');
                    redirect($return);
                }
                if ($stk !== '' && is_numeric($stk)) {
                    $stk .= ' units';
                }
                $newRow = [$name, $cat, $str !== '' ? $str : '500 mg', $stk !== '' ? $stk : '100 units'];
            } else {
                redirect($return);
            }

            $idx = null;
            foreach ($st['crud'][$type] as $i => $r) {
                if (($orig !== '' && $r[0] === $orig) || strcasecmp($r[0], $newRow[0]) === 0) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx !== null) {
                $st['crud'][$type][$idx] = $newRow;
                $logAudit('Updated', $type, '#' . strtoupper(substr($type, 0, 3)) . '-' . rand(100, 999));
                flash($newRow[0] . ' updated');
            } else {
                $st['crud'][$type][] = $newRow;
                $logAudit('Created', $type, '#' . strtoupper(substr($type, 0, 3)) . '-' . rand(100, 999));
                flash($newRow[0] . ' added');
            }
            redirect($return);

        case 'delete_crud':
            $type = (string) ($_POST['type'] ?? '');
            $key  = (string) ($_POST['key'] ?? '');
            if (isset($st['crud'][$type])) {
                $deletedRow = null;
                foreach ($st['crud'][$type] as $r) {
                    if ($r[0] === $key) {
                        $deletedRow = $r;
                        break;
                    }
                }
                $st['crud'][$type] = array_values(array_filter(
                    $st['crud'][$type],
                    static fn ($r) => $r[0] !== $key
                ));
                if ($deletedRow !== null) {
                    $recordLabel = match ($type) {
                        'departments' => 'Department',
                        'rooms'       => 'Room',
                        'medications' => 'Medication',
                        default       => ucfirst(rtrim($type, 's')),
                    };
                    $arcRow = db_archive_record($key, $recordLabel, current_user_name($role), [
                        'kind' => $type,
                        'data' => $deletedRow,
                    ]);
                    array_unshift($st['archive'], $arcRow);
                    $logAudit('Archived', $type, $arcRow[0]);
                    flash($key . ' moved to Archive (' . $arcRow[0] . ')');
                }
            }
            redirect($return);

        case 'restore_archive':
            $arcId = (string) ($_POST['key'] ?? '');
            $restored = db_restore_archive_record($arcId, $st);
            $st['archive'] = array_values(array_filter(
                $st['archive'] ?? [],
                static fn ($r) => $r[0] !== $arcId
            ));
            $logAudit('Restored', 'archive', $arcId);
            if ($restored) {
                $st = db_load_state();
                $_SESSION['state'] = &$st;
                flash(($restored['subject'] ?? $arcId) . ' restored to active ' . strtolower($restored['type'] ?? 'records'));
            } else {
                flash($arcId . ' restored to active records');
            }
            redirect($return);

        case 'discharge':
            $key   = (string) ($_POST['key'] ?? '');
            $found = null;
            foreach ($st['admissions'] as $r) {
                if ($r[0] === $key) {
                    $found = $r;
                }
            }
            if ($found === null) {
                flash('Admission not found');
                redirect($return);
            }
            $up = store_proof_image('proof', 'discharges');
            if (isset($up['error'])) {
                flash('Discharge not saved: ' . $up['error']);
                redirect($return);
            }
            // Remove from admissions
            $st['admissions'] = array_values(array_filter(
                $st['admissions'],
                static fn ($r) => $r[0] !== $key
            ));
            // Free the room automatically upon discharge
            foreach ($st['crud']['rooms'] as &$room) {
                if ($room[0] === $found[1] || strcasecmp((string) ($room[3] ?? ''), (string) $found[0]) === 0) {
                    $room[2] = 'Available';
                    $room[3] = '—';
                }
            }
            unset($room);
            $st['discharges'][] = [$found[0], $found[1], $found[2], date('M d, Y'), $up['path'], time()];
            $logAudit('Updated', 'admissions', '#ADM-' . rand(1000, 9999));
            flash($found[0] . ' discharged — ' . $found[1] . ' is now available');
            redirect($return);

        case 'upload_receipt':
            $patient = (string) ($_POST['key'] ?? '');
            $idx     = null;
            foreach ($st['bills'] as $i => $b) {
                if ($b[0] === $patient) {
                    $idx = $i;
                }
            }
            if ($idx === null) {
                flash('Bill not found');
                redirect($return);
            }
            $up = store_proof_image('receipt', 'receipts');
            if (isset($up['error'])) {
                flash('Receipt not saved: ' . $up['error']);
                redirect($return);
            }
            $st['receipts'][$patient] = $up['path'];
            $st['bills'][$idx][2]     = 'Paid';
            $logAudit('Updated', 'bills', '#INV-' . rand(1000, 9999));
            flash('Receipt uploaded and bill marked as paid');
            redirect($return);

        case 'save_role':
            $orig  = (string) ($_POST['orig'] ?? '');
            $name  = trim((string) ($_POST['name'] ?? ''));
            $desc  = trim((string) ($_POST['description'] ?? ''));
            $perms = array_values(array_intersect(permissions_list(), (array) ($_POST['perms'] ?? [])));
            if ($name === '' || mb_strlen($name) > 40) {
                flash('Role not saved: enter a role name (max 40 characters)');
                redirect($return);
            }
            if (in_array(strtolower($name), ['administrator', 'admin'], true)) {
                flash('Role not saved: there can only be one Administrator in the system');
                redirect($return);
            }
            foreach ($st['roles'] as $r) {
                if (strcasecmp($r[0], $name) === 0 && $r[0] !== $orig) {
                    flash('Role not saved: a role named "' . $name . '" already exists');
                    redirect($return);
                }
            }
            $idx = null;
            foreach ($st['roles'] as $i => $r) {
                if ($orig !== '' && $r[0] === $orig) {
                    $idx = $i;
                }
            }
            if ($idx === null) {
                $st['roles'][] = [$name, $desc !== '' ? $desc : 'Custom role', 0, $perms, 'shield'];
                $logAudit('Created', 'roles', $name);
                flash('Role "' . $name . '" created');
            } else {
                $st['roles'][$idx][0] = $name;
                $st['roles'][$idx][1] = $desc;
                $st['roles'][$idx][3] = $perms;
                $logAudit('Updated', 'roles', $name);
                flash('Role "' . $name . '" updated');
            }
            redirect($return);

        case 'delete_role':
            $key = (string) ($_POST['key'] ?? '');
            $deletedRole = null;
            foreach ($st['roles'] as $r) {
                if ($r[0] === $key) {
                    if ($r[2] > 0) {
                        flash('This role cannot be deleted while it has users assigned');
                        redirect($return);
                    }
                    $deletedRole = $r;
                }
            }
            $st['roles'] = array_values(array_filter($st['roles'], static fn ($r) => $r[0] !== $key));
            if ($deletedRole !== null) {
                $arcRow = db_archive_record($key, 'Role', current_user_name($role), [
                    'kind' => 'role',
                    'data' => $deletedRole,
                ]);
                array_unshift($st['archive'], $arcRow);
                $logAudit('Archived', 'roles', $arcRow[0]);
                flash('Role "' . $key . '" moved to Archive (' . $arcRow[0] . ')');
            }
            redirect($return);

        case 'read_notifications':
            $who = $_SESSION['role'] ?? '';
            foreach ($st['notifications'][$who] ?? [] as $i => $n) {
                $st['notifications'][$who][$i][3] = true;
            }
            redirect($return);

        case 'set_room_status':
            $roomKey = (string) ($_POST['key'] ?? '');
            foreach ($st['crud']['rooms'] as &$room) {
                if ($room[0] === $roomKey && $room[2] !== 'Occupied') {
                    $room[2] = 'Available';
                    $room[3] = '—';
                    flash($room[0] . ' is now available');
                }
            }
            unset($room);
            redirect($return);

        case 'save_room':
            $orig     = trim((string) ($_POST['orig'] ?? ''));
            $roomNum  = trim((string) ($_POST['room_number'] ?? ''));
            $ward     = trim((string) ($_POST['ward'] ?? 'General Ward'));
            $ptDetail = trim((string) ($_POST['patient_detail'] ?? ''));
            $doctor   = trim((string) ($_POST['doctor'] ?? 'Unassigned'));

            if ($roomNum === '' || $roomNum === 'No available rooms') {
                flash('Room not saved: please select an available room');
                redirect($return);
            }

            $patientName = '—';
            if ($ptDetail !== '' && !str_starts_with($ptDetail, '—')) {
                $patientName = trim(explode('·', $ptDetail)[0]);
            }

            // Room status is 100% automatic based on patient assignment
            if ($patientName === '' || $patientName === '—') {
                $status      = 'Available';
                $patientName = '—';
            } else {
                $status = 'Occupied';
                // Free any other room previously occupied by this patient
                foreach ($st['crud']['rooms'] as &$otherRm) {
                    if ($otherRm[0] !== $roomNum && $otherRm[0] !== $orig && strcasecmp((string) ($otherRm[3] ?? ''), $patientName) === 0) {
                        $otherRm[2] = 'Available';
                        $otherRm[3] = '—';
                    }
                }
                unset($otherRm);
            }

            $targetKey = $orig !== '' ? $orig : $roomNum;
            $idx = null;
            foreach ($st['crud']['rooms'] as $i => $rm) {
                if ($rm[0] === $targetKey || $rm[0] === $roomNum) {
                    $idx = $i;
                    break;
                }
            }

            if ($idx !== null) {
                $st['crud']['rooms'][$idx] = [$roomNum, $ward, $status, $patientName];
                $logAudit('Updated', 'rooms', $roomNum);
                flash($roomNum . ' updated (' . $status . ')');
            } else {
                $st['crud']['rooms'][] = [$roomNum, $ward, $status, $patientName];
                $logAudit('Created', 'rooms', $roomNum);
                flash($roomNum . ' added (' . $status . ')');
            }

            if ($status === 'Occupied' && $patientName !== '—') {
                // Remove any other patient's active admission in this room
                $st['admissions'] = array_values(array_filter(
                    $st['admissions'],
                    static fn ($adm) => ($adm[1] !== $roomNum && ($orig === '' || $adm[1] !== $orig)) || strcasecmp((string) $adm[0], $patientName) === 0
                ));
                $admExists = false;
                foreach ($st['admissions'] as &$adm) {
                    if (strcasecmp((string) $adm[0], $patientName) === 0) {
                        $adm[1] = $roomNum;
                        $adm[2] = $ward;
                        $admExists = true;
                    }
                }
                unset($adm);
                if (!$admExists) {
                    $st['admissions'][] = [$patientName, $roomNum, $ward, date('M d, Y'), $doctor, 'Assigned via Rooms'];
                }
            } else {
                // Remove from active admissions if this room was set to Available
                $st['admissions'] = array_values(array_filter(
                    $st['admissions'],
                    static fn ($adm) => $adm[1] !== $roomNum && ($orig === '' || $adm[1] !== $orig)
                ));
            }
            redirect($return);

        case 'admit_patient':
            $patient  = trim((string) ($_POST['patient'] ?? ''));
            $roomRaw  = trim((string) ($_POST['room'] ?? ''));
            $roomKey  = trim(explode('·', $roomRaw)[0]);
            $doctor   = trim((string) ($_POST['doctor'] ?? 'Unassigned'));
            $reason   = trim((string) ($_POST['reason'] ?? ''));

            if ($patient === '' || $patient === 'Select patient' || $roomKey === '' || $roomKey === 'No available rooms') {
                flash('Please select a patient and an available room');
                redirect($return);
            }

            // Find the room and its ward
            $roomWard = '';
            $roomFound = false;
            foreach ($st['crud']['rooms'] as &$room) {
                if ($room[0] === $roomKey) {
                    if ($room[2] === 'Occupied' && strcasecmp((string) ($room[3] ?? ''), $patient) !== 0) {
                        flash($roomKey . ' is already occupied');
                        redirect($return);
                    }
                    $roomWard   = $room[1];
                    $room[2]    = 'Occupied';
                    $room[3]    = $patient;
                    $roomFound  = true;
                } elseif (strcasecmp((string) ($room[3] ?? ''), $patient) === 0) {
                    // Free previous room if patient was moved
                    $room[2] = 'Available';
                    $room[3] = '—';
                }
            }
            unset($room);

            if (!$roomFound) {
                flash('Room not found');
                redirect($return);
            }

            // Replace any prior active admission for this patient and add the new admission
            $st['admissions'] = array_values(array_filter(
                $st['admissions'],
                static fn ($adm) => strcasecmp((string) $adm[0], $patient) !== 0 && $adm[1] !== $roomKey
            ));
            array_unshift($st['admissions'], [$patient, $roomKey, $roomWard, date('M d, Y'), $doctor, $reason]);
            // Ensure an inpatient bill entry exists or is created
            $hasBill = false;
            foreach ($st['bills'] as $b) {
                if ($b[0] === $patient && $b[2] !== 'Paid') {
                    $hasBill = true;
                }
            }
            if (!$hasBill) {
                array_unshift($st['bills'], [$patient, '$1,450.00', 'Pending', date('M d, Y')]);
            }
            $logAudit('Created', 'admissions', '#ADM-' . rand(1000, 9999));
            flash($patient . ' admitted to ' . $roomKey);
            redirect(url(['page' => 'admissions']));

        case 'start_consultation':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            foreach ($st['appointments'] as &$appt) {
                if ($appt[1] === $patient && in_array($appt[3], ['Confirmed', 'Waiting', 'Upcoming'], true)) {
                    $appt[3] = 'In progress';
                }
            }
            unset($appt);
            redirect(url(['page' => 'my-appointments', 'detail' => 'new-record', 'patient' => urlencode($patient)]));

        case 'save_record':
            $patient    = trim((string) ($_POST['patient'] ?? ''));
            $complaint  = trim((string) ($_POST['complaint'] ?? ''));
            $notes      = trim((string) ($_POST['notes'] ?? ''));
            $visitType  = trim((string) ($_POST['visit_type'] ?? 'Consultation'));
            $recStatus  = in_array($_POST['record_status'] ?? '', ['Draft', 'Completed'], true)
                          ? (string) $_POST['record_status'] : 'Completed';

            // Build diagnoses from parallel arrays
            $icd10s    = array_map('trim', (array) ($_POST['icd10'] ?? []));
            $descs     = array_map('trim', (array) ($_POST['diagnosis'] ?? []));
            $diagnoses = [];
            foreach ($icd10s as $i => $code) {
                $desc = $descs[$i] ?? '';
                if ($code !== '' || $desc !== '') {
                    $diagnoses[] = [$code, $desc];
                }
            }

            // Build prescriptions
            $meds    = (array) ($_POST['medication'] ?? []);
            $doses   = array_map('trim', (array) ($_POST['dosage'] ?? []));
            $freqs   = (array) ($_POST['frequency'] ?? []);
            $durs    = array_map('trim', (array) ($_POST['duration'] ?? []));
            $prescriptions = [];
            foreach ($meds as $i => $med) {
                if ($med !== '' && $med !== 'Select medication') {
                    $prescriptions[] = [$med, $doses[$i] ?? '', $freqs[$i] ?? '', $durs[$i] ?? ''];
                }
            }

            // Build lab orders
            $tests    = (array) ($_POST['test'] ?? []);
            $pris     = (array) ($_POST['priority'] ?? []);
            $labOrders = [];
            foreach ($tests as $i => $test) {
                if ($test !== '' && $test !== 'Select test') {
                    $labOrders[] = [$test, $pris[$i] ?? 'Routine'];
                }
            }

            if ($patient === '') {
                flash('Record not saved: no patient specified');
                redirect($return);
            }

            $recId = 'REC-' . strtoupper(bin2hex(random_bytes(3)));
            $rec   = [
                'id'            => $recId,
                'date'          => date('M d, Y'),
                'visit_type'    => $visitType,
                'complaint'     => $complaint,
                'notes'         => $notes,
                'diagnoses'     => $diagnoses,
                'prescriptions' => $prescriptions,
                'lab_orders'    => $labOrders,
                'doctor'        => current_user_name($role),
                'status'        => $recStatus,
            ];

            if (!isset($st['records'][$patient])) {
                $st['records'][$patient] = [];
            }
            array_unshift($st['records'][$patient], $rec);

            // Mark appointment status & sync complaint
            foreach ($st['appointments'] as &$appt) {
                if ($appt[1] === $patient) {
                    $appt[3] = $recStatus === 'Draft' ? 'In progress' : 'Completed';
                    if ($complaint !== '') {
                        $appt[4] = $complaint;
                    }
                }
            }
            unset($appt);

            // Add ordered lab tests to session lab results
            foreach ($labOrders as $order) {
                $testId = strtoupper(substr(str_replace(' ', '', $order[0]), 0, 3)) . '-' . rand(1000, 9999);
                array_unshift($st['lab_results'], [$testId, $patient, $order[0], 'Pending', date('M d, Y')]);
            }

            $logAudit('Created', 'medical_records', '#' . $recId);
            flash($recStatus === 'Draft' ? 'Draft saved for ' . $patient : 'Visit completed for ' . $patient);
            redirect(url(['page' => 'my-appointments', 'detail' => 'history', 'patient' => urlencode($patient)]));

        case 'complete_lab':
            $testId = trim((string) ($_POST['test_id'] ?? ''));
            foreach ($st['lab_results'] as &$lr) {
                if ($lr[0] === $testId) {
                    $lr[3] = 'Done';
                    flash('Lab test ' . $testId . ' marked as Done');
                }
            }
            unset($lr);
            redirect($return);

        case 'cancel_appt':
            $idx = (int) ($_POST['idx'] ?? -1);
            $cur = $st['appointments'][$idx] ?? null;
            if ($cur !== null
                && in_array($cur[3], BOOKABLE_STATUSES, true)
                && ($role !== 'Patient' || $cur[1] === current_user_name('Patient'))) {
                $st['appointments'][$idx][3] = 'Cancelled';
                $logAudit('Cancelled', 'appointments', '#APT-' . rand(1000, 9999));
                flash('Appointment with ' . $cur[5] . ' on ' . $cur[7] . ' has been cancelled');
            }
            redirect($return);

        case 'update_patient_profile':
            $ptName  = current_user_name('Patient');
            $phone   = trim((string) ($_POST['phone'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $foundPt = false;
            foreach ($st['patients'] as &$pt) {
                if ($pt[0] === $ptName) {
                    if ($phone !== '') {
                        $pt[2] = $phone;
                    }
                    if ($address !== '') {
                        $pt[7] = $address;
                    }
                    $foundPt = true;
                }
            }
            unset($pt);
            if (!$foundPt) {
                $st['patients'][] = [$ptName, '—', $phone ?: '—', '—', initials($ptName, 2), 'Not specified', 'Not specified', $address ?: '—'];
            }
            $logAudit('Updated', 'patients', '#PT-0001');
            flash('Contact information updated');
            redirect($return);

        case 'change_patient_password':
            $currPw = (string) ($_POST['current_password'] ?? '');
            $newPw  = (string) ($_POST['new_password'] ?? '');
            $confPw = (string) ($_POST['confirm_password'] ?? '');
            if ($currPw === '' || $newPw === '') {
                flash('Please enter your current and new password');
                redirect($return);
            }
            if (mb_strlen($newPw) < 8) {
                flash('New password must be at least 8 characters');
                redirect($return);
            }
            if ($newPw !== $confPw) {
                flash('New password and confirmation do not match');
                redirect($return);
            }
            $pwRes = db_change_account_password((string) ($_SESSION['user_id'] ?? ''), $currPw, $newPw);
            if (isset($pwRes['error'])) {
                flash($pwRes['error']);
                redirect($return);
            }
            $logAudit('Updated', 'accounts', '#' . ($_SESSION['user_id'] ?? 'PT'));
            flash('Password updated successfully');
            redirect($return);

        case 'process_payment':
            $billNo   = trim((string) ($_POST['bill_no'] ?? ''));
            $amtPay   = parse_money($_POST['amount_to_pay'] ?? 0);
            $amtRecv  = parse_money($_POST['amount_received'] ?? 0);
            $method   = trim((string) ($_POST['payment_method'] ?? 'Cash'));
            $refNo    = trim((string) ($_POST['reference_no'] ?? ''));
            if (!in_array($method, ['Cash', 'Card', 'GCash', 'Insurance'], true)) {
                $method = 'Cash';
            }

            $billIdx = null;
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
                flash('Bill not found');
                redirect($return);
            }

            $balanceDue = $normBill['balance'];
            if ($balanceDue <= 0) {
                flash('This bill is already paid in full');
                redirect($return);
            }

            if ($amtPay <= 0) {
                $amtPay = $balanceDue;
            }
            if ($amtPay > $balanceDue) {
                $amtPay = $balanceDue;
            }

            if ($method !== 'Cash' && $refNo === '') {
                flash('Please enter a reference number for ' . $method . ' payments');
                redirect(url(['page' => 'process-payment', 'bill' => $normBill['bill_no']]));
            }

            if ($amtRecv < $amtPay) {
                flash('Amount received (' . format_money($amtRecv) . ') cannot be less than the amount being paid (' . format_money($amtPay) . ')');
                redirect(url(['page' => 'process-payment', 'bill' => $normBill['bill_no']]));
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

            // Generate next Official Receipt number (OR-1001, OR-1002, ...)
            $maxOr = 1000;
            foreach ($st['payments'] ?? [] as $existingPy) {
                if (preg_match('/^OR-0*(\d+)$/i', (string) ($existingPy['receipt_no'] ?? ''), $om)) {
                    $maxOr = max($maxOr, (int) $om[1]);
                }
            }
            $receiptNo   = 'OR-' . str_pad((string) ($maxOr + 1), 4, '0', STR_PAD_LEFT);
            $cashierName = current_user_name($role);

            $paymentRow = [
                'receipt_no'      => $receiptNo,
                'bill_no'         => $normBill['bill_no'],
                'patient_name'    => $normBill['patient'],
                'amount_paid'     => $amtPay,
                'amount_received' => $amtRecv,
                'change_due'      => $changeDue,
                'payment_method'  => $method,
                'reference_no'    => $method === 'Cash' ? '—' : $refNo,
                'cashier_name'    => $cashierName,
                'paid_date'       => date('Y-m-d'),
                'paid_at'         => date('M d, Y · H:i'),
                'items'           => $normBill['items'],
            ];
            if (!isset($st['payments']) || !is_array($st['payments'])) {
                $st['payments'] = [];
            }
            array_unshift($st['payments'], $paymentRow);

            $logAudit('Collected', 'payments', $receiptNo);
            flash('Payment of ' . format_money($amtPay) . ' confirmed (' . $receiptNo . ')');
            redirect(url(['page' => 'process-payment', 'bill' => $normBill['bill_no'], 'success_or' => $receiptNo]));

        case 'create_bill':
            $patient = trim((string) ($_POST['patient'] ?? ''));
            $descs   = (array) ($_POST['item_desc'] ?? []);
            $qtys    = (array) ($_POST['item_qty'] ?? []);
            $rates   = (array) ($_POST['item_rate'] ?? []);

            if ($patient === '' || $patient === 'Select patient') {
                flash('Please select a patient for the bill');
                redirect($return);
            }

            $items = [];
            $total = 0.0;
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
            if (!$items || $total <= 0) {
                flash('Please enter at least one charge item with a valid amount');
                redirect($return);
            }

            $maxBillNum = 1000;
            foreach ($st['bills'] ?? [] as $idx => $b) {
                $nb = normalize_bill_row($b, $idx);
                if (preg_match('/^BILL-0*(\d+)$/i', $nb['bill_no'], $bm)) {
                    $maxBillNum = max($maxBillNum, (int) $bm[1]);
                }
            }
            $newBillNo = 'BILL-' . str_pad((string) ($maxBillNum + 1), 4, '0', STR_PAD_LEFT);
            array_unshift($st['bills'], normalize_bill_row([
                $patient,
                format_money($total),
                'Unpaid',
                date('M d, Y'),
                $newBillNo,
                0.0,
                $items,
            ], 0));
            $logAudit('Created', 'bills', $newBillNo);
            flash('Bill ' . $newBillNo . ' (' . format_money($total) . ') created for ' . $patient);
            redirect($return);
    }
    redirect($return);
}

/* ---------------------------------------------------------------
 * 2. Not signed in -> landing page, login screen, or register screen
 * ------------------------------------------------------------- */
$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['Admin', 'Staff', 'Cashier', 'Doctor', 'Patient'], true)) {
    $authView = (string) ($_GET['auth'] ?? '');
    if ($authView === 'register' || !empty($_SESSION['register_error'])) {
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

// Booking uses the dedicated 3-step flow instead of a modal
if ($modal === 'Book appointment' && in_array($role, ['Patient', 'Staff'], true)) {
    redirect(url(['page' => 'book', 'dept' => $_GET['dept'] ?? null, 'doc' => $_GET['doc'] ?? null, 'step' => isset($_GET['doc']) ? 2 : null]));
}

$state = &state();

// Archive CSV export (served before any HTML)
if ($role === 'Admin' && $page === 'archive' && isset($_GET['export'])) {
    $from = (isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])) ? $_GET['from'] : '';
    $to   = (isset($_GET['to'])   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']))   ? $_GET['to']   : '';
    $exportRows = array_values(array_filter(
        $state['archive'] ?? archive_records(),
        static fn ($r) => in_range($r[3], $from, $to)
    ));
    $filename = 'hospital-archive' . ($from ? '-' . $from : '') . ($to ? '-to-' . $to : '') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Archive ID', 'Patient', 'Record type', 'Archived date', 'Archived by']);
    foreach ($exportRows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Payment History CSV export (served before any HTML)
if ($role === 'Cashier' && $page === 'payment-history' && isset($_GET['export'])) {
    [$from, $to] = range_params();
    $q       = trim((string) ($_GET['q'] ?? ''));
    $method_ = (string) ($_GET['method'] ?? 'All methods');
    $cashier_= (string) ($_GET['cashier'] ?? 'All cashiers');
    $exportRows = array_values(array_filter(
        $state['payments'] ?? [],
        static fn ($p) => ($method_ === 'All methods' || strcasecmp((string) ($p['payment_method'] ?? ''), $method_) === 0)
            && ($cashier_ === 'All cashiers' || strcasecmp((string) ($p['cashier_name'] ?? ''), $cashier_) === 0)
            && in_range((string) ($p['paid_date'] ?? ($p['paid_at'] ?? '')), $from, $to)
            && row_matches([
                $p['receipt_no'] ?? '',
                $p['bill_no'] ?? '',
                $p['patient_name'] ?? '',
                $p['payment_method'] ?? '',
                $p['reference_no'] ?? '',
                $p['cashier_name'] ?? '',
            ], $q)
    ));
    $filename = 'payment-history' . ($from ? '-' . $from : '') . ($to ? '-to-' . $to : '') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Receipt No.', 'Bill No.', 'Patient', 'Amount Paid', 'Amount Received', 'Change Due', 'Method', 'Reference No.', 'Cashier', 'Date & Time']);
    foreach ($exportRows as $p) {
        fputcsv($out, [
            $p['receipt_no'] ?? '',
            $p['bill_no'] ?? '',
            $p['patient_name'] ?? '',
            format_money($p['amount_paid'] ?? 0),
            format_money($p['amount_received'] ?? 0),
            format_money($p['change_due'] ?? 0),
            $p['payment_method'] ?? 'Cash',
            $p['reference_no'] ?? '—',
            $p['cashier_name'] ?? 'Hospital Cashier',
            $p['paid_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

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
