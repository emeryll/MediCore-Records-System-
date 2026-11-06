<?php
declare(strict_types=1);

/** 3-step booking flow shared by Patient and Staff: choose doctor → pick date & time → confirm. */
$isPatientRole = ($role === 'Patient');
$deptRows      = $state['crud']['departments'] ?? [];
$deptNames     = array_column($deptRows, 0);

// ---- Reschedule mode -------------------------------------------------
$resIdx  = isset($_GET['reschedule']) ? (int) $_GET['reschedule'] : -1;
$resAppt = $state['appointments'][$resIdx] ?? null;
if ($resAppt !== null) {
    $okStatus = in_array($resAppt[3] ?? '', BOOKABLE_STATUSES, true);
    $okOwner  = !$isPatientRole || (strcasecmp((string) ($resAppt[1] ?? ''), current_user_name('Patient')) === 0 && can_patient_modify_appointment($resAppt));
    if (!$okStatus || !$okOwner) {
        $resAppt = null;
        $resIdx  = -1;
    }
}
$resMode = $resAppt !== null;

// ---- Flow state ------------------------------------------------------
$ptName = $isPatientRole ? current_user_name('Patient') : trim((string) ($_GET['patient'] ?? ''));
if ($resMode) {
    $ptName  = (string) $resAppt[1];
    $docName = (string) $resAppt[5];
} else {
    $docName = trim((string) ($_GET['doc'] ?? ''));
}
$doc    = doctor_by_name($docName);
$dept   = (string) ($_GET['dept'] ?? '');
$q      = trim((string) ($_GET['q'] ?? ''));
$step   = (int) ($_GET['step'] ?? 1);
$date   = (string) ($_GET['date'] ?? '');
$time   = (string) ($_GET['time'] ?? '');

// Staff patient search filters (Section 5.3: Search by name + birthdate + phone)
$ptSearchName  = trim((string) ($_GET['pt_q'] ?? ''));
$ptSearchDob   = trim((string) ($_GET['pt_dob'] ?? ''));
$ptSearchPhone = trim((string) ($_GET['pt_phone'] ?? ''));

$selectedPtRec = $ptName !== '' ? find_patient_record($state, $ptName) : null;
$needPatient   = !$isPatientRole && $selectedPtRec === null;
if ($needPatient) {
    $ptName = '';
}
if ($step >= 2 && $step < 4 && ($doc === null || $needPatient)) {
    $step = 1;
}
if ($resMode && $step < 2) {
    $step = 2;
}
$ignoreIdx  = $resMode ? $resIdx : -1;
$allowToday = !$isPatientRole;
if ($step === 3) {
    $valid = $doc !== null
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        && slot_available($state, $doc, $date, $time, $ignoreIdx, $ptName, $allowToday);
    if (!$valid) {
        $step = 2;
        $time = '';
    }
}

$link = static function (array $over) use ($isPatientRole, $ptName, $resMode, $resIdx): string {
    $base = ['page' => 'book'];
    if (!$isPatientRole && $ptName !== '') {
        $base['patient'] = $ptName;
    }
    if ($resMode) {
        $base['reschedule'] = $resIdx;
    }
    return url(array_merge($base, $over));
};

$stepLabels = [1 => 'Choose doctor', 2 => 'Date & time', 3 => 'Confirm'];
$doneUpTo   = $step === 4 ? 3 : ($resMode && $step === 2 ? 1 : $step - 1);
?>
<?= page_title(
    $resMode ? 'Reschedule appointment' : 'Book appointment',
    $resMode
        ? 'Pick a new date and time for your visit with ' . ($doc[0] ?? 'your doctor') . '.'
        : ($isPatientRole
            ? 'Choose a doctor, pick an available date and time, and state the reason for your visit.'
            : 'Search by patient name or MRN first, or register a new patient before selecting a schedule.'),
    button('Back to appointments', ['variant' => 'secondary', 'href' => url(['page' => 'appointments'])])
) ?>

<!-- Progress bar -->
<div class="bk-progress" aria-label="Booking progress">
    <?php foreach ($stepLabels as $n => $label): ?>
        <?php $state_ = $n <= $doneUpTo ? 'done' : ($n === $step ? 'active' : ''); ?>
        <div class="bk-progress-step <?= $state_ ?>">
            <span class="bk-progress-dot"><?= $n <= $doneUpTo ? '✓' : $n ?></span>
            <span class="bk-progress-label"><?= e($label) ?></span>
        </div>
        <?php if ($n < 3): ?><div class="bk-progress-bar <?= $n <= $doneUpTo ? 'filled' : '' ?>"></div><?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if ($step === 1): ?>
    <?php
    $doctors = array_values(array_filter(
        doctors_data(),
        static fn ($d) => ($dept === '' || $d[1] === $dept) && row_matches([$d[0], $d[1], $d[2]], $q)
    ));
    ?>
    <?php if (!$isPatientRole): ?>
        <!-- Staff Patient Lookup & Walk-in / Phone-in Registration Selector (Section 5.3) -->
        <section class="panel" style="margin-bottom:20px;">
            <div class="panel-head">
                <div>
                    <h2>Patient Selection (Search Before Booking)</h2>
                    <p>Search by patient name or MRN. If no match is found, register the patient below.</p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <?= button('Quick Add', [
                        'variant' => 'secondary',
                        'icon'    => 'plus',
                        'href'    => url(['page' => 'patients', 'modal' => 'Quick add provisional', 'book_after' => '1']),
                    ]) ?>
                    <?= button('Register Patient', [
                        'icon' => 'plus',
                        'href' => url(['page' => 'patients', 'modal' => 'Register patient', 'book_after' => '1']),
                    ]) ?>
                </div>
            </div>

            <?php if ($selectedPtRec):
                $selProv = is_provisional_patient($selectedPtRec);
                $selMrn  = patient_mrn_display($selectedPtRec, 'None (Provisional)');
            ?>
                <div style="background:var(--teal-50); border:1px solid var(--teal-200); border-radius:10px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <div>
                        <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--teal-800);">Selected Patient for Booking</span>
                        <div style="font-size:16px; font-weight:700; color:var(--navy-900); margin-top:2px;">
                            <?= e((string) $selectedPtRec[0]) ?>
                            <?= badge($selProv ? 'Provisional' : 'Registered', $selProv ? 'warning' : 'success') ?>
                        </div>
                        <div style="font-size:13px; color:var(--gray-600); margin-top:2px;">
                            MRN: <strong><?= e($selMrn) ?></strong> · Birthdate: <?= e(format_birthdate((string) $selectedPtRec[1])) ?> · Phone: <?= e((string) $selectedPtRec[2]) ?>
                        </div>
                    </div>
                    <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'book'])) ?>"><span>Change patient</span></a>
                </div>
            <?php else: ?>
                <form method="get" action="index.php" class="table-toolbar" style="padding:0 0 14px 0; border-bottom:1px solid var(--gray-200); margin-bottom:14px;">
                    <input type="hidden" name="page" value="book">
                    <?= search_box('Search by patient name or MRN...', 'pt_q', $ptSearchName) ?>
                    <?= filter_button('Find Patient') ?>
                </form>

                <?php
                $ptMatches = array_values(array_filter($state['patients'] ?? [], static function (array $p) use ($ptSearchName): bool {
                    if ($ptSearchName !== '' && !row_matches([$p[0] ?? '', $p['mrn'] ?? '', $p[8] ?? ''], $ptSearchName)) {
                        return false;
                    }
                    return true;
                }));
                ?>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:10px; max-height:240px; overflow-y:auto;">
                    <?php foreach (array_slice($ptMatches, 0, 9) as $pm):
                        $pmProv = is_provisional_patient($pm);
                        $pmMrn  = patient_mrn_display($pm, '—');
                    ?>
                        <div class="panel" style="padding:12px 14px; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                            <div>
                                <strong style="font-size:13.5px; display:block;"><?= e((string) $pm[0]) ?></strong>
                                <small style="color:var(--gray-600); display:block;">
                                    <?= $pmProv ? 'Provisional (No MRN)' : 'MRN: ' . e($pmMrn) ?> · <?= e(format_birthdate((string) $pm[1])) ?>
                                </small>
                                <small style="color:var(--gray-500); font-family:monospace;"><?= e((string) $pm[2]) ?></small>
                            </div>
                            <a class="btn btn-primary btn-sm" href="<?= e(url(['page' => 'book', 'patient' => $pm[0], 'dept' => $dept ?: null])) ?>"><span>Select</span></a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!$ptMatches): ?>
                    <div style="padding:16px; background:#fffbeb; border:1px solid #fcd34d; border-radius:8px; font-size:13px; color:#92400e; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                        <span>No existing patient matched. Is the patient physically present (Walk-in) or calling by phone (Phone-in)?</span>
                        <div style="display:flex; gap:8px;">
                            <a class="btn btn-secondary btn-sm" href="<?= e(url(['page' => 'patients', 'modal' => 'Quick add provisional', 'book_after' => '1'])) ?>"><span>Quick Add</span></a>
                            <a class="btn btn-primary btn-sm" href="<?= e(url(['page' => 'patients', 'modal' => 'Register patient', 'book_after' => '1'])) ?>"><span>Register Patient</span></a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="panel bk-panel">
        <div class="panel-head">
            <div>
                <h2>1. Choose a doctor</h2>
                <p>Filter by department or search by doctor name and specialty</p>
            </div>
        </div>
        <div class="appt-cats bk-dept-chips">
            <a class="appt-cat <?= $dept === '' ? 'active' : '' ?>" href="<?= e($link(['dept' => null])) ?>">All departments</a>
            <?php foreach ($deptNames as $dn): ?>
                <a class="appt-cat <?= $dept === $dn ? 'active' : '' ?>" href="<?= e($link(['dept' => $dn])) ?>"><?= e($dn) ?></a>
            <?php endforeach; ?>
        </div>
        <form method="get" action="index.php" class="bk-search-row">
            <input type="hidden" name="page" value="book">
            <?php if (!$isPatientRole && $ptName !== ''): ?><input type="hidden" name="patient" value="<?= e($ptName) ?>"><?php endif; ?>
            <?php if ($dept !== ''): ?><input type="hidden" name="dept" value="<?= e($dept) ?>"><?php endif; ?>
            <?= search_box('Search by doctor name or specialty...', 'q', $q) ?>
            <?= filter_button('Search') ?>
        </form>

        <div class="bk-doctor-grid">
            <?php foreach ($doctors as $d): ?>
                <article class="bk-doctor-card">
                    <?= doctor_photo($d[0], 'lg') ?>
                    <div class="bk-doctor-text">
                        <strong><?= e($d[0]) ?></strong>
                        <span><?= e($d[2]) ?></span>
                        <small><?= e($d[1]) ?> · <?= e($d[4]) ?></small>
                    </div>
                    <?php if ($needPatient): ?>
                        <span class="btn btn-secondary" style="opacity:0.6; cursor:not-allowed;"><span>Select patient above first</span></span>
                    <?php else: ?>
                        <?= button('Choose doctor', ['href' => $link(['step' => 2, 'doc' => $d[0]])]) ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!$doctors): ?>
            <?= filtered_empty('No doctors match your search in this department.') ?>
        <?php endif; ?>
    </section>

<?php elseif ($step === 2): ?>
    <!-- STEP 2: month calendar + time chips -->
    <?php
    [$minDate, $maxDate] = booking_window($allowToday);
    $monthParam = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? (string) $_GET['month'] : substr($date !== '' ? $date : $minDate, 0, 7);
    $monthStart = $monthParam . '-01';
    if ($monthStart < substr($minDate, 0, 7) . '-01') {
        $monthStart = substr($minDate, 0, 7) . '-01';
    }
    if ($monthStart > substr($maxDate, 0, 7) . '-01') {
        $monthStart = substr($maxDate, 0, 7) . '-01';
    }
    $monthKey    = substr($monthStart, 0, 7);
    $prevMonth   = date('Y-m', strtotime($monthStart . ' -1 month'));
    $nextMonth   = date('Y-m', strtotime($monthStart . ' +1 month'));
    $hasPrev     = $monthKey > substr($minDate, 0, 7);
    $hasNext     = $monthKey < substr($maxDate, 0, 7);
    $daysInMonth = (int) date('t', strtotime($monthStart));
    $lead        = (int) date('N', strtotime($monthStart)) - 1;
    $selDay      = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? day_availability($state, $doc, $date, $ignoreIdx, $ptName, $allowToday) : null;
    ?>
    <div class="bk-step2">
        <section class="panel bk-panel">
            <div class="bk-doctor-banner">
                <?= doctor_photo($doc[0]) ?>
                <div>
                    <strong><?= e($doc[0]) ?></strong>
                    <span><?= e($doc[2]) ?> · <?= e($doc[1]) ?> (<?= e($doc[4]) ?>)</span>
                </div>
                <?php if (!$resMode): ?>
                    <a class="text-link" href="<?= e($link(['step' => 1])) ?>">Change doctor</a>
                <?php endif; ?>
            </div>

            <div class="bk-cal-head">
                <?php if ($hasPrev): ?>
                    <a class="btn btn-secondary" href="<?= e($link(['step' => 2, 'doc' => $doc[0], 'month' => $prevMonth])) ?>">‹</a>
                <?php else: ?>
                    <span class="btn btn-secondary is-disabled">‹</span>
                <?php endif; ?>
                <strong><?= e(date('F Y', strtotime($monthStart))) ?></strong>
                <?php if ($hasNext): ?>
                    <a class="btn btn-secondary" href="<?= e($link(['step' => 2, 'doc' => $doc[0], 'month' => $nextMonth])) ?>">›</a>
                <?php else: ?>
                    <span class="btn btn-secondary is-disabled">›</span>
                <?php endif; ?>
            </div>

            <div class="bk-cal">
                <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd): ?>
                    <span class="bk-cal-dow"><?= $wd ?></span>
                <?php endforeach; ?>
                <?php for ($i = 0; $i < $lead; $i++): ?><span class="bk-cal-blank"></span><?php endfor; ?>
                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                    $ymd = $monthKey . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                    $av  = day_availability($state, $doc, $ymd, $ignoreIdx, $ptName, $allowToday);
                    $cls = 'bk-day ' . $av['status'] . ($ymd === $date ? ' selected' : '');
                    ?>
                    <?php if ($av['status'] === 'open'): ?>
                        <a class="<?= $cls ?>" href="<?= e($link(['step' => 2, 'doc' => $doc[0], 'month' => $monthKey, 'date' => $ymd])) ?>">
                            <b><?= $day ?></b><small><?= $av['free'] ?> open</small>
                        </a>
                    <?php else: ?>
                        <span class="<?= $cls ?>" aria-disabled="true" title="<?= e($av['reason'] ?? '') ?>">
                            <b><?= $day ?></b><small><?= $av['status'] === 'full' ? 'Full' : ($av['status'] === 'off' ? 'Off' : '') ?></small>
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <div class="bk-legend">
                <span><i class="open"></i>Open slots</span>
                <span><i class="full"></i>Fully booked</span>
                <span><i class="off"></i>Doctor off / leave</span>
            </div>
        </section>

        <section class="panel bk-panel bk-times">
            <div class="panel-head">
                <div>
                    <h2>Available times</h2>
                    <p><?= $selDay && $selDay['status'] === 'open' ? e(date('l, F j, Y', strtotime($date))) : 'Select a highlighted day on the calendar' ?></p>
                </div>
            </div>
            <?php if ($selDay && $selDay['status'] === 'open'): ?>
                <div class="bk-chips">
                    <?php foreach (booking_slots($doc) as $s): ?>
                        <?php if (patient_has_slot($state, $ptName, $date, $s, $ignoreIdx)): ?>
                            <span class="bk-chip booked" aria-disabled="true" title="Patient already has an appointment at this time"><?= e(slot_label($s)) ?></span>
                        <?php elseif (slot_booked($state, $doc[0], $date, $s, $ignoreIdx)): ?>
                            <span class="bk-chip booked" aria-disabled="true"><?= e(slot_label($s)) ?></span>
                        <?php else: ?>
                            <a class="bk-chip" href="<?= e($link(['step' => 3, 'doc' => $doc[0], 'month' => $monthKey, 'date' => $date, 'time' => $s])) ?>"><?= e(slot_label($s)) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <p class="field-hint" style="padding:0 22px 18px;">A patient cannot book an appointment at the same time as an existing one. Pick an open slot to continue.</p>
            <?php else: ?>
                <div class="empty-state" style="padding:40px 20px;">
                    <?= icon('calendar', 28) ?>
                    <strong>No day selected</strong>
                    <p>Highlighted days have open slots based on <?= e($doc[0]) ?>'s schedule.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($step === 3): ?>
    <!-- STEP 3: confirmation -->
    <?php
    $ptRec  = find_patient_record($state, $ptName);
    $isProv = is_provisional_patient($ptRec);
    $ptMrn  = patient_mrn_display($ptRec, 'None (Provisional — Issued at Check-in)');
    ?>
    <section class="panel bk-panel bk-confirm">
        <div class="panel-head">
            <div>
                <h2>3. Confirm appointment</h2>
                <p>Review details and state the reason for the visit</p>
            </div>
            <?php if ($isProv): ?>
                <?= badge('Provisional Patient', 'warning') ?>
            <?php endif; ?>
        </div>
        <form method="post" action="index.php" class="bk-confirm-body" onsubmit="return confirm('Confirm this appointment booking?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="book_appointment">
            <input type="hidden" name="return" value="<?= e(url(['page' => 'book'])) ?>">
            <input type="hidden" name="doctor" value="<?= e($doc[0]) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <input type="hidden" name="slot" value="<?= e($time) ?>">
            <input type="hidden" name="patient" value="<?= e($ptName) ?>">
            <?php if ($resMode): ?><input type="hidden" name="reschedule" value="<?= e((string) $resIdx) ?>"><?php endif; ?>

            <div class="bk-summary">
                <div class="bk-summary-doctor">
                    <?= doctor_photo($doc[0], 'lg') ?>
                    <div><strong><?= e($doc[0]) ?></strong><span><?= e($doc[2]) ?> · <?= e($doc[1]) ?></span></div>
                </div>
                <dl>
                    <div><dt>Patient</dt><dd><?= e($ptName) ?> <?= $isProv ? '(Provisional)' : '(' . e($ptMrn) . ')' ?></dd></div>
                    <div><dt>Date</dt><dd><?= e(date('l, F j, Y', strtotime($date))) ?></dd></div>
                    <div><dt>Time</dt><dd><?= e(slot_label($time)) ?></dd></div>
                    <div><dt>Department</dt><dd><?= e($doc[1]) ?></dd></div>
                </dl>
            </div>

            <label class="field">
                <span class="field-label">Reason for the visit *</span>
                <textarea name="complaint" required placeholder="State the symptoms or reason for this hospital visit..."><?= e($resMode ? ($resAppt[4] ?? '') : '') ?></textarea>
            </label>

            <div class="bk-confirm-actions">
                <?= button('Back', ['variant' => 'secondary', 'href' => $link(['step' => 2, 'doc' => $doc[0], 'month' => substr($date, 0, 7), 'date' => $date])]) ?>
                <?= button($resMode ? 'Confirm new time' : 'Confirm appointment', ['type' => 'submit']) ?>
            </div>
        </form>
    </section>

<?php else: ?>
    <!-- STEP 4: SUCCESS -->
    <?php
    $aIdx    = (int) ($_GET['appt'] ?? 0);
    $booked  = $state['appointments'][$aIdx] ?? ($state['appointments'][0] ?? null);
    $wasRes  = !empty($_GET['rescheduled']);
    $bPtRec  = $booked ? find_patient_record($state, (string) $booked[1]) : null;
    $bIsProv = is_provisional_patient($bPtRec);
    ?>
    <section class="panel bk-panel bk-confirm">
        <div class="bk-confirm-body" style="text-align:center; padding:32px 24px;">
            <div style="width:56px; height:56px; border-radius:50%; background:var(--teal-50); color:var(--teal-700); display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px;">
                <?= icon('shield', 28) ?>
            </div>
            <h2><?= $wasRes ? 'Appointment Rescheduled!' : 'Appointment Confirmed!' ?></h2>
            <?php if ($booked): ?>
                <p style="max-width:520px; margin:8px auto 20px; color:var(--gray-600);">
                    <strong><?= e((string) $booked[1]) ?></strong> is scheduled with <strong><?= e((string) $booked[5]) ?></strong>
                    (<?= e((string) $booked[6]) ?>) on <strong><?= e((string) $booked[7]) ?></strong> at <strong><?= e(slot_label(normalize_slot((string) $booked[0]))) ?></strong>.
                </p>
                <?php if ($bIsProv): ?>
                    <div style="background:#fffbeb; border:1px solid #fcd34d; color:#92400e; padding:12px 16px; border-radius:10px; max-width:520px; margin:0 auto 20px; font-size:13px;">
                        📱 <strong>Phone-In Provisional Record:</strong> An SMS appointment confirmation has been sent. When the patient arrives for check-in, staff will complete full registration, assign their MRN, and send their account activation invite.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div style="display:flex; justify-content:center; gap:10px;">
                <?= button('View Appointments', ['href' => url(['page' => 'appointments'])]) ?>
                <?= button('Book Another', ['variant' => 'secondary', 'href' => url(['page' => 'book'])]) ?>
            </div>
        </div>
    </section>
<?php endif; ?>
