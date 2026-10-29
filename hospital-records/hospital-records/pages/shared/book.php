<?php
/** 3-step booking flow shared by Patient and Staff: choose doctor → pick date & time → confirm. */
$isPatientRole = ($role === 'Patient');
$deptRows      = $state['crud']['departments'] ?? [];
$deptNames     = array_column($deptRows, 0);

// ---- Reschedule mode -------------------------------------------------
$resIdx  = isset($_GET['reschedule']) ? (int) $_GET['reschedule'] : -1;
$resAppt = $state['appointments'][$resIdx] ?? null;
if ($resAppt !== null) {
    $okStatus = in_array($resAppt[3], BOOKABLE_STATUSES, true);
    $okOwner  = !$isPatientRole || $resAppt[1] === current_user_name('Patient');
    if (!$okStatus || !$okOwner) {
        $resAppt = null;
        $resIdx  = -1;
    }
}
$resMode = $resAppt !== null;

// ---- Flow state ------------------------------------------------------
$ptName = $isPatientRole ? current_user_name('Patient') : trim((string) ($_GET['patient'] ?? ''));
if ($resMode) {
    $ptName  = $resAppt[1];
    $docName = $resAppt[5];
} else {
    $docName = trim((string) ($_GET['doc'] ?? ''));
}
$doc    = doctor_by_name($docName);
$dept   = (string) ($_GET['dept'] ?? '');
$q      = trim((string) ($_GET['q'] ?? ''));
$step   = (int) ($_GET['step'] ?? 1);
$date   = (string) ($_GET['date'] ?? '');
$time   = (string) ($_GET['time'] ?? '');

$needPatient = !$isPatientRole && !in_array($ptName, array_column($state['patients'], 0), true);
if ($needPatient) {
    $ptName = '';
}
if ($step >= 2 && $step < 4 && ($doc === null || $needPatient)) {
    $step = 1;
}
if ($resMode && $step < 2) {
    $step = 2;
}
$ignoreIdx = $resMode ? $resIdx : -1;
if ($step === 3) {
    $valid = $doc !== null
        && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        && slot_available($state, $doc, $date, $time, $ignoreIdx, $ptName);
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
    $resMode ? 'Pick a new date and time for your visit with ' . ($doc[0] ?? 'your doctor') . '.' : 'Choose a doctor, pick an open time and confirm your visit.',
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
    <!-- STEP 1: department then doctor -->
    <?php
    $doctors = array_values(array_filter(
        doctors_data(),
        static fn ($d) => ($dept === '' || $d[1] === $dept) && row_matches([$d[0], $d[1], $d[2]], $q)
    ));
    ?>
    <?php if (!$isPatientRole): ?>
        <form method="get" action="index.php" class="panel bk-patient-pick">
            <input type="hidden" name="page" value="book">
            <?php if ($dept !== ''): ?><input type="hidden" name="dept" value="<?= e($dept) ?>"><?php endif; ?>
            <?= select_field('Booking for patient', 'patient', array_merge(['Select patient'], array_column($state['patients'], 0)), $ptName !== '' ? $ptName : 'Select patient') ?>
            <?= button('Select patient', ['type' => 'submit', 'variant' => 'secondary']) ?>
        </form>
    <?php endif; ?>

    <section class="panel bk-panel">
        <div class="panel-head">
            <div>
                <h2>1. Choose a department</h2>
                <p>Then pick the doctor you would like to see</p>
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
            <?= filter_button() ?>
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
                        <?= button('Select a patient first', ['variant' => 'secondary', 'type' => 'button']) ?>
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
    [$minDate, $maxDate] = booking_window();
    $monthParam = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? (string) $_GET['month'] : substr($date !== '' ? $date : $minDate, 0, 7);
    $monthStart = $monthParam . '-01';
    if ($monthStart < substr($minDate, 0, 7) . '-01') {
        $monthStart = substr($minDate, 0, 7) . '-01';
    }
    if ($monthStart > substr($maxDate, 0, 7) . '-01') {
        $monthStart = substr($maxDate, 0, 7) . '-01';
    }
    $monthKey   = substr($monthStart, 0, 7);
    $prevMonth  = date('Y-m', strtotime($monthStart . ' -1 month'));
    $nextMonth  = date('Y-m', strtotime($monthStart . ' +1 month'));
    $hasPrev    = $monthKey > substr($minDate, 0, 7);
    $hasNext    = $monthKey < substr($maxDate, 0, 7);
    $daysInMonth = (int) date('t', strtotime($monthStart));
    $lead        = (int) date('N', strtotime($monthStart)) - 1;
    $selDay      = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? day_availability($state, $doc, $date, $ignoreIdx, $ptName) : null;
    ?>
    <div class="bk-step2">
        <section class="panel bk-panel">
            <div class="bk-doctor-banner">
                <?= doctor_photo($doc[0]) ?>
                <div>
                    <strong><?= e($doc[0]) ?></strong>
                    <span><?= e($doc[2]) ?> · <?= e($doc[1]) ?></span>
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
                    $av  = day_availability($state, $doc, $ymd, $ignoreIdx, $ptName);
                    $cls = 'bk-day ' . $av['status'] . ($ymd === $date ? ' selected' : '');
                    ?>
                    <?php if ($av['status'] === 'open'): ?>
                        <a class="<?= $cls ?>" href="<?= e($link(['step' => 2, 'doc' => $doc[0], 'month' => $monthKey, 'date' => $ymd])) ?>">
                            <b><?= $day ?></b><small><?= $av['free'] ?> open</small>
                        </a>
                    <?php else: ?>
                        <span class="<?= $cls ?>" aria-disabled="true">
                            <b><?= $day ?></b><small><?= $av['status'] === 'full' ? 'Full' : ($av['status'] === 'off' ? 'Off' : '') ?></small>
                        </span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <div class="bk-legend">
                <span><i class="open"></i>Open slots</span>
                <span><i class="full"></i>Fully booked</span>
                <span><i class="off"></i>Day off / unavailable</span>
            </div>
        </section>

        <section class="panel bk-panel bk-times">
            <div class="panel-head">
                <div>
                    <h2>Available times</h2>
                    <p><?= $selDay && $selDay['status'] === 'open' ? e(date('l, F j, Y', strtotime($date))) : 'Select a highlighted day' ?></p>
                </div>
            </div>
            <?php if ($selDay && $selDay['status'] === 'open'): ?>
                <div class="bk-chips">
                    <?php foreach (booking_slots() as $s): ?>
                        <?php if (patient_has_slot($state, $ptName, $date, $s, $ignoreIdx)): ?>
                            <span class="bk-chip booked" aria-disabled="true" title="You already have an appointment at this time"><?= e(slot_label($s)) ?></span>
                        <?php elseif (slot_booked($state, $doc[0], $date, $s, $ignoreIdx)): ?>
                            <span class="bk-chip booked" aria-disabled="true"><?= e(slot_label($s)) ?></span>
                        <?php else: ?>
                            <a class="bk-chip" href="<?= e($link(['step' => 3, 'doc' => $doc[0], 'month' => $monthKey, 'date' => $date, 'time' => $s])) ?>"><?= e(slot_label($s)) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <p class="field-hint" style="padding:0 22px 18px;">Greyed-out times are already booked, or clash with another appointment you already have. Pick a time to continue.</p>
            <?php else: ?>
                <div class="empty-state" style="padding:40px 20px;">
                    <?= icon('calendar', 28) ?>
                    <strong>No day selected</strong>
                    <p>Highlighted days have open slots. Greyed-out days are fully booked or the doctor is off.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($step === 3): ?>
    <!-- STEP 3: confirmation -->
    <section class="panel bk-panel bk-confirm">
        <div class="panel-head">
            <div>
                <h2>3. Confirm your appointment</h2>
                <p>Review the details and tell us the reason for your visit</p>
            </div>
        </div>
        <form method="post" action="index.php" class="bk-confirm-body">
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
                    <div><dt>Patient</dt><dd><?= e($ptName) ?></dd></div>
                    <div><dt>Date</dt><dd><?= e(date('l, F j, Y', strtotime($date))) ?></dd></div>
                    <div><dt>Time</dt><dd><?= e(slot_label($time)) ?></dd></div>
                    <div><dt>Department</dt><dd><?= e($doc[1]) ?></dd></div>
                </dl>
            </div>

            <label class="field">
                <span class="field-label">Reason for visit</span>
                <textarea name="complaint" required placeholder="Briefly describe your symptoms or the reason for this visit..."><?= e($resMode ? ($resAppt[4] ?? '') : '') ?></textarea>
            </label>

            <div class="bk-confirm-actions">
                <?= button('Back', ['variant' => 'secondary', 'href' => $link(['step' => 2, 'doc' => $doc[0], 'month' => substr($date, 0, 7), 'date' => $date])]) ?>
                <?= button($resMode ? 'Confirm new time' : 'Confirm appointment', ['type' => 'submit']) ?>
            </div>
        </form>
    </section>

<?php else: ?>
    <!-- SUCCESS -->
    <?php
    $doneIdx = (int) ($_GET['appt'] ?? 0);
    $done    = $state['appointments'][$doneIdx] ?? null;
    ?>
    <section class="panel bk-panel bk-success">
        <span class="bk-success-icon"><?= icon('shield', 34) ?></span>
        <h2><?= !empty($_GET['rescheduled']) ? 'Appointment rescheduled' : 'Appointment confirmed!' ?></h2>
        <?php if ($done): ?>
            <p>Your visit with <strong><?= e($done[5]) ?></strong> is booked for
                <strong><?= e(date('l, F j', strtotime($done[7]))) ?></strong> at <strong><?= e(slot_label(normalize_slot((string) $done[0]))) ?></strong>.</p>
            <dl class="bk-success-meta">
                <div><dt>Patient</dt><dd><?= e($done[1]) ?></dd></div>
                <div><dt>Department</dt><dd><?= e($done[6]) ?></dd></div>
                <div><dt>Reason</dt><dd><?= e($done[4]) ?></dd></div>
            </dl>
        <?php endif; ?>
        <div class="bk-confirm-actions" style="justify-content:center;">
            <?= button('View my appointments', ['variant' => 'secondary', 'href' => url(['page' => 'appointments'])]) ?>
            <?= button('Book another', ['icon' => 'plus', 'href' => url(['page' => 'book'])]) ?>
        </div>
    </section>
<?php endif; ?>

<?= upcoming_appointments_html($state, $role) ?>
