<?php
$q    = trim((string) ($_GET['q'] ?? ''));
$dept = (string) ($_GET['dept'] ?? 'All departments');

$departments = $state['crud']['departments'] ?? [];
$doctors     = doctors_data();

// Include any department referenced by a doctor even if not yet in $state['crud']['departments']
$knownDepts = array_column($departments, 0);
foreach ($doctors as $d) {
    if (!in_array($d[1], $knownDepts, true)) {
        $departments[] = [$d[1], $d[0], '1', 'Main Wing'];
        $knownDepts[]  = $d[1];
    }
}
$deptNames = array_column($departments, 0);

// Filter doctors by search term (name, specialty, department) and department
$matches = array_values(array_filter(
    $doctors,
    static fn ($d) => ($dept === 'All departments' || $d[1] === $dept) && row_matches([$d[0], $d[1], $d[2]], $q)
));

// Group by department, keeping department info (head, location)
$groups = [];
foreach ($departments as $dp) {
    $docs = array_values(array_filter($matches, static fn ($d) => $d[1] === $dp[0]));
    $deptHit = $q !== '' && mb_stripos($dp[0], $q) !== false;
    if ($dept !== 'All departments' && $dp[0] !== $dept) {
        continue;
    }
    // Department name matched the search: show all its doctors
    if ($deptHit && !$docs) {
        $docs = array_values(array_filter($doctors, static fn ($d) => $d[1] === $dp[0]));
    }
    if ($docs || ($q === '' && $dept === 'All departments')) {
        $groups[] = ['dept' => $dp, 'doctors' => $docs];
    }
}
$shown = array_sum(array_map(static fn ($g) => count($g['doctors']), $groups));

$toolbar = toolbar_open('doctors')
    . search_box('Search specialist, doctor, or department...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'dept', array_merge(['All departments'], $deptNames), $dept)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Find a Doctor',
    'Browse hospital departments and doctors, or search for the specialist you need, then book directly.',
    button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'doctors', 'modal' => 'Book appointment'])])
) ?>

<div class="table-card" style="margin-bottom:20px;">
    <?= $toolbar ?>
</div>

<?php foreach ($groups as $g): [$dName, $dHead, $dStaff, $dLoc] = $g['dept']; ?>
    <section class="panel pt-dept-panel">
        <div class="panel-head">
            <div>
                <h2><?= e($dName) ?></h2>
                <p>Led by <?= e($dHead) ?> · <?= e($dLoc) ?> · <?= e((string) $dStaff) ?> staff</p>
            </div>
            <?= button('Book in ' . $dName, ['variant' => 'secondary', 'href' => url(['page' => 'doctors', 'modal' => 'Book appointment', 'dept' => $dName])]) ?>
        </div>

        <?php if ($g['doctors']): ?>
            <div class="pt-doctor-grid">
                <?php foreach ($g['doctors'] as $d): ?>
                    <article class="pt-doctor-card">
                        <div class="pt-doctor-top">
                            <span class="avatar"><?= e(initials(str_replace('Dr. ', '', $d[0]), 2)) ?></span>
                            <div>
                                <strong><?= e($d[0]) ?></strong>
                                <span><?= e($d[2]) ?></span>
                            </div>
                        </div>
                        <dl class="pt-doctor-meta">
                            <div><dt>Experience</dt><dd><?= e((string) $d[3]) ?> years</dd></div>
                            <div><dt>Available</dt><dd><?= e($d[4]) ?></dd></div>
                        </dl>
                        <?= button('Book appointment', ['icon' => 'calendar', 'href' => url(['page' => 'doctors', 'modal' => 'Book appointment', 'doc' => $d[0]])]) ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:26px 20px;">
                <strong>No doctors listed yet</strong>
                <p>Doctors for <?= e($dName) ?> will appear here once they are assigned.</p>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php if (!$groups): ?>
    <?= filtered_empty('No doctors or departments match “' . $q . '”. Try a different specialty or name.') ?>
<?php else: ?>
    <p class="muted" style="font-size:12px; margin-top:6px;">Showing <?= $shown ?> doctor<?= $shown !== 1 ? 's' : '' ?> across <?= count($groups) ?> department<?= count($groups) !== 1 ? 's' : '' ?>.</p>
<?php endif; ?>
