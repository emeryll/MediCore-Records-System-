<?php
$ptName = current_user_name('Patient');
$tab    = ($_GET['tab'] ?? 'Upcoming') === 'Past' ? 'Past' : 'Upcoming';
$mob    = $_GET['mobile'] ?? null;

$upcoming = [];
$past     = [];
foreach ($state['appointments'] as $idx => $a) {
    if ($a[1] !== $ptName) {
        continue;
    }
    if (in_array($a[3], ['Confirmed', 'Waiting', 'Upcoming', 'In progress'], true)) {
        $upcoming[$idx] = $a;
    } else {
        $past[$idx] = $a;
    }
}

$activeList = $tab === 'Upcoming' ? $upcoming : $past;
?>
<?= page_title(
    'My Appointments',
    'Manage your upcoming visits or book a new appointment with a doctor.',
    button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'appointments', 'tab' => $tab, 'modal' => 'Book appointment', 'mobile' => $mob])])
) ?>

<div class="appt-cats">
    <a class="appt-cat <?= $tab === 'Upcoming' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'appointments', 'tab' => 'Upcoming', 'mobile' => $mob])) ?>">
        Upcoming
        <span class="appt-cat-count"><?= count($upcoming) ?></span>
    </a>
    <a class="appt-cat <?= $tab === 'Past' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'appointments', 'tab' => 'Past', 'mobile' => $mob])) ?>">
        Past & Cancelled
        <span class="appt-cat-count"><?= count($past) ?></span>
    </a>
</div>

<div class="pt-appt-cards">
    <?php foreach ($activeList as $idx => $a): ?>
        <article class="panel pt-appt-card">
            <div class="pt-appt-date-box">
                <span><?= e(strtoupper(date('M', strtotime($a[7] ?? 'today')))) ?></span>
                <strong><?= e(date('d', strtotime($a[7] ?? 'today'))) ?></strong>
                <small><?= e($a[0]) ?></small>
            </div>

            <div class="pt-appt-info">
                <div class="pt-appt-top">
                    <?= badge($a[3], status_tone($a[3])) ?>
                    <span class="pt-dept-tag"><?= e($a[6] ?? 'Cardiology') ?></span>
                </div>
                <h3><?= e($a[2]) ?></h3>
                <p class="pt-doctor-line"><?= icon('patient', 15) ?> <strong><?= e($a[5] ?? 'Attending Physician') ?></strong></p>
                <?php if (!empty($a[4])): ?>
                    <p class="pt-reason-line">Reason: <em><?= e($a[4]) ?></em></p>
                <?php endif; ?>
            </div>

            <div class="pt-appt-actions">
                <?php if ($tab === 'Upcoming' && in_array($a[3], ['Confirmed', 'Waiting', 'Upcoming'], true)): ?>
                    <?= button('Reschedule', ['variant' => 'secondary', 'icon' => 'calendar', 'href' => url(['page' => 'book', 'step' => 2, 'reschedule' => $idx])]) ?>
                    <?= post_button('Cancel', 'cancel_appt', ['idx' => (string) $idx], ['variant' => 'secondary', 'class' => 'btn-cancel']) ?>
                <?php elseif ($a[3] === 'Completed'): ?>
                    <a class="text-link" href="<?= e(url(['page' => 'history', 'mobile' => $mob])) ?>">View visit summary <?= icon('arrow', 14) ?></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$activeList): ?>
        <?= filtered_empty($tab === 'Upcoming' ? 'You have no upcoming appointments.' : 'No past appointments found.') ?>
    <?php endif; ?>
</div>
