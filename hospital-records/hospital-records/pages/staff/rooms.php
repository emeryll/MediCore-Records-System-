<?php
declare(strict_types=1);

$view       = ($_GET['view'] ?? 'Grid') === 'List' ? 'List' : 'Grid';
$q          = trim((string) ($_GET['q'] ?? ''));
$wardFilter = (string) ($_GET['ward'] ?? 'All wards');
$typeFilter = (string) ($_GET['type'] ?? 'All types');
$floorFilt  = (string) ($_GET['floor'] ?? 'All floors');
$statusFilt = (string) ($_GET['status'] ?? 'All statuses');

$allBeds = array_values(array_filter($state['beds'] ?? [], static fn ($b) => !empty($b['active'])));

// Sync bed occupancy with active admissions so every bed reflects live admission data
$admByBed = [];
foreach ($state['admissions'] as $adm) {
    $bedKey = trim((string) ($adm[1] ?? ''));
    if ($bedKey !== '') {
        $admByBed[$bedKey] = $adm;
    }
}

foreach ($allBeds as &$b) {
    $bId = $b['bed_id'];
    if (isset($admByBed[$bId])) {
        $adm = $admByBed[$bId];
        $b['status']         = 'Occupied';
        $b['patient']        = $adm[0];
        $b['admitted_date']  = $adm[3] ?? '';
        $b['doctor']         = $adm[4] ?? '';
        $ptRec               = find_patient_record($state, (string) $adm[0]);
        $b['mrn']            = $ptRec ? patient_mrn_display($ptRec) : ($adm[8] ?? '');
    }
}
unset($b);

$totalCount = count($allBeds);
$vacantCnt  = count(array_filter($allBeds, static fn ($b) => $b['status'] === 'Vacant'));
$occCnt     = count(array_filter($allBeds, static fn ($b) => $b['status'] === 'Occupied'));
$maintCnt   = count(array_filter($allBeds, static fn ($b) => $b['status'] === 'Maintenance'));

$wards  = ['All wards', 'Cardiology Ward', 'Medical Ward', 'Pediatrics Ward', 'Maternity Ward', 'Orthopedics Ward', 'ICU'];
$types  = ['All types', 'Ward', 'Semi-Private', 'Private', 'ICU'];
$floors = ['All floors', '2nd Floor', '3rd Floor', '4th Floor'];

$filteredBeds = array_values(array_filter($allBeds, static function (array $b) use ($q, $wardFilter, $typeFilter, $floorFilt, $statusFilt): bool {
    if ($wardFilter !== 'All wards' && strcasecmp($b['ward'], $wardFilter) !== 0) {
        return false;
    }
    if ($typeFilter !== 'All types' && strcasecmp($b['room_type'], $typeFilter) !== 0) {
        return false;
    }
    if ($floorFilt !== 'All floors' && strcasecmp($b['floor'], $floorFilt) !== 0) {
        return false;
    }
    if ($statusFilt !== 'All statuses' && strcasecmp($b['status'], $statusFilt) !== 0) {
        return false;
    }
    if ($q !== '') {
        return row_matches([$b['bed_id'], $b['room_no'], $b['bed_code'], $b['ward'], $b['room_type'], $b['patient'], $b['mrn']], $q);
    }
    return true;
}));

$toolbar = toolbar_open('staff-rooms', ['view' => $view])
    . search_box('Search room/bed number or occupying patient / MRN...', 'q', $q)
    . '<div class="toolbar-right">'
    . select_field('', 'ward', $wards, $wardFilter)
    . select_field('', 'type', $types, $typeFilter)
    . select_field('', 'floor', $floors, $floorFilt)
    . select_field('', 'status', ['All statuses', 'Vacant', 'Occupied', 'Maintenance'], $statusFilt)
    . filter_button()
    . '</div>'
    . toolbar_close();
?>
<?= page_title(
    'Rooms & Bed Availability',
    'Live hospital floor map and bed status across West Metro Medical Center wards.',
    button('Admit patient', ['icon' => 'plus', 'href' => url(['page' => 'admissions', 'modal' => 'Admit patient'])])
) ?>

<!-- Summary Bar -->
<div class="stats-grid four">
    <?= stat_card('Total Active Beds', (string) $totalCount, 'Across all hospital wards', 'bed', 'navy') ?>
    <?= stat_card('Vacant Beds', (string) $vacantCnt, 'Ready for immediate admission', 'bed', 'green') ?>
    <?= stat_card('Occupied Beds', (string) $occCnt, 'Currently assigned to inpatients', 'patient', 'teal') ?>
    <?= stat_card('Under Maintenance', (string) $maintCnt, 'Cleaning, sanitation, or repair', 'shield', 'amber') ?>
</div>

<div class="view-toggle" style="margin-bottom:14px;">
    <a class="view-toggle-btn <?= $view === 'Grid' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'staff-rooms', 'view' => 'Grid', 'ward' => $wardFilter, 'type' => $typeFilter, 'floor' => $floorFilt, 'status' => $statusFilt, 'q' => $q])) ?>">
        <?= icon('grid', 15) ?> Grid / Floor-Map View
    </a>
    <a class="view-toggle-btn <?= $view === 'List' ? 'active' : '' ?>"
       href="<?= e(url(['page' => 'staff-rooms', 'view' => 'List', 'ward' => $wardFilter, 'type' => $typeFilter, 'floor' => $floorFilt, 'status' => $statusFilt, 'q' => $q])) ?>">
        <?= icon('clipboard', 15) ?> List View
    </a>
</div>

<div class="table-card" style="margin-bottom:18px;">
    <?= $toolbar ?>
</div>

<?php if ($view === 'Grid'): ?>
    <?php
    // Group filtered beds by room_no
    $roomsGrouped = [];
    foreach ($filteredBeds as $b) {
        $rKey = $b['room_no'];
        if (!isset($roomsGrouped[$rKey])) {
            $roomsGrouped[$rKey] = [
                'room_no'   => $b['room_no'],
                'ward'      => $b['ward'],
                'room_type' => $b['room_type'],
                'floor'     => $b['floor'],
                'rate'      => $b['rate'],
                'beds'      => [],
            ];
        }
        $roomsGrouped[$rKey]['beds'][] = $b;
    }
    ?>
    <?php if ($roomsGrouped): ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(330px, 1fr)); gap:16px;">
            <?php foreach ($roomsGrouped as $rm): ?>
                <section class="panel" style="padding:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid var(--gray-200);">
                        <div>
                            <span class="eyebrow" style="font-size:10.5px;"><?= e($rm['floor']) ?> · <?= e($rm['ward']) ?></span>
                            <h3 style="margin:2px 0 0; font-size:16px;">Room <?= e($rm['room_no']) ?> <small style="font-weight:500; color:var(--gray-500);">(<?= e($rm['room_type']) ?>)</small></h3>
                        </div>
                        <strong style="font-size:13px; color:var(--teal-700);"><?= e(format_money((float) $rm['rate'])) ?>/day</strong>
                    </div>

                    <div style="display:grid; gap:10px;">
                        <?php foreach ($rm['beds'] as $bed):
                            $st = $bed['status'];
                            $bg = $st === 'Vacant' ? '#f0fdf4' : ($st === 'Occupied' ? '#eff6ff' : '#fffbeb');
                            $bd = $st === 'Vacant' ? '#bbf7d0' : ($st === 'Occupied' ? '#bfdbfe' : '#fde68a');
                        ?>
                            <div style="background:<?= $bg ?>; border:1px solid <?= $bd ?>; border-radius:10px; padding:12px 14px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <strong style="font-size:14px; color:var(--navy-900);"><?= icon('bed', 15) ?> <?= e($bed['bed_id']) ?> (<?= e($bed['bed_code']) ?>)</strong>
                                    <?= badge($st, status_tone($st)) ?>
                                </div>

                                <?php if ($st === 'Occupied'): ?>
                                    <div style="font-size:12.5px; color:var(--gray-700); margin-bottom:8px;">
                                        <div>Patient: <strong><?= e($bed['patient']) ?></strong> <?= $bed['mrn'] ? '<code>' . e($bed['mrn']) . '</code>' : '' ?></div>
                                        <div class="muted" style="font-size:11.5px;">Admitted: <?= e($bed['admitted_date'] ?: 'Current') ?> · <?= e($bed['doctor'] ?: 'Attending') ?></div>
                                    </div>
                                    <div style="display:flex; gap:8px;">
                                        <?= button('View Admission', [
                                            'variant' => 'secondary',
                                            'href'    => url(['page' => 'admissions', 'q' => $bed['patient']]),
                                        ]) ?>
                                    </div>
                                <?php elseif ($st === 'Maintenance'): ?>
                                    <p style="font-size:12px; color:var(--amber-text); margin:0 0 8px;">Reason: <em><?= e($bed['maintenance_reason'] ?: 'Scheduled cleaning / repair') ?></em></p>
                                    <?= post_button('Mark Vacant (Ready)', 'set_bed_maintenance', ['bed_id' => $bed['bed_id'], 'target_status' => 'Vacant'], ['variant' => 'secondary']) ?>
                                <?php else: ?>
                                    <p class="muted" style="font-size:12px; margin:0 0 8px;">Bed is sanitized and ready for patient assignment.</p>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <?= button('Admit to this bed', [
                                            'variant' => 'primary',
                                            'href'    => url(['page' => 'admissions', 'modal' => 'Admit patient', 'bed_id' => $bed['bed_id']]),
                                        ]) ?>
                                        <?= button('Set Maintenance', [
                                            'variant' => 'secondary',
                                            'href'    => url(['page' => 'staff-rooms', 'view' => 'Grid', 'modal' => 'Bed maintenance', 'bed_id' => $bed['bed_id']]),
                                        ]) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?= filtered_empty('No rooms or beds match these filter criteria.') ?>
    <?php endif; ?>
<?php else: ?>
    <?php $paged = paginate_rows($filteredBeds, 10, 'p'); ?>
    <?= table_open(['Bed ID', 'Room & Floor', 'Ward & Type', 'Rate / Day', 'Status', 'Occupying Patient / Reason', 'Actions']) ?>
    <?php foreach ($paged['rows'] as $bed):
        $st = $bed['status'];
    ?>
        <tr>
            <td><strong><code><?= e($bed['bed_id']) ?></code></strong></td>
            <td>
                <strong>Room <?= e($bed['room_no']) ?> (<?= e($bed['bed_code']) ?>)</strong>
                <div class="muted" style="font-size:11px;"><?= e($bed['floor']) ?></div>
            </td>
            <td>
                <strong><?= e($bed['ward']) ?></strong>
                <div class="muted" style="font-size:11px;"><?= e($bed['room_type']) ?></div>
            </td>
            <td><strong><?= e(format_money((float) $bed['rate'])) ?></strong></td>
            <td><?= badge($st, status_tone($st)) ?></td>
            <td>
                <?php if ($st === 'Occupied'): ?>
                    <strong><?= e($bed['patient']) ?></strong> <?= $bed['mrn'] ? '<code>' . e($bed['mrn']) . '</code>' : '' ?>
                    <div class="muted" style="font-size:11px;">Admitted <?= e($bed['admitted_date']) ?> · <?= e($bed['doctor']) ?></div>
                <?php elseif ($st === 'Maintenance'): ?>
                    <span style="color:var(--amber-text); font-size:12px;"><?= e($bed['maintenance_reason'] ?: 'Sanitation / Maintenance') ?></span>
                <?php else: ?>
                    <span class="muted">Available for admission</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="row-actions">
                    <?php if ($st === 'Vacant'): ?>
                        <?= button('Admit Here', ['variant' => 'primary', 'href' => url(['page' => 'admissions', 'modal' => 'Admit patient', 'bed_id' => $bed['bed_id']])]) ?>
                        <?= button('Maintenance', ['variant' => 'secondary', 'href' => url(['page' => 'staff-rooms', 'view' => 'List', 'modal' => 'Bed maintenance', 'bed_id' => $bed['bed_id']])]) ?>
                    <?php elseif ($st === 'Maintenance'): ?>
                        <?= post_button('Return to Vacant', 'set_bed_maintenance', ['bed_id' => $bed['bed_id'], 'target_status' => 'Vacant'], ['variant' => 'secondary']) ?>
                    <?php else: ?>
                        <?= button('View Admission', ['variant' => 'secondary', 'href' => url(['page' => 'admissions', 'q' => $bed['patient']])]) ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('bed', true, $paged['total'], $paged['page'], $paged['pages']) ?>
    <?php if (!$paged['rows']): ?>
        <?= filtered_empty('No beds match these filters.') ?>
    <?php endif; ?>
<?php endif; ?>
