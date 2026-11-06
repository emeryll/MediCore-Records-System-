<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/archive_helper.php';

$q    = trim((string) ($_GET['q'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to   = trim((string) ($_GET['to'] ?? ''));

$admins = archive_get_admins();
$years  = [date('Y'), (string) ((int) date('Y') - 1)];

// Category definitions
$categories = [
    'Patients' => [
        'file'        => 'Patients.csv',
        'title'       => 'Patients Registry',
        'desc'        => 'Master patient directory, MRNs, demographics, verified contact info, and registration status.',
        'count'       => count($state['patients'] ?? []),
        'badge'       => 'Master Directory',
    ],
    'Billing' => [
        'file'        => 'Billing.csv',
        'title'       => 'Hospital Billing & Invoices',
        'desc'        => 'Itemized clinical invoices, cashier collections, payment receipts, and balance ledgers.',
        'count'       => count($state['bills'] ?? []),
        'badge'       => 'Financial',
    ],
    'Appointments' => [
        'file'        => 'Appointments.csv',
        'title'       => 'Outpatient Appointments',
        'desc'        => 'Specialist consultations, clinic booking logs, appointment statuses, and attending physician details.',
        'count'       => count($state['appointments'] ?? []),
        'badge'       => 'Outpatient',
    ],
    'Admissions' => [
        'file'        => 'Admissions.csv',
        'title'       => 'Inpatient Admissions & Wards',
        'desc'        => 'Bed assignments, room numbers, ward categorizations, daily room rates, and inpatient rosters.',
        'count'       => count($state['beds'] ?? []),
        'badge'       => 'Inpatient',
    ],
    'Medical_Records' => [
        'file'        => 'Medical_Records.csv',
        'title'       => 'Clinical Records & History',
        'desc'        => 'Physician consultation notes, clinical diagnoses, symptoms, treatments, and medical prescriptions.',
        'count'       => array_reduce($state['records'] ?? [], static fn($carry, $item) => $carry + (is_array($item) ? count($item) : 0), 0),
        'badge'       => 'Clinical',
    ],
    'Audit_Logs' => [
        'file'        => 'Audit_Logs.csv',
        'title'       => 'System & Security Audit Trail',
        'desc'        => 'Comprehensive chronological record of system logins, record edits, deactivations, and turnover events.',
        'count'       => count($state['audit'] ?? []),
        'badge'       => 'Security Trail',
    ],
];

// Active filters count
$hasFilter = ($q !== '' || $from !== '' || $to !== '');
?>
<?= page_title(
    'Hospital Archives & Administrative Records',
    'Historical records organized by Year > Administrator. Export individual category CSV files, selected records, or complete Year/Admin ZIP packages.'
) ?>

<!-- Search & Date Filter Form -->
<div class="panel" style="margin-bottom: 24px; padding: 20px;">
    <form method="get" action="index.php" style="display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end;">
        <input type="hidden" name="page" value="archive">

        <label class="field" style="flex:1; min-width:240px; margin:0;">
            <span class="field-label">Search Archive Records</span>
            <span class="input-wrap">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name, MRN, diagnosis, invoice, or keyword...">
                <?= icon('search', 16) ?>
            </span>
        </label>

        <label class="field" style="width:170px; margin:0;">
            <span class="field-label">From Date</span>
            <span class="input-wrap">
                <input type="date" name="from" value="<?= e($from) ?>">
            </span>
        </label>

        <label class="field" style="width:170px; margin:0;">
            <span class="field-label">To Date</span>
            <span class="input-wrap">
                <input type="date" name="to" value="<?= e($to) ?>">
            </span>
        </label>

        <div style="display:flex; gap:8px;">
            <button class="btn btn-primary" type="submit" style="height:42px;">
                <?= icon('search', 16) ?>
                <span>Search</span>
            </button>
            <?php if ($hasFilter): ?>
                <a class="btn btn-secondary" href="<?= e(url(['page' => 'archive'])) ?>" style="height:42px;">
                    <span>Clear</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Form for Multi-File ZIP Export -->
<form method="post" action="index.php" id="bulk-export-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="export_archive_zip">
    <input type="hidden" name="filter_q" value="<?= e($q) ?>">
    <input type="hidden" name="filter_from" value="<?= e($from) ?>">
    <input type="hidden" name="filter_to" value="<?= e($to) ?>">

    <!-- Hierarchical Year > Admin Structure -->
    <?php foreach ($years as $year): ?>
        <div style="margin-bottom: 32px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px; border-bottom:2px solid var(--teal-600); padding-bottom:8px;">
                <span style="font-size:24px;">📁</span>
                <h2 style="margin:0; font-size:22px; color:var(--navy-900); font-weight:700;">Year <?= e($year) ?></h2>
                <span class="badge badge-info" style="font-weight:700;">Hospital Records Archive</span>
            </div>

            <?php foreach ($admins as $admin):
                $adminId       = (string) ($admin['user_id'] ?? 'A-0001');
                $adminName     = (string) ($admin['full_name'] ?? 'Administrator');
                $adminEmail    = (string) ($admin['email'] ?? '');
                $adminStatus   = (string) ($admin['status'] ?? 'Active');
                $isDeactivated = ($adminStatus === 'Disabled' || $adminStatus === 'Deactivated');
            ?>
                <div class="panel" style="margin-bottom:20px; border: 1px solid <?= $isDeactivated ? '#fed7aa' : 'var(--gray-200)' ?>; background: <?= $isDeactivated ? '#fffdfa' : '#ffffff' ?>;">
                    <div class="panel-head" style="background: <?= $isDeactivated ? '#fff7ed' : '#f8fafc' ?>; border-bottom: 1px solid <?= $isDeactivated ? '#ffedd5' : 'var(--gray-200)' ?>; padding: 16px 20px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="font-size:20px;">👤</span>
                            <div>
                                <h3 style="margin:0; font-size:17px; color:var(--navy-900);">
                                    <?= e($adminName) ?>
                                </h3>
                                <p style="margin:2px 0 0; font-size:12.5px; color:var(--gray-600);">
                                    <?= e($adminEmail) ?> · Managed Records Repository (<?= e($year) ?>)
                                </p>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:10px;">
                            <?php if ($isDeactivated): ?>
                                <span class="badge badge-danger" style="display:inline-flex; align-items:center; gap:4px;">
                                    <?= icon('lock', 13) ?>
                                    <span>Deactivated (Turnover · Read-Only)</span>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-success">Active Administrator</span>
                            <?php endif; ?>

                            <!-- Whole Folder Export as ZIP -->
                            <button type="submit" name="export_scope" value="<?= e($year . '__' . $adminId) ?>" class="btn btn-secondary" style="font-size:12.5px; padding:6px 12px; display:inline-flex; align-items:center; gap:6px;">
                                <?= icon('download', 14) ?>
                                <span>Export Folder ZIP</span>
                            </button>
                        </div>
                    </div>

                    <?php if ($isDeactivated): ?>
                        <div style="padding:10px 20px; background:#fef3c7; border-bottom:1px solid #fde68a; font-size:13px; color:#92400e; display:flex; align-items:center; gap:8px;">
                            <?= icon('shield', 15) ?>
                            <span><strong>Turnover Archive:</strong> This administrator account is deactivated. All hospital records under this administration are locked and preserved in <strong>Read-Only mode</strong>.</span>
                        </div>
                    <?php endif; ?>

                    <div class="table-scroll">
                        <table style="margin:0;">
                            <thead>
                                <tr>
                                    <th style="width:40px; text-align:center;">
                                        <input type="checkbox" onclick="toggleFolderChecks(this, '<?= e($year . '_' . $adminId) ?>')">
                                    </th>
                                    <th>Category File</th>
                                    <th>Archive Classification</th>
                                    <th>Description</th>
                                    <th>Record Count</th>
                                    <th style="text-align:right;">Export Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $catKey => $cat):
                                    $fileKey = $year . '__' . $adminId . '__' . $catKey;
                                ?>
                                    <tr>
                                        <td style="text-align:center;">
                                            <input type="checkbox" name="selected_files[]" value="<?= e($fileKey) ?>" class="folder-chk-<?= e($year . '_' . $adminId) ?>">
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <span style="font-size:16px;">📄</span>
                                                <strong style="font-family:monospace; font-size:13.5px; color:var(--teal-900);"><?= e($cat['file']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary"><?= e($cat['badge']) ?></span>
                                        </td>
                                        <td style="font-size:13px; color:var(--gray-600); max-width:320px;">
                                            <?= e($cat['desc']) ?>
                                        </td>
                                        <td>
                                            <strong style="color:var(--navy-900);"><?= number_format($cat['count']) ?></strong>
                                            <span style="font-size:12px; color:var(--gray-500);"> rows</span>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px;">
                                                <a href="<?= e(url([
                                                    'page'        => 'archive',
                                                    'action'      => 'download_csv',
                                                    'category'    => $catKey,
                                                    'admin_id'    => $adminId,
                                                    'year'        => $year,
                                                    'q'           => $q ?: null,
                                                    'from'        => $from ?: null,
                                                    'to'          => $to ?: null,
                                                ])) ?>" class="btn btn-outline" style="font-size:12px; padding:4px 10px; display:inline-flex; align-items:center; gap:4px;">
                                                    <?= icon('download', 13) ?>
                                                    <span>CSV</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <!-- Bottom Selected Files Export Action Bar -->
    <div style="position:sticky; bottom:20px; z-index:90; background:#ffffff; border:2px solid var(--teal-600); border-radius:12px; padding:16px 20px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); margin-top:24px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:22px;">📦</span>
            <div>
                <strong style="font-size:15px; color:var(--navy-900);">Batch Archive Export</strong>
                <p style="margin:0; font-size:12.5px; color:var(--gray-600);">Select individual category files above or export entire folders as ZIP.</p>
            </div>
        </div>

        <button type="submit" name="export_scope" value="selected" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
            <?= icon('download', 16) ?>
            <span>Download Selected as ZIP</span>
        </button>
    </div>
</form>

<script>
function toggleFolderChecks(master, folderClass) {
    var boxes = document.querySelectorAll('.folder-chk-' + folderClass);
    boxes.forEach(function(b) {
        b.checked = master.checked;
    });
}
</script>
