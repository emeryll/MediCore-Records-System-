<?php
declare(strict_types=1);

/** Shared Admin page for Departments & Doctors ($page === 'departments') and Room Setup ($page === 'rooms'). */
$type = $page;
$q    = trim((string) ($_GET['q'] ?? ''));

if ($type === 'rooms'):
    $showAddBed = isset($_GET['add_bed']);
    $editBedId  = trim((string) ($_GET['edit_bed'] ?? ''));
    $beds       = $state['beds'] ?? [];
    $filtered   = array_values(array_filter($beds, static fn ($b) => row_matches([$b['bed_id'] ?? '', $b['room_no'] ?? '', $b['bed_code'] ?? '', $b['ward'] ?? '', $b['room_type'] ?? '', $b['floor'] ?? ''], $q)));
    $paged      = paginate_rows($filtered, 12, 'p');

    $editBed = null;
    if ($editBedId !== '') {
        foreach ($beds as $b) {
            if ($b['bed_id'] === $editBedId) {
                $editBed = $b;
                break;
            }
        }
    }

    $toolbar = toolbar_open('rooms')
        . search_box('Search room number, bed code, ward, or room type...', 'q', $q)
        . toolbar_close();
?>
    <?= page_title(
        'Room & Bed Setup',
        'Create and manage hospital rooms and beds, configure room types (Ward, Semi-Private, Private, ICU) and daily rates (₱), or deactivate rooms taken out of service.',
        button('Add room / bed', ['icon' => 'plus', 'href' => url(['page' => 'rooms', 'add_bed' => 1])])
    ) ?>

    <?php if ($showAddBed || $editBed): ?>
        <div class="panel" style="padding:20px 22px; margin-bottom:20px; border-color:var(--teal-600);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                <div>
                    <h2 style="font-size:15px;"><?= $editBed ? 'Edit Bed ' . e($editBed['bed_id']) : 'Add New Room & Bed' ?></h2>
                    <p style="font-size:11.5px; color:var(--gray-500);">Configure ward assignment, floor, room type, and daily rate in Philippine Pesos (₱)</p>
                </div>
                <a class="icon-btn" href="<?= e(url(['page' => 'rooms'])) ?>"><?= icon('close') ?></a>
            </div>
            <form method="post" action="index.php" style="display:grid; gap:14px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_room_setup">
                <input type="hidden" name="orig_bed_id" value="<?= e($editBed['bed_id'] ?? '') ?>">
                <div class="form-grid three">
                    <?= field('Room Number *', 'room_no', 'e.g. 205', 'text', $editBed['room_no'] ?? '', ['required' => true]) ?>
                    <?= field('Bed Code *', 'bed_code', 'e.g. Bed A', 'text', $editBed['bed_code'] ?? 'Bed A', ['required' => true]) ?>
                    <?= select_field('Room Type *', 'room_type', ['Ward', 'Semi-Private', 'Private', 'ICU'], $editBed['room_type'] ?? 'Private') ?>
                </div>
                <div class="form-grid three">
                    <?= select_field('Ward / Department *', 'ward', ['Cardiology Ward', 'Medical Ward', 'Pediatrics Ward', 'Maternity Ward', 'Orthopedics Ward', 'ICU'], $editBed['ward'] ?? 'Medical Ward') ?>
                    <?= select_field('Floor *', 'floor', ['2nd Floor', '3rd Floor', '4th Floor'], $editBed['floor'] ?? '2nd Floor') ?>
                    <?= field('Rate per Day (₱) *', 'rate', '3200.00', 'number', (string) ($editBed['rate'] ?? '3200'), ['required' => true]) ?>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <?= button('Cancel', ['variant' => 'secondary', 'href' => url(['page' => 'rooms'])]) ?>
                    <?= button($editBed ? 'Save Changes' : 'Add Room / Bed', ['type' => 'submit', 'icon' => 'check']) ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?= table_open(['Bed ID', 'Room No.', 'Bed Code', 'Ward', 'Room Type', 'Floor', 'Daily Rate (₱)', 'Occupancy', 'Service Status', 'Actions'], $toolbar) ?>
    <?php foreach ($paged['rows'] as $b):
        $isActive = !empty($b['active']);
    ?>
        <tr>
            <td><strong><code><?= e($b['bed_id']) ?></code></strong></td>
            <td><strong>Room <?= e($b['room_no']) ?></strong></td>
            <td><?= e($b['bed_code']) ?></td>
            <td><?= e($b['ward']) ?></td>
            <td><?= badge($b['room_type'], 'info') ?></td>
            <td><?= e($b['floor']) ?></td>
            <td><strong><?= e(format_money((float) $b['rate'])) ?></strong></td>
            <td><?= badge($b['status'], status_tone($b['status'])) ?></td>
            <td><?= badge($isActive ? 'Active' : 'Deactivated', $isActive ? 'success' : 'danger') ?></td>
            <td>
                <div class="row-actions" style="gap:8px;">
                    <a href="<?= e(url(['page' => 'rooms', 'edit_bed' => $b['bed_id']])) ?>"><?= icon('edit', 15) ?> Edit</a>
                    <?= post_button('', 'toggle_bed_active', ['bed_id' => $b['bed_id']], [
                        'plain' => true,
                        'class' => $isActive ? 'danger-link' : 'text-link',
                        'html'  => $isActive ? 'Deactivate' : 'Activate',
                    ]) ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close('bed', true, $paged['total'], $paged['page'], $paged['pages']) ?>

<?php else:
    $records = array_values(array_filter($state['crud']['departments'] ?? [], static fn ($r) => row_matches($r, $q)));
    $doctors = doctors_data();
    $toolbar = toolbar_open('departments') . search_box('Search departments or head physician...', 'q', $q) . toolbar_close();
?>
    <?= page_title(
        'Departments & Doctors',
        'Add or edit hospital departments and review linked specialists at West Metro Medical Center.',
        button('Add department', ['icon' => 'plus', 'href' => url(['page' => 'departments', 'modal' => 'Add department'])])
    ) ?>

    <?= table_open(['Department', 'Department Head', 'Linked Doctors', 'Location', 'Actions'], $toolbar) ?>
    <?php foreach ($records as $r):
        $dName = (string) ($r[0] ?? '');
        $linkedDocs = array_values(array_filter($doctors, static fn ($d) => strcasecmp((string) $d[1], $dName) === 0));
    ?>
        <tr>
            <td><strong><?= e($dName) ?></strong></td>
            <td><?= e($r[1] ?? '—') ?></td>
            <td>
                <?php if ($linkedDocs): ?>
                    <?php foreach ($linkedDocs as $ld): ?>
                        <div><strong><?= e($ld[0]) ?></strong> <span class="muted">(<?= e($ld[2]) ?>)</span></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="muted">No doctors linked yet</span>
                <?php endif; ?>
            </td>
            <td><?= e($r[3] ?? ($r[2] ?? 'Main Wing')) ?></td>
            <td>
                <div class="row-actions">
                    <a href="<?= e(url(['page' => 'departments', 'modal' => 'Edit department', 'key' => $r[0]])) ?>"><?= icon('edit', 15) ?> Edit Department</a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?= table_close(count($records) . ' department(s)', false) ?>
<?php endif; ?>
