<?php
declare(strict_types=1);

$idInfo    = resolve_patient_identity($state);
$ptName    = $idInfo['name'];
$firstName = explode(' ', $ptName)[0];
$mob       = $_GET['mobile'] ?? null;
$myPtId    = $idInfo['id'];
$ptRecord  = $idInfo['record'] ?? find_patient_record($state, $ptName);
$myMrn     = $ptRecord ? patient_mrn_display($ptRecord) : (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', (string) $myPtId, $m) ? 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT) : $myPtId);
$myDob     = format_birthdate($ptRecord[1] ?? '');
$myAge     = patient_age_label($myDob, false, '—');

// Find next upcoming appointment
$nextAppt    = null;
$nextApptIdx = null;
foreach ($state['appointments'] as $idx => $a) {
    if (matches_patient_identity($idInfo, $a[1] ?? '', $a[8] ?? null)
        && in_array($a[3] ?? '', ['Scheduled', 'Checked-in', 'In consultation', 'Confirmed', 'Waiting', 'Upcoming', 'In progress'], true)) {
        $nextAppt    = $a;
        $nextApptIdx = $idx;
        break;
    }
}

// Collect active prescriptions
$prescriptions = patient_prescriptions($state, $ptName, $myPtId);

// Find unpaid/pending bills for this patient
$unpaidBills = [];
foreach ($state['bills'] as $bIdx => $b) {
    if (matches_patient_identity($idInfo, $b[0] ?? '', null) && ($b[2] ?? '') !== 'Paid') {
        $unpaidBills[] = ['idx' => $bIdx, 'row' => $b, 'norm' => normalize_bill_row($b, $bIdx)];
    }
}

// Find unlinked patients registered with the current user's phone number
$unlinkedPatients = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = db()->prepare('SELECT phone FROM accounts WHERE user_id = :uid LIMIT 1');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $currAccPhone = (string) $stmt->fetchColumn();
    if ($currAccPhone !== '') {
        $unlinkedPatients = db_get_unlinked_patients_by_phone($currAccPhone, (string) $_SESSION['user_id']);
    }
}
?>
<div class="pt-greeting-banner">
    <div>
        <p class="eyebrow">Patient Portal · <?= e(str_starts_with($myMrn, 'MRN-') ? $myMrn : 'MRN ' . $myMrn) ?><?= $myDob !== '—' ? ' · Birthday: ' . e($myDob) . ' (' . e($myAge) . ')' : '' ?></p>
        <h1>Good day, <?= e($firstName) ?> 👋</h1>
        <p>Welcome to your West Metro Medical Center patient portal — view your upcoming appointments, prescriptions, and billing statements.</p>
    </div>
    <?= button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'book', 'step' => 1, 'mobile' => $mob])]) ?>
</div>

<?php if (!empty($unlinkedPatients)): ?>
    <?php foreach ($unlinkedPatients as $unlinked): ?>
        <?php
            $attemptKey = 'link_attempts_' . $unlinked['patient_id'];
            $attempts   = (int) ($_SESSION[$attemptKey] ?? 0);
            $maskedName = mask_patient_name($unlinked['full_name']);
            $uDisplayMrn = (string) ($unlinked['mrn'] ?: $unlinked['patient_id']);
            if (preg_match('/^(?:MRN|P)-?0*(\d+)$/i', $uDisplayMrn, $m)) {
                $uDisplayMrn = 'MRN-' . str_pad($m[1], 4, '0', STR_PAD_LEFT);
            }
        ?>
        <div class="pt-link-alert" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:22px; flex-wrap:wrap;" role="alert">
            <div style="display:flex; align-items:center; gap:14px; flex:1; min-width:280px;">
                <div style="width:42px; height:42px; border-radius:10px; background:#dbeafe; color:#1d4ed8; display:grid; place-items:center; flex:none;">
                    <?= icon('patient', 22) ?>
                </div>
                <div>
                    <strong style="color:#1e3a8a; font-size:14px; display:block;">Profile Linking Available</strong>
                    <p style="color:#1e40af; font-size:13px; margin:2px 0 0;">
                        We found another patient registered with your number: <strong><?= e($maskedName) ?></strong> (<?= e($uDisplayMrn) ?>).
                        <?= $attempts >= 3 ? 'Maximum verification attempts reached. Please contact hospital staff.' : 'Enter the birthdate to add this profile.' ?>
                    </p>
                </div>
            </div>
            <?php if ($attempts < 3): ?>
                <form method="post" action="index.php" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin:0;">
                    <input type="hidden" name="action" value="link_patient_profile">
                    <input type="hidden" name="patient_id" value="<?= e($unlinked['patient_id']) ?>">
                    <label for="link_bdate_<?= e($unlinked['patient_id']) ?>" class="sr-only">Birthdate</label>
                    <input type="date" id="link_bdate_<?= e($unlinked['patient_id']) ?>" name="birthdate" class="form-control" style="padding:7px 12px; font-size:13px; border-radius:8px; border:1px solid #93c5fd; background:#fff;" required>
                    <button type="submit" class="btn btn-primary" style="padding:7px 14px; font-size:13px;">Add profile</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($unpaidBills): ?>
    <?php $firstUnpaid = $unpaidBills[0]['norm']; ?>
    <div class="pt-bill-alert" role="alert">
        <div class="pt-bill-alert-icon"><?= icon('card', 22) ?></div>
        <div class="pt-bill-alert-copy">
            <strong>Unpaid bill alert · <?= e(format_money($firstUnpaid['balance'])) ?> balance due</strong>
            <p>You have <?= count($unpaidBills) ?> outstanding invoice<?= count($unpaidBills) > 1 ? 's' : '' ?> issued on <?= e($firstUnpaid['issued']) ?>.</p>
        </div>
        <?= button('View bill', ['variant' => 'secondary', 'href' => url(['page' => 'bills', 'detail' => 'bill', 'bill' => $unpaidBills[0]['idx'], 'mobile' => $mob])]) ?>
    </div>
<?php endif; ?>

<div class="pt-home-grid">
    <!-- Next Appointment Card -->
    <section class="panel pt-card">
        <div class="panel-head">
            <div>
                <h2>Upcoming appointment</h2>
                <p>Your next scheduled visit at West Metro Medical Center</p>
            </div>
            <a class="text-link" href="<?= e(url(['page' => 'appointments', 'mobile' => $mob])) ?>">All appointments <?= icon('arrow', 14) ?></a>
        </div>

        <?php if ($nextAppt): ?>
            <div class="pt-next-appt">
                <div class="pt-appt-date-box">
                    <span><?= e(strtoupper(date('M', strtotime($nextAppt[7] ?? 'today')))) ?></span>
                    <strong><?= e(date('d', strtotime($nextAppt[7] ?? 'today'))) ?></strong>
                    <small><?= e($nextAppt[0]) ?></small>
                </div>
                <div class="pt-appt-info">
                    <div class="pt-appt-top">
                        <?= badge($nextAppt[3], status_tone($nextAppt[3])) ?>
                        <span class="pt-dept-tag"><?= e($nextAppt[6] ?? 'Outpatient Clinic') ?></span>
                    </div>
                    <h3><?= e($nextAppt[2]) ?></h3>
                    <p class="pt-doctor-line"><?= icon('patient', 15) ?> <strong><?= e($nextAppt[5] ?? 'Attending Physician') ?></strong> · West Metro Medical Center</p>
                    <?php if (!empty($nextAppt[4])): ?>
                        <p class="pt-reason-line">Reason: <em><?= e($nextAppt[4]) ?></em></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:36px 20px;">
                <?= icon('calendar', 28) ?>
                <strong>No upcoming appointments</strong>
                <p>Need to see a doctor? Schedule your next visit online.</p>
                <?= button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'book', 'step' => 1, 'mobile' => $mob])]) ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Active Prescriptions -->
    <section class="panel pt-card">
        <div class="panel-head">
            <div>
                <h2>Active prescriptions</h2>
                <p>Current medications prescribed by your care team</p>
            </div>
            <a class="text-link" href="<?= e(url(['page' => 'prescriptions', 'mobile' => $mob])) ?>">View all <?= icon('arrow', 14) ?></a>
        </div>

        <div class="pt-rx-mini-list">
            <?php foreach (array_slice($prescriptions, 0, 3) as $rx): ?>
                <div class="pt-rx-mini-row">
                    <span class="pt-rx-icon"><?= icon('pill', 18) ?></span>
                    <div>
                        <strong><?= e($rx['med']) ?> · <?= e($rx['dosage']) ?></strong>
                        <p><?= e($rx['frequency']) ?> · <?= e($rx['duration']) ?></p>
                    </div>
                    <?= badge('Active', 'success') ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$prescriptions): ?>
                <div class="empty-state" style="padding:32px 20px;">
                    <?= icon('pill', 26) ?>
                    <strong>No active prescriptions</strong>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
