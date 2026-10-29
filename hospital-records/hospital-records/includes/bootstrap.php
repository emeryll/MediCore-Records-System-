<?php
declare(strict_types=1);

session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

const APP_NAME = 'MediCore';

/** Escape output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an app URL from query params (empty values are dropped). */
function url(array $params = []): string
{
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

/** Current URL with some query params overridden. */
function url_with(array $override): string
{
    return url(array_merge($_GET, $override));
}

function redirect(string $to): never
{
    if (isset($_SESSION['state']) && is_array($_SESSION['state']) && function_exists('db_save_state')) {
        db_save_state($_SESSION['state']);
    }
    header('Location: ' . $to);
    exit;
}

function flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): ?string
{
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/** Initials for avatars: "Dr. James Wilson" -> "DJ" (limit 2) or "ER". */
function initials(string $name, int $limit = 2): string
{
    $out = '';
    foreach (preg_split('/\s+/', trim($name)) ?: [] as $word) {
        $out .= mb_substr($word, 0, 1);
    }
    return mb_strtoupper(mb_substr($out, 0, $limit));
}

/** Format a birthdate string (e.g. '1995-10-14' or 'Oct 14, 1995') into 'M d, Y' or '—'. */
function format_birthdate(?string $bdate): string
{
    $bdate = trim((string) $bdate);
    if ($bdate === '' || $bdate === '—' || $bdate === '-') {
        return '—';
    }
    $ts = strtotime($bdate);
    return $ts !== false ? date('M d, Y', $ts) : $bdate;
}

/** Calculate exact age in years from a birthdate string, or null if not set. */
function patient_age(?string $bdate): ?int
{
    $bdate = trim((string) $bdate);
    if ($bdate === '' || $bdate === '—' || $bdate === '-') {
        return null;
    }
    $ts = strtotime($bdate);
    if ($ts === false) {
        return null;
    }
    try {
        $dob = new DateTimeImmutable(date('Y-m-d', $ts));
        $now = new DateTimeImmutable('today');
        return max(0, $dob->diff($now)->y);
    } catch (Throwable) {
        return max(0, (int) floor((time() - $ts) / (365.25 * 86400)));
    }
}

/** Human-readable age string (e.g. '29 yrs' when $short=true, or '29 years old' when $short=false). */
function patient_age_label(?string $bdate, bool $short = false, string $fallback = '—'): string
{
    $age = patient_age($bdate);
    if ($age === null) {
        return $fallback;
    }
    return $short ? ($age . ' ' . ($age === 1 ? 'yr' : 'yrs')) : ($age . ' ' . ($age === 1 ? 'year old' : 'years old'));
}

/** Find a patient record in $state['patients'] by name or patient ID. */
function find_patient_record(array $state, string $nameOrId): ?array
{
    $q = trim($nameOrId);
    if ($q === '') {
        return null;
    }
    foreach ($state['patients'] ?? [] as $p) {
        if (strcasecmp((string) ($p[0] ?? ''), $q) === 0 || strcasecmp((string) ($p[8] ?? ''), $q) === 0) {
            return $p;
        }
    }
    // Also check if $q matches a patient user account's name or ID, then resolve their patient record
    foreach ($state['users'] ?? [] as $u) {
        if (strcasecmp((string) ($u[2] ?? ''), 'Patient') === 0
            && (strcasecmp((string) ($u[0] ?? ''), $q) === 0 || strcasecmp((string) ($u[7] ?? ''), $q) === 0)) {
            $uid   = (string) ($u[7] ?? '');
            $uname = (string) ($u[0] ?? '');
            foreach ($state['patients'] ?? [] as $p) {
                if (($uid !== '' && strcasecmp((string) ($p[8] ?? ''), $uid) === 0)
                    || ($uname !== '' && strcasecmp((string) ($p[0] ?? ''), $uname) === 0)) {
                    return $p;
                }
            }
        }
    }
    return null;
}

/**
 * Resolve all known names and the canonical P-xxxx ID for a patient (by name, ID, or current Patient session).
 * Returns ['id' => 'P-xxxx', 'name' => 'Full Name', 'names' => ['full name', ...], 'record' => ?array].
 */
function resolve_patient_identity(array $state, ?string $nameOrId = null, ?string $explicitPtId = null): array
{
    $sessionRole = $_SESSION['role'] ?? '';
    $sessionUid  = ($sessionRole === 'Patient' && !empty($_SESSION['user_id'])) ? trim((string) $_SESSION['user_id']) : '';
    $sessionName = ($sessionRole === 'Patient' && !empty($_SESSION['user_name'])) ? trim((string) $_SESSION['user_name']) : '';

    $queryName = trim((string) ($nameOrId ?? ''));
    $queryId   = strtoupper(trim((string) ($explicitPtId ?? '')));

    if ($queryName === '' && $sessionRole === 'Patient') {
        $queryName = $sessionName !== '' ? $sessionName : current_user_name('Patient');
    }
    if ($queryId === '' && $sessionUid !== '' && ($nameOrId === null || strcasecmp($queryName, $sessionName) === 0)) {
        $queryId = strtoupper($sessionUid);
    }
    if ($queryId === '' && preg_match('/^P-\d+$/i', $queryName)) {
        $queryId = strtoupper($queryName);
    }

    $names  = [];
    $ptRec  = null;
    $addName = static function (string $n) use (&$names): void {
        $n = trim($n);
        if ($n !== '' && $n !== '—') {
            $names[mb_strtolower($n)] = $n;
        }
    };

    if ($queryName !== '' && !preg_match('/^P-\d+$/i', $queryName)) {
        $addName($queryName);
    }

    foreach ($state['patients'] ?? [] as $p) {
        $pName = trim((string) ($p[0] ?? ''));
        $pId   = strtoupper(trim((string) ($p[8] ?? '')));
        if (($queryId !== '' && $pId !== '' && strcasecmp($pId, $queryId) === 0)
            || ($pName !== '' && isset($names[mb_strtolower($pName)]))) {
            $ptRec ??= $p;
            if ($queryId === '' && $pId !== '') {
                $queryId = $pId;
            }
            $addName($pName);
        }
    }

    foreach ($state['users'] ?? [] as $u) {
        if (strcasecmp((string) ($u[2] ?? ''), 'Patient') !== 0) {
            continue;
        }
        $uName = trim((string) ($u[0] ?? ''));
        $uId   = strtoupper(trim((string) ($u[7] ?? '')));
        if (($queryId !== '' && $uId !== '' && strcasecmp($uId, $queryId) === 0)
            || ($uName !== '' && isset($names[mb_strtolower($uName)]))) {
            if ($queryId === '' && $uId !== '') {
                $queryId = $uId;
            }
            $addName($uName);
        }
    }

    // Second pass over $state['patients'] in case user account matched a patient ID
    if ($ptRec === null && $queryId !== '') {
        foreach ($state['patients'] ?? [] as $p) {
            if (strcasecmp((string) ($p[8] ?? ''), $queryId) === 0) {
                $ptRec = $p;
                $addName((string) ($p[0] ?? ''));
                break;
            }
        }
    }

    $canonicalName = $ptRec[0] ?? (reset($names) ?: ($queryName !== '' ? $queryName : 'Patient'));
    $canonicalId   = $queryId !== '' ? $queryId : (string) ($ptRec[8] ?? 'P-1001');

    return [
        'id'     => $canonicalId,
        'name'   => (string) $canonicalName,
        'names'  => array_keys($names),
        'record' => $ptRec,
    ];
}

/** Does a record with ($recPatientName, $recPatientId) belong to the given patient identity? */
function matches_patient_identity(array $identity, ?string $recPatientName, ?string $recPatientId = null): bool
{
    $rId = strtoupper(trim((string) ($recPatientId ?? '')));
    if ($rId !== '' && $rId !== '—' && !empty($identity['id']) && strcasecmp($rId, (string) $identity['id']) === 0) {
        return true;
    }
    $rName = mb_strtolower(trim((string) ($recPatientName ?? '')));
    if ($rName !== '' && in_array($rName, $identity['names'] ?? [], true)) {
        return true;
    }
    return false;
}

/** Get all medical records written by doctors for a patient (matched by ID and/or name, sorted newest first). */
function patient_medical_records(array $state, ?string $nameOrId = null, ?string $ptId = null): array
{
    $idInfo = resolve_patient_identity($state, $nameOrId, $ptId);
    $out    = [];
    $seen   = [];
    foreach ($state['records'] ?? [] as $keyName => $recList) {
        if (!is_array($recList)) {
            continue;
        }
        foreach ($recList as $rec) {
            $recCode = (string) ($rec['id'] ?? '');
            $rPtId   = (string) ($rec['patient_id'] ?? '');
            $rPtName = (string) ($rec['patient_name'] ?? $keyName);
            if (matches_patient_identity($idInfo, $rPtName, $rPtId) || matches_patient_identity($idInfo, (string) $keyName, null)) {
                $dedupKey = $recCode !== '' ? $recCode : md5(json_encode($rec));
                if (!isset($seen[$dedupKey])) {
                    $seen[$dedupKey] = true;
                    $out[] = $rec;
                }
            }
        }
    }
    return $out;
}

/** Get all prescriptions written by doctors for a patient (from both medical_records and $state['prescriptions']). */
function patient_prescriptions(array $state, ?string $nameOrId = null, ?string $ptId = null): array
{
    $idInfo  = resolve_patient_identity($state, $nameOrId, $ptId);
    $records = patient_medical_records($state, $idInfo['name'], $idInfo['id']);
    $out     = [];
    $seen    = [];
    foreach ($records as $rec) {
        $recCode = (string) ($rec['id'] ?? '');
        foreach ($rec['prescriptions'] ?? [] as $rx) {
            $med = trim((string) ($rx[0] ?? ''));
            if ($med === '' || $med === 'Select medication') {
                continue;
            }
            $key = strtolower($recCode . '|' . $med . '|' . ($rx[1] ?? '') . '|' . ($rx[2] ?? ''));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = [
                    'med'         => $med,
                    'dosage'      => (string) ($rx[1] ?? 'As directed'),
                    'frequency'   => (string) ($rx[2] ?? 'Once daily'),
                    'duration'    => (string) ($rx[3] ?? '30 days'),
                    'doctor'      => (string) ($rec['doctor'] ?? 'Attending Physician'),
                    'date'        => (string) ($rec['date'] ?? date('M d, Y')),
                    'record_code' => $recCode,
                    'status'      => (string) ($rec['status'] ?? 'Active'),
                ];
            }
        }
    }
    foreach ($state['prescriptions'] ?? [] as $rxRow) {
        if (matches_patient_identity($idInfo, (string) ($rxRow['patient_name'] ?? ''), (string) ($rxRow['patient_id'] ?? ''))) {
            $key = strtolower(($rxRow['record_code'] ?? '') . '|' . ($rxRow['med'] ?? '') . '|' . ($rxRow['dosage'] ?? '') . '|' . ($rxRow['frequency'] ?? ''));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $rxRow;
            }
        }
    }
    return $out;
}

/** Get all lab results ordered/completed by doctors for a patient. */
function patient_lab_results(array $state, ?string $nameOrId = null, ?string $ptId = null): array
{
    $idInfo = resolve_patient_identity($state, $nameOrId, $ptId);
    $out    = [];
    foreach ($state['lab_results'] ?? [] as $lr) {
        if (matches_patient_identity($idInfo, (string) ($lr[1] ?? ''), (string) ($lr[5] ?? ''))) {
            $out[] = $lr;
        }
    }
    return $out;
}

/** Parse currency string (e.g. "$1,450.00") or number into float. */
function parse_money(mixed $val): float
{
    if (is_int($val) || is_float($val)) {
        return round((float) $val, 2);
    }
    $clean = preg_replace('/[^0-9.\-]/', '', (string) $val);
    return round((float) ($clean !== '' ? $clean : 0), 2);
}

/** Format float into "$1,450.00". */
function format_money(float|int|string $val): string
{
    return '$' . number_format(parse_money($val), 2);
}

/** Generate default itemized charges summing to $total when a bill does not yet have custom line items. */
function default_bill_items(float $total): array
{
    $total = max(0.0, round($total, 2));
    if ($total <= 0) {
        return [['Medical consultation & hospital services', 1, '$0.00', '$0.00']];
    }
    if (abs($total - 185.00) < 0.01) {
        return [
            ['Specialist consultation & clinical evaluation', 1, '$125.00', '$125.00'],
            ['Complete blood count (CBC) & lab panel', 1, '$60.00', '$60.00'],
        ];
    }
    if (abs($total - 1450.00) < 0.01) {
        return [
            ['Inpatient room & nursing care (per day)', 2, '$450.00', '$900.00'],
            ['Attending physician & ward rounds', 1, '$320.00', '$320.00'],
            ['Diagnostic laboratory & imaging panel', 1, '$230.00', '$230.00'],
        ];
    }
    $consult = round($total * 0.65, 2);
    $lab     = round($total - $consult, 2);
    if ($lab <= 0) {
        return [['Outpatient consultation & clinical services', 1, format_money($total), format_money($total)]];
    }
    return [
        ['Outpatient consultation & physician fee', 1, format_money($consult), format_money($consult)],
        ['Laboratory & diagnostic services', 1, format_money($lab), format_money($lab)],
    ];
}

/** Normalize a bill row into a consistent 7-element array + associative info. */
function normalize_bill_row(array $b, int $idx = 0): array
{
    $patient   = (string) ($b[0] ?? 'Patient');
    $total     = parse_money($b[1] ?? 0);
    $rawStatus = (string) ($b[2] ?? 'Unpaid');
    $issued    = (string) ($b[3] ?? date('M d, Y'));
    $billNo    = !empty($b[4]) ? (string) $b[4] : ('BILL-' . str_pad((string) (1001 + $idx), 4, '0', STR_PAD_LEFT));
    $paid      = isset($b[5]) && $b[5] !== '' ? parse_money($b[5]) : ($rawStatus === 'Paid' ? $total : 0.0);
    $paid      = min($total, max(0.0, $paid));
    $balance   = max(0.0, round($total - $paid, 2));

    if ($balance <= 0.009 && $total > 0) {
        $status  = 'Paid';
        $paid    = $total;
        $balance = 0.0;
    } elseif ($paid > 0.009) {
        $status = 'Partial';
    } else {
        $status = 'Unpaid';
        $paid   = 0.0;
    }

    $items = (!empty($b[6]) && is_array($b[6])) ? $b[6] : default_bill_items($total);

    return [
        0          => $patient,
        1          => format_money($total),
        2          => $status,
        3          => $issued,
        4          => $billNo,
        5          => $paid,
        6          => $items,
        'patient'  => $patient,
        'total'    => $total,
        'paid'     => $paid,
        'balance'  => $balance,
        'status'   => $status,
        'issued'   => $issued,
        'bill_no'  => $billNo,
        'items'    => $items,
    ];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && $_POST['csrf'] !== '';
}

/** Only allow redirects back into this app. */
function safe_return(?string $to): string
{
    return ($to && str_starts_with($to, 'index.php')) ? $to : 'index.php';
}

/** Database-backed hospital state (loaded from relational tables on each request). */
function &state(): array
{
    static $loaded = false;
    if (!$loaded && function_exists('db_load_state')) {
        $_SESSION['state'] = db_load_state();
        $loaded = true;
    }
    if (($_SESSION['state']['_v'] ?? 0) < 12) {
        $_SESSION['state'] = ['_v' => 12];
    }
    $_SESSION['state'] += [
        'patients'      => patients_data(),
        'users'         => seed_users(),
        'crud'          => seed_crud(),
        'admissions'    => seed_admissions(),
        'bills'         => bills_data(),
        'payments'      => [],
        'discharges'    => seed_discharges(),
        'receipts'      => [],
        'roles'         => seed_roles(),
        'notifications' => seed_notifications(),
        'appointments'  => seed_appointments(),   // doctor & staff appointment list (mutable)
        'records'       => seed_medical_records(), // patient => visit records[]
        'lab_results'   => seed_lab_results(),     // flat array of lab test rows
        'archive'       => archive_records(),
        'audit'         => audit_rows(),
    ];
    return $_SESSION['state'];
}

/**
 * Validate and store an uploaded image under uploads/<folder>/.
 * Returns ['path' => relative web path] or ['error' => message].
 */
function store_proof_image(string $field, string $folder): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return ['error' => 'Please choose an image to upload.'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        return ['error' => 'Upload failed. Please try again.'];
    }
    if ($f['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Image must be 5 MB or smaller.'];
    }
    $info    = @getimagesize($f['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) {
        return ['error' => 'Only JPG, PNG, GIF or WEBP images are allowed.'];
    }
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return ['error' => 'Could not create the upload folder.'];
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return ['error' => 'Could not save the uploaded image.'];
    }
    return ['path' => 'uploads/' . $folder . '/' . $name];
}
