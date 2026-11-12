<?php
declare(strict_types=1);

$q         = trim((string) ($_GET['q'] ?? ''));
$dobFilter = trim((string) ($_GET['dob'] ?? ''));
$phFilter  = trim((string) ($_GET['phone'] ?? ''));
$stFilter  = (string) ($_GET['reg_status'] ?? 'All statuses');

$allPatients = $state['patients'] ?? [];

// If Doctor, show patients they have seen or are assigned to (or all if searching)
if ($role === 'Doctor' && $q === '' && $dobFilter === '' && $phFilter === '') {
    $docName = current_user_name('Doctor');
    $docPts  = [];
    foreach ($state['appointments'] ?? [] as $a) {
        if (strcasecmp((string) ($a[5] ?? ''), $docName) === 0) {
            $docPts[mb_strtolower((string) ($a[1] ?? ''))] = true;
        }
    }
    foreach ($state['admissions'] ?? [] as $ad) {
        if (strcasecmp((string) ($ad['doctor_name'] ?? ($ad[4] ?? '')), $docName) === 0) {
            $docPts[mb_strtolower((string) ($ad['patient_name'] ?? ($ad[0] ?? '')))] = true;
        }
    }
    foreach ($state['records'] ?? [] as $ptKey => $rList) {
        foreach ($rList as $r) {
            if (strcasecmp((string) ($r['doctor'] ?? ''), $docName) === 0) {
                $docPts[mb_strtolower((string) $ptKey)] = true;
            }
        }
    }
    if (!empty($docPts)) {
        $filteredByDoc = array_values(array_filter($allPatients, static fn ($p) => isset($docPts[mb_strtolower((string) ($p[0] ?? ''))])));
        if (!empty($filteredByDoc)) {
            $allPatients = $filteredByDoc;
        }
    }
}

$filtered = array_values(array_filter($allPatients, static function (array $p) use ($q, $dobFilter, $phFilter, $stFilter): bool {
    $isProv    = is_provisional_patient($p);
    $regStatus = $isProv ? 'Provisional' : 'Registered';
    $mrn       = patient_mrn_display($p, '');

    if ($stFilter !== 'All statuses' && strcasecmp($regStatus, $stFilter) !== 0) {
        return false;
    }
    if ($dobFilter !== '') {
        $pDobIso = strtotime((string) ($p[1] ?? '')) !== false ? date('Y-m-d', strtotime((string) $p[1])) : '';
        if ($pDobIso !== $dobFilter && mb_stripos((string) ($p[1] ?? ''), $dobFilter) === false) {
            return false;
        }
    }
    if ($phFilter !== '') {
        $ptPhone = preg_replace('/\D+/', '', (string) ($p[2] ?? ''));
        $cleanFilter = preg_replace('/\D+/', '', $phFilter);
        if ($cleanFilter !== '' && !str_contains($ptPhone, $cleanFilter)) {
            return false;
        }
    }
    if ($q !== '') {
        return row_matches([
            $p[0] ?? '',
            $mrn,
            $p[1] ?? '',
            $p[2] ?? '',
            $p[7] ?? '',
            $p['contact_name'] ?? '',
            $p['contact_phone'] ?? '',
            $regStatus
        ], $q);
    }
    return true;
}));

$paged = paginate_rows($filtered, 10);

$actionsHtml = '';
if ($role === 'Staff') {
    $actionsHtml = '<div style="display:flex; gap:10px; flex-wrap:wrap;">'
        . button('Register patient', [
            'icon' => 'plus',
            'href' => url(['page' => 'patients', 'modal' => 'Register patient', 'q' => $q, 'dob' => $dobFilter]),
        ])
        . '</div>';
}
?>
<?= page_title(
    'Patients',
    $role === 'Staff'
        ? 'Search by name or birthdate before creating a record. Issue MRNs only at full registration.'
        : 'Search patients you have seen or are assigned to, and open their clinical chart.',
    $actionsHtml
) ?>

<?php if ($role === 'Staff' && ($q !== '' || $dobFilter !== '') && count($filtered) > 0): ?>
    <div style="background:#fffbeb; border:1px solid #fcd34d; border-radius:10px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:12px; font-size:13px; color:#92400e;">
        <div>
            <strong>⚠️ Likely Patient Match Found (<?= count($filtered) ?> record<?= count($filtered) === 1 ? '' : 's' ?>):</strong>
            Always verify if the patient is already in the list below before creating a new record to prevent duplicates.
        </div>
    </div>
<?php endif; ?>

<?php
$toolbar = toolbar_open('patients')
    . search_box('Search by name or MRN...', 'q', $q)
    . '<div class="input-wrap" style="min-width:145px;"><input type="date" name="dob" value="' . e($dobFilter) . '" title="Filter by Birthdate" style="height:42px; padding:0 10px; border:1px solid var(--gray-200); border-radius:8px; font-size:13px;"></div>'
    . '<div class="input-wrap" style="min-width:130px;"><input type="text" name="phone" value="' . e($phFilter) . '" placeholder="Phone..." title="Filter by Phone" style="height:42px; padding:0 10px; border:1px solid var(--gray-200); border-radius:8px; font-size:13px;"></div>'
    . select_field('', 'reg_status', ['All statuses', 'Registered', 'Provisional'], $stFilter)
    . filter_button('Search')
    . (($q !== '' || $dobFilter !== '' || $phFilter !== '' || $stFilter !== 'All statuses')
        ? '<a class="btn btn-ghost" href="' . e(url(['page' => 'patients'])) . '"><span>Reset</span></a>'
        : '')
    . toolbar_close();

echo table_open([
    'Patient Name',
    'MRN',
    'Status',
    'Birthdate & Age',
    'Sex',
    'Phone Number',
    'Address',
    'Actions',
], $toolbar);

foreach ($paged['rows'] as $p):
    $isProv     = is_provisional_patient($p);
    $regStatus  = $isProv ? 'Provisional' : 'Registered';
    $mrn        = patient_mrn_display($p, '');
    $phVer      = !empty($p['phone_verified']);
    $isStale    = is_stale_provisional($p);
    $ptKey      = (string) ($p[8] ?? $p[0]);
    $hasNoPhone = !empty($p['no_phone']) || empty($p[2]) || $p[2] === '—';
?>
    <tr>
        <td>
            <div class="cell-user">
                <?= mini_avatar((string) $p[0]) ?>
                <div>
                    <strong><?= e((string) $p[0]) ?></strong>
                    <?php if (!empty($p['email'])): ?>
                        <small style="display:block; color:var(--gray-500); font-size:11.5px;"><?= e((string) $p['email']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </td>
        <td>
            <?php if ($mrn !== ''): ?>
                <code style="font-weight:700; color:var(--teal-800); background:var(--teal-50); padding:3px 8px; border-radius:6px; font-size:12.5px;"><?= e($mrn) ?></code>
            <?php else: ?>
                <span style="color:var(--gray-400); font-size:12px;">— <em>(None)</em></span>
            <?php endif; ?>
        </td>
        <td>
            <?= badge($regStatus, status_tone($regStatus)) ?>
            <?php if ($isStale): ?>
                <?= badge('Stale', 'danger') ?>
            <?php endif; ?>
        </td>
        <td>
            <div><?= e(format_birthdate((string) $p[1])) ?></div>
            <small style="color:var(--gray-500);"><?= e(patient_age_label((string) $p[1], true)) ?></small>
        </td>
        <td><?= e((string) ($p[5] ?? '—')) ?></td>
        <td>
            <?php if ($hasNoPhone): ?>
                <div style="color:var(--gray-500); font-size:12.5px;">— (No phone)</div>
                <small style="display:inline-block; margin-top:2px; font-size:11px; font-weight:600; color:var(--gray-500);">No account (no phone)</small>
            <?php else: ?>
                <div style="font-family:monospace; font-size:13px;"><?= e((string) $p[2]) ?></div>
                <?php if ($isProv || !$phVer): ?>
                    <small style="color:var(--amber-700); font-size:11px;">Unverified</small>
                <?php else: ?>
                    <small style="color:var(--teal-700); font-size:11px;">✓ OTP Verified</small>
                <?php endif; ?>
            <?php endif; ?>
        </td>
        <td style="max-width:230px; font-size:12.5px; color:var(--gray-600);">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:6px;">
                <span><?= e($isProv ? 'Pending check-in verification' : (string) ($p[7] ?: '—')) ?></span>
                <?php if (!$isProv && in_array($role, ['Staff', 'Admin'], true)): ?>
                    <a href="<?= e(url(['page' => 'patients', 'modal' => ($role === 'Admin' ? 'Correct patient address' : 'Edit patient address'), 'patient' => $p[0]])) ?>" title="Edit residential address" style="color:var(--teal-700); display:inline-flex; align-items:center; flex-shrink:0; padding:2px;">
                        <?= icon('edit', 14) ?>
                    </a>
                <?php endif; ?>
            </div>
        </td>
        <td>
            <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                <?php if ($role === 'Staff'): ?>
                    <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'patients', 'detail' => 'profile', 'patient' => $p[0]])) ?>">
                        <span>View</span>
                    </a>
                    <?php if ($isProv): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e(url(['page' => 'patients', 'modal' => 'Complete registration', 'patient' => $p[0]])) ?>">
                            <span>Complete registration</span>
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'book', 'patient' => $p[0]])) ?>">
                        <span>Book</span>
                    </a>
                <?php else: ?>
                    <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'patients', 'detail' => 'history', 'patient' => $p[0]])) ?>">
                        <span>Open Chart</span>
                    </a>
                    <?php if (!$isProv): ?>
                        <a class="btn btn-primary btn-sm" href="<?= e(url(['page' => 'consultation', 'patient' => $p[0]])) ?>">
                            <span>Consult</span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>

<?php if (!$paged['rows']): ?>
    <tr>
        <td colspan="8"><?= filtered_empty('No matching patient records found.') ?></td>
    </tr>
<?php endif; ?>

<?= table_close($paged['label'], true, $paged['pages']) ?>
