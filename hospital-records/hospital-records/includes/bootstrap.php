<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

const APP_NAME          = 'West Metro Medical Center';
const APP_SHORT_NAME    = 'WMMC';
const HOSPITAL_SUBTITLE = 'Metro Pacific Health · Zamboanga City';
const HOSPITAL_ADDRESS  = 'Veterans Avenue Extension, Tetuan, Zamboanga City';
const HOSPITAL_PHONE    = '0968 873 7637 • 0998 978 9694';
const HOSPITAL_WEBSITE  = 'new.westmetro.com.ph';
const HOSPITAL_EMAIL    = 'records@westmetromedical.ph';

require_once __DIR__ . '/wmmc_media.php';

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

/** Store a simulated SMS or Email notification so it displays visibly in the UI for testing. */
function push_sim_message(string $channel, string $recipient, string $message, string $linkUrl = '', string $linkLabel = ''): void
{
    if (!isset($_SESSION['sim_messages']) || !is_array($_SESSION['sim_messages'])) {
        $_SESSION['sim_messages'] = [];
    }
    array_unshift($_SESSION['sim_messages'], [
        'channel'    => $channel, // 'SMS' or 'Email'
        'recipient'  => $recipient,
        'message'    => $message,
        'link_url'   => $linkUrl,
        'link_label' => $linkLabel,
        'time'       => date('h:i A'),
    ]);
    $_SESSION['sim_messages'] = array_slice($_SESSION['sim_messages'], 0, 5);
}

/** Initials for avatars: "Dr. James Wilson" -> "DJ" (limit 2). */
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

/** Is a patient record Provisional (has no MRN yet)? */
function is_provisional_patient(?array $pt): bool
{
    if (!$pt) {
        return false;
    }
    $regStatus = (string) ($pt['registration_status'] ?? '');
    if (strcasecmp($regStatus, 'Provisional') === 0) {
        return true;
    }
    $id  = strtoupper(trim((string) ($pt[8] ?? '')));
    $mrn = strtoupper(trim((string) ($pt['mrn'] ?? '')));
    if (str_starts_with($id, 'PROV-') && ($mrn === '' || $mrn === '—')) {
        return true;
    }
    return false;
}

/** Return the display MRN for a patient, or blank ('') / fallback ('—') if Provisional. */
function patient_mrn_display(?array $pt, string $provisionalFallback = ''): string
{
    if (!$pt || is_provisional_patient($pt)) {
        return $provisionalFallback;
    }
    $mrn = trim((string) ($pt['mrn'] ?? ''));
    if ($mrn !== '' && $mrn !== '—' && !str_starts_with(strtoupper($mrn), 'PROV-')) {
        return $mrn;
    }
    $id = trim((string) ($pt[8] ?? ''));
    if ($id !== '' && !str_starts_with(strtoupper($id), 'PROV-')) {
        return $id;
    }
    return $provisionalFallback;
}

/** Is a provisional record stale (created more than $daysThreshold days ago and still not converted)? */
function is_stale_provisional(array $pt, int $daysThreshold = 30): bool
{
    if (!is_provisional_patient($pt)) {
        return false;
    }
    if (!empty($pt['is_stale'])) {
        return true;
    }
    $createdAt = trim((string) ($pt['created_at'] ?? ''));
    if ($createdAt === '') {
        return false;
    }
    $ts = strtotime($createdAt);
    if ($ts === false) {
        return false;
    }
    return ((time() - $ts) / 86400) >= $daysThreshold;
}

/**
 * Search for likely duplicate patient records by name + birthdate + phone (Section 9 Rule 1).
 * Returns array of matching patient rows.
 */
function search_duplicate_patients(array $state, string $nameQuery, string $birthdate = '', string $phone = '', string $excludeId = ''): array
{
    $matches    = [];
    $normName   = mb_strtolower(preg_replace('/\s+/', ' ', trim($nameQuery)));
    $normDob    = $birthdate !== '' ? format_birthdate($birthdate) : '';
    $normDigits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($normDigits) > 10) {
        $normDigits = substr($normDigits, -10);
    }

    foreach ($state['patients'] ?? [] as $p) {
        $pid = (string) ($p[8] ?? '');
        if ($excludeId !== '' && strcasecmp($pid, $excludeId) === 0) {
            continue;
        }
        $pName   = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) ($p[0] ?? ''))));
        $pDob    = format_birthdate((string) ($p[1] ?? ''));
        $pDigits = preg_replace('/[^0-9]/', '', (string) ($p[2] ?? ''));
        if (strlen($pDigits) > 10) {
            $pDigits = substr($pDigits, -10);
        }

        $score = 0;
        if ($normName !== '' && ($pName === $normName || str_contains($pName, $normName) || str_contains($normName, $pName))) {
            $score += 2;
        }
        if ($normDob !== '' && $normDob !== '—' && $pDob === $normDob) {
            $score += 2;
        }
        if (strlen($normDigits) >= 9 && $pDigits !== '' && substr($pDigits, -9) === substr($normDigits, -9)) {
            $score += 2;
        }

        if ($score >= 2) {
            $matches[] = $p;
        }
    }
    return $matches;
}

/** Find a patient record in $state['patients'] by name, MRN, or internal patient ID. */
function find_patient_record(array $state, string $nameOrId): ?array
{
    $q = trim($nameOrId);
    if ($q === '') {
        return null;
    }
    foreach ($state['patients'] ?? [] as $p) {
        $pName = (string) ($p[0] ?? '');
        $pId   = (string) ($p[8] ?? '');
        $pMrn  = (string) ($p['mrn'] ?? '');
        if (strcasecmp($pName, $q) === 0
            || ($pId !== '' && strcasecmp($pId, $q) === 0)
            || ($pMrn !== '' && strcasecmp($pMrn, $q) === 0)) {
            return $p;
        }
    }
    foreach ($state['users'] ?? [] as $u) {
        if (strcasecmp((string) ($u[2] ?? ''), 'Patient') === 0
            && (strcasecmp((string) ($u[0] ?? ''), $q) === 0 || strcasecmp((string) ($u[7] ?? ''), $q) === 0)) {
            $uid   = (string) ($u[7] ?? '');
            $uname = (string) ($u[0] ?? '');
            foreach ($state['patients'] ?? [] as $p) {
                if (($uid !== '' && (strcasecmp((string) ($p[8] ?? ''), $uid) === 0 || strcasecmp((string) ($p['mrn'] ?? ''), $uid) === 0))
                    || ($uname !== '' && strcasecmp((string) ($p[0] ?? ''), $uname) === 0)) {
                    return $p;
                }
            }
        }
    }
    return null;
}

/**
 * Resolve all known names and the canonical ID/MRN for a patient.
 * Returns ['id' => 'MRN-xxxx'|'PROV-xxxx', 'mrn' => 'MRN-xxxx'|'', 'is_provisional' => bool, 'name' => 'Full Name', 'names' => [...], 'record' => ?array].
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
    if ($queryId === '' && preg_match('/^(MRN|PROV|P)-\d+$/i', $queryName)) {
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

    if ($queryName !== '' && !preg_match('/^(MRN|PROV|P)-\d+$/i', $queryName)) {
        $addName($queryName);
    }

    foreach ($state['patients'] ?? [] as $p) {
        $pName = trim((string) ($p[0] ?? ''));
        $pId   = strtoupper(trim((string) ($p[8] ?? '')));
        $pMrn  = strtoupper(trim((string) ($p['mrn'] ?? '')));
        if (($queryId !== '' && (($pId !== '' && strcasecmp($pId, $queryId) === 0) || ($pMrn !== '' && strcasecmp($pMrn, $queryId) === 0)))
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

    if ($ptRec === null && $queryId !== '') {
        foreach ($state['patients'] ?? [] as $p) {
            if (strcasecmp((string) ($p[8] ?? ''), $queryId) === 0 || strcasecmp((string) ($p['mrn'] ?? ''), $queryId) === 0) {
                $ptRec = $p;
                $addName((string) ($p[0] ?? ''));
                break;
            }
        }
    }

    $canonicalName = $ptRec[0] ?? (reset($names) ?: ($queryName !== '' ? $queryName : 'Patient'));
    $canonicalId   = $queryId !== '' ? $queryId : (string) ($ptRec[8] ?? 'MRN-1001');
    $isProv        = is_provisional_patient($ptRec);
    $mrn           = $isProv ? '' : patient_mrn_display($ptRec, $canonicalId);

    return [
        'id'             => $canonicalId,
        'mrn'            => $mrn,
        'is_provisional' => $isProv,
        'name'           => (string) $canonicalName,
        'names'          => array_keys($names),
        'record'         => $ptRec,
    ];
}

/** Does a record with ($recPatientName, $recPatientId) belong to the given patient identity? */
function matches_patient_identity(array $identity, ?string $recPatientName, ?string $recPatientId = null): bool
{
    $rId = strtoupper(trim((string) ($recPatientId ?? '')));
    if ($rId !== '' && $rId !== '—') {
        if (!empty($identity['id']) && strcasecmp($rId, (string) $identity['id']) === 0) {
            return true;
        }
        if (!empty($identity['mrn']) && strcasecmp($rId, (string) $identity['mrn']) === 0) {
            return true;
        }
    }
    $rName = mb_strtolower(trim((string) ($recPatientName ?? '')));
    if ($rName !== '' && in_array($rName, $identity['names'] ?? [], true)) {
        return true;
    }
    return false;
}

/** Get all medical records written by doctors for a patient. */
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

/** Get all prescriptions written by doctors for a patient. */
function patient_prescriptions(array $state, ?string $nameOrId = null, ?string $ptId = null): array
{
    $idInfo  = resolve_patient_identity($state, $nameOrId, $ptId);
    $records = patient_medical_records($state, $idInfo['name'], $idInfo['id']);
    $out     = [];
    $seen    = [];

    $normalizeRx = static function (array $raw, string $defaultDoc = 'Attending Physician', string $defaultDate = '', string $defaultRecCode = ''): array {
        $med      = trim((string) ($raw['med'] ?? ($raw['medication'] ?? ($raw[0] ?? ''))));
        $dosage   = trim((string) ($raw['dosage'] ?? ($raw[1] ?? 'As directed')));
        $freq     = trim((string) ($raw['frequency'] ?? ($raw[2] ?? 'Once daily')));
        $duration = trim((string) ($raw['duration'] ?? ($raw[3] ?? '7 days')));
        $doc      = trim((string) ($raw['doctor'] ?? ($raw['doctor_name'] ?? $defaultDoc)));
        $date     = trim((string) ($raw['date'] ?? ($raw['prescribed_date'] ?? ($defaultDate ?: date('M d, Y')))));
        $recCode  = trim((string) ($raw['record_code'] ?? $defaultRecCode));
        $status   = trim((string) ($raw['status'] ?? 'Active'));
        $rxCode   = trim((string) ($raw['rx_code'] ?? ''));

        return [
            'med'             => $med,
            'medication'      => $med,
            'dosage'          => $dosage !== '' ? $dosage : 'As directed',
            'frequency'       => $freq !== '' ? $freq : 'Once daily',
            'duration'        => $duration !== '' ? $duration : '7 days',
            'doctor'          => $doc !== '' ? $doc : 'Attending Physician',
            'doctor_name'     => $doc !== '' ? $doc : 'Attending Physician',
            'date'            => $date,
            'prescribed_date' => $date,
            'record_code'     => $recCode,
            'rx_code'         => $rxCode,
            'status'          => $status !== '' ? $status : 'Active',
        ];
    };

    foreach ($records as $rec) {
        $recCode = (string) ($rec['id'] ?? '');
        $recDoc  = (string) ($rec['doctor'] ?? 'Attending Physician');
        $recDate = (string) ($rec['date'] ?? date('M d, Y'));
        foreach ($rec['prescriptions'] ?? [] as $rx) {
            $normalized = $normalizeRx(is_array($rx) ? $rx : ['med' => (string) $rx], $recDoc, $recDate, $recCode);
            if ($normalized['med'] === '' || $normalized['med'] === 'Select medication') {
                continue;
            }
            $key = strtolower($normalized['med'] . '|' . $normalized['dosage'] . '|' . $normalized['frequency'] . '|' . $normalized['record_code']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $normalized;
            }
        }
    }

    foreach ($state['prescriptions'] ?? [] as $rxRow) {
        if (!is_array($rxRow)) {
            continue;
        }
        if (matches_patient_identity($idInfo, (string) ($rxRow['patient_name'] ?? ''), (string) ($rxRow['patient_id'] ?? ''))) {
            $normalized = $normalizeRx($rxRow);
            if ($normalized['med'] === '' || $normalized['med'] === 'Select medication') {
                continue;
            }
            $key = strtolower($normalized['med'] . '|' . $normalized['dosage'] . '|' . $normalized['frequency'] . '|' . $normalized['record_code']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $normalized;
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

/** Parse currency string (e.g. "₱1,450.00" or "$1,450.00") or number into float. */
function parse_money(mixed $val): float
{
    if (is_int($val) || is_float($val)) {
        return round((float) $val, 2);
    }
    $clean = preg_replace('/[^0-9.\-]/', '', (string) $val);
    return round((float) ($clean !== '' ? $clean : 0), 2);
}

/** Format float into Philippine Peso "₱1,450.00". */
function format_money(float|int|string $val): string
{
    return '₱' . number_format(parse_money($val), 2);
}

/** Look up a price from the hospital Price List ($state['price_list']). */
function price_list_lookup(array $state, string $itemName, string $category = '', float $fallback = 650.00): float
{
    $q = mb_strtolower(trim($itemName));
    foreach ($state['price_list'] ?? [] as $item) {
        if (($item['status'] ?? 'Active') !== 'Active') {
            continue;
        }
        $iName = mb_strtolower(trim((string) ($item['name'] ?? '')));
        $iCat  = mb_strtolower(trim((string) ($item['category'] ?? '')));
        if ($iName === $q || ($q !== '' && (str_contains($iName, $q) || str_contains($q, $iName)))) {
            if ($category === '' || $iCat === mb_strtolower($category)) {
                return parse_money($item['price'] ?? $fallback);
            }
        }
    }
    return $fallback;
}

/** Generate default itemized charges summing to $total when a bill does not yet have custom line items. */
function default_bill_items(float $total): array
{
    $total = max(0.0, round($total, 2));
    if ($total <= 0) {
        return [['Medical consultation & hospital services', 1, '₱0.00', '₱0.00']];
    }
    $consult = round($total * 0.65, 2);
    $lab     = round($total - $consult, 2);
    if ($lab <= 0) {
        return [['Outpatient consultation & clinical services', 1, format_money($total), format_money($total)]];
    }
    return [
        ['Outpatient specialist consultation fee', 1, format_money($consult), format_money($consult)],
        ['Clinical laboratory & diagnostic panel', 1, format_money($lab), format_money($lab)],
    ];
}

/** Normalize a bill row into a consistent array + associative info. */
function normalize_bill_row(array $b, int $idx = 0): array
{
    $patient   = (string) ($b[0] ?? ($b['patient'] ?? 'Patient'));
    $total     = parse_money($b[1] ?? ($b['total'] ?? 0));
    $rawStatus = (string) ($b[2] ?? ($b['status'] ?? 'Unpaid'));
    $issued    = (string) ($b[3] ?? ($b['issued'] ?? date('M d, Y')));
    $billNo    = !empty($b[4]) ? (string) $b[4] : (!empty($b['bill_no']) ? (string) $b['bill_no'] : ('BILL-' . str_pad((string) (1001 + $idx), 4, '0', STR_PAD_LEFT)));
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

    $items = (!empty($b[6]) && is_array($b[6])) ? $b[6] : ((!empty($b['items']) && is_array($b['items'])) ? $b['items'] : default_bill_items($total));
    $mrn   = (string) ($b['mrn'] ?? ($b[7] ?? ''));

    return [
        0          => $patient,
        1          => format_money($total),
        2          => $status,
        3          => $issued,
        4          => $billNo,
        5          => $paid,
        6          => $items,
        7          => $mrn,
        'patient'  => $patient,
        'mrn'      => $mrn,
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
    if (($_SESSION['state']['_v'] ?? 0) < 14) {
        $_SESSION['state']['_v'] = 14;
    }
    // Rule: Provisional accounts are only allowed until 30 days, after 30 days they are archived.
    if (!empty($_SESSION['state']['patients'])) {
        $_SESSION['state']['patients'] = array_values(array_filter(
            $_SESSION['state']['patients'],
            static fn($p) => !is_stale_provisional($p, 30) && (($p['status'] ?? '') !== 'Archived')
        ));
    }
    $_SESSION['state'] += [
        'patients'         => patients_data(),
        'users'            => seed_users(),
        'crud'             => seed_crud(),
        'beds'             => seed_beds(),
        'price_list'       => seed_price_list(),
        'doctor_schedules' => seed_doctor_schedules(),
        'admissions'       => seed_admissions(),
        'bills'            => bills_data(),
        'payments'         => [],
        'discharges'       => seed_discharges(),
        'receipts'         => [],
        'roles'            => seed_roles(),
        'notifications'    => seed_notifications(),
        'appointments'     => seed_appointments(),
        'records'          => seed_medical_records(),
        'lab_results'      => seed_lab_results(),
        'archive'          => archive_records(),
        'audit'            => audit_rows(),
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
