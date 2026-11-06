<?php
declare(strict_types=1);

$q       = trim((string) ($_GET['q'] ?? ''));
$dept    = (string) ($_GET['dept'] ?? 'All departments');
$pageNum = max(1, (int) ($_GET['p'] ?? 1));

$departments = $state['crud']['departments'] ?? [];
$doctors     = doctors_data();

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

$paged = paginate_rows($matches, 6, 'p');

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
    'Search and filter West Metro Medical Center specialists by department, view their schedule, and book directly.',
    button('Book appointment', ['icon' => 'plus', 'href' => url(['page' => 'book', 'step' => 1])])
) ?>

<div class="table-card" style="margin-bottom:20px;">
    <?= $toolbar ?>
</div>

<?php if ($paged['rows']): ?>
    <div class="pt-doctor-grid" style="margin-bottom:18px;">
        <?php foreach ($paged['rows'] as $d): ?>
            <article class="pt-doctor-card">
                <div class="pt-doctor-top">
                    <span class="avatar"><?= e(initials(str_replace('Dr. ', '', $d[0]), 2)) ?></span>
                    <div>
                        <strong><?= e($d[0]) ?></strong>
                        <span><?= e($d[2]) ?> · <?= e($d[1]) ?></span>
                    </div>
                </div>
                <dl class="pt-doctor-meta">
                    <div><dt>Department</dt><dd><?= e($d[1]) ?></dd></div>
                    <div><dt>Schedule</dt><dd><?= e($d[4]) ?></dd></div>
                </dl>
                <?= button('Book appointment', ['icon' => 'calendar', 'href' => url(['page' => 'book', 'step' => 2, 'dept' => $d[1], 'doc' => $d[0]])]) ?>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="table-card">
        <?= pagination_bar('doctor', $paged['total'], $paged['page'], $paged['pages'], 'p') ?>
    </div>
<?php else: ?>
    <?= filtered_empty('No doctors or departments match “' . $q . '”. Try a different specialty or name.') ?>
<?php endif; ?>
