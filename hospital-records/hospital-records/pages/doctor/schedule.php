<?php
declare(strict_types=1);

$docName = current_user_name('Doctor');
if (!str_starts_with($docName, 'Dr.')) {
    $docName = 'Dr. ' . $docName;
}

// Allow selecting doctor if testing/viewing or match logged-in doctor
$doctors = doctors_data();
$selectedDoc = trim((string) ($_GET['doc'] ?? $docName));
$foundDoc = null;
foreach ($doctors as $d) {
    if (strcasecmp($d[0], $selectedDoc) === 0) {
        $foundDoc = $d;
        break;
    }
}
if (!$foundDoc && $doctors) {
    $foundDoc = $doctors[0];
    $selectedDoc = $foundDoc[0];
}

$sched = $state['doctor_schedules'][$selectedDoc] ?? [
    'days'          => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    'start_time'    => '09:00',
    'end_time'      => '16:00',
    'slots'         => ['09:00', '09:30', '10:00', '10:30', '11:00', '13:30', '14:00', '14:30', '15:00'],
    'blocked_dates' => [],
];

$allDays  = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday'];
$allSlots = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30'];
?>
<?= page_title(
    'My Clinic Schedule & Availability',
    'Set your available clinic days, consultation time slots, and leave/blocked dates. These settings control which slots Patients and Front Desk Staff can book.'
) ?>

<div class="grid-two">
    <form class="panel" method="post" action="index.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_doctor_schedule">
        <input type="hidden" name="doctor_name" value="<?= e($selectedDoc) ?>">

        <div class="panel-head">
            <div>
                <h2>Weekly Clinic Days &amp; Time Slots</h2>
                <p>Configure recurring availability for <strong><?= e($selectedDoc) ?></strong></p>
            </div>
            <?= badge(implode(', ', $sched['days'] ?? []), 'info') ?>
        </div>

        <div class="field" style="margin-bottom:18px;">
            <span class="field-label">Available Days of the Week</span>
            <div class="sched-days-grid" style="margin-top:6px;">
                <?php foreach ($allDays as $code => $fullDay):
                    $checked = in_array($code, $sched['days'] ?? [], true);
                ?>
                    <label style="display:flex; align-items:center; gap:8px; padding:10px 12px; border:1px solid <?= $checked ? 'var(--teal-500)' : 'var(--gray-300)' ?>; background:<?= $checked ? 'var(--teal-50)' : '#fff' ?>; border-radius:8px; cursor:pointer; font-weight:600; font-size:13px;">
                        <input type="checkbox" name="days[]" value="<?= e($code) ?>" <?= $checked ? 'checked' : '' ?>>
                        <span><?= e($fullDay) ?> (<?= e($code) ?>)</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="field" style="margin-bottom:18px;">
            <span class="field-label">Consultation Time Slots</span>
            <small class="field-hint" style="display:block; margin-bottom:8px;">Check the specific 30-minute consultation slots open for patient &amp; staff booking.</small>
            <div class="sched-slots-grid">
                <?php foreach ($allSlots as $sl):
                    $sChecked = in_array($sl, $sched['slots'] ?? [], true);
                ?>
                    <label style="display:flex; align-items:center; gap:6px; padding:8px 10px; border:1px solid var(--gray-300); border-radius:6px; cursor:pointer; font-size:12.5px; background:#fff;">
                        <input type="checkbox" name="slots[]" value="<?= e($sl) ?>" <?= $sChecked ? 'checked' : '' ?>>
                        <code><?= e($sl) ?></code>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="btn btn-primary" type="submit">
            <?= icon('check', 16) ?> <span>Save Weekly Schedule</span>
        </button>
    </form>

    <div class="panel">
        <div class="panel-head">
            <div>
                <h2>Leave &amp; Blocked Dates</h2>
                <p>Block specific dates for conferences, surgery, or personal leave</p>
            </div>
        </div>

        <form method="post" action="index.php" style="margin-bottom:18px; display:grid; gap:10px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add_doctor_blocked_date">
            <input type="hidden" name="doctor_name" value="<?= e($selectedDoc) ?>">
            <div class="form-grid">
                <?= field('Date to Block *', 'blocked_date', '', 'date', date('Y-m-d', strtotime('+3 days')), ['required' => true]) ?>
                <?= field('Reason (Optional)', 'reason', 'e.g. Medical Conference / Leave', 'text', '') ?>
            </div>
            <button class="btn btn-secondary" type="submit"><?= icon('plus', 15) ?> <span>Add Blocked / Leave Date</span></button>
        </form>

        <div style="display:grid; gap:8px;">
            <?php foreach ($sched['blocked_dates'] ?? [] as $bDate):
                $dStr = is_array($bDate) ? ($bDate['date'] ?? '') : (string) $bDate;
                $dRsn = is_array($bDate) ? ($bDate['reason'] ?? 'Blocked / On Leave') : 'Blocked / On Leave';
            ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px;">
                    <div>
                        <strong style="color:#991b1b; font-size:13px;"><?= e(date('M d, Y (D)', strtotime($dStr))) ?></strong>
                        <div style="font-size:11.5px; color:#b91c1c;"><?= e($dRsn) ?></div>
                    </div>
                    <?= post_button('Remove', 'remove_doctor_blocked_date', ['doctor_name' => $selectedDoc, 'blocked_date' => $dStr], ['variant' => 'secondary']) ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($sched['blocked_dates'])): ?>
                <div class="empty-state" style="padding:24px 16px;">
                    <?= icon('calendar', 22) ?>
                    <strong>No blocked or leave dates</strong>
                    <p>All recurring weekly clinic days are open for booking.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
