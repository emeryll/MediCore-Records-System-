<?php
declare(strict_types=1);

$patientParam = trim((string) ($_GET['patient'] ?? ''));
$pt           = find_patient_record($state, $patientParam) ?: ($state['patients'][0] ?? null);

if (!$pt) {
    echo page_title('Patient Not Found', 'The requested patient record could not be located.');
    echo button('← Back to Patients', ['variant' => 'secondary', 'href' => url(['page' => 'patients'])]);
    return;
}

$ptName    = (string) ($pt[0] ?? 'Patient');
$isProv    = is_provisional_patient($pt);
$regStatus = $isProv ? 'Provisional' : 'Registered';
$mrn       = patient_mrn_display($pt, '');
$dob       = format_birthdate((string) ($pt[1] ?? ''));
$age       = patient_age_label($dob, false, '—');
$phone     = (string) ($pt[2] ?? '—');
$sex       = (string) ($pt[5] ?? '—');
$addr      = (string) ($pt[7] ?? '—');
$email     = (string) ($pt['email'] ?? '');
$phVer     = !empty($pt['phone_verified']);
$idChecked = !empty($pt['id_checked']);
$consent   = !empty($pt['consent_given']);
$createdBy = (string) ($pt['created_by'] ?? 'Front Desk Staff');
$createdAt = (string) ($pt['created_at'] ?? '—');
$upgBy     = (string) ($pt['upgraded_by'] ?? '');
$upgAt     = (string) ($pt['upgraded_at'] ?? '');

$ptAppts   = array_values(array_filter($state['appointments'] ?? [], static fn ($a) => strcasecmp((string) ($a[1] ?? ''), $ptName) === 0));
$ptRecords = patient_medical_records($state, $ptName, (string) ($pt[8] ?? ''));
?>
<div style="margin-bottom:14px;">
    <a class="back-link" href="<?= e(url(['page' => 'patients'])) ?>">← Back to Patients</a>
</div>

<?= page_title(
    $ptName,
    $isProv
        ? 'Provisional Record (Phone-in) · No MRN assigned until verified in person at check-in.'
        : 'Registered Patient Record · MRN: ' . $mrn,
    '<div style="display:flex; gap:10px; flex-wrap:wrap;">'
    . ($isProv
        ? button('Complete registration (Issue MRN)', [
            'icon' => 'shield',
            'href' => url(['page' => 'patients', 'detail' => 'profile', 'patient' => $ptName, 'modal' => 'Complete registration']),
        ])
        : button('Edit patient', [
            'variant' => 'secondary',
            'icon'    => 'edit',
            'href'    => url(['page' => 'patients', 'detail' => 'profile', 'patient' => $ptName, 'modal' => 'Edit patient']),
        ]))
    . button('Book appointment', [
        'variant' => 'secondary',
        'icon'    => 'calendar',
        'href'    => url(['page' => 'book', 'patient' => $ptName]),
    ])
    . (!$isProv
        ? button('Admit patient', [
            'variant' => 'secondary',
            'icon'    => 'bed',
            'href'    => url(['page' => 'admissions', 'modal' => 'Admit patient', 'patient' => $ptName]),
        ])
        : '')
    . '</div>'
) ?>

<?php if ($isProv): ?>
    <div style="background:#fffbeb; border:1px solid #fcd34d; border-radius:12px; padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <strong style="color:#92400e; font-size:15px;">⚠️ Provisional Patient Record (No MRN Yet)</strong>
            <p style="margin:4px 0 0; color:#b45309; font-size:13.5px;">
                This bare-bones entry was created for a phone-in booking. Inpatient admission, billing clearance, and online account activation are blocked until the patient arrives and completes full registration.
            </p>
        </div>
        <?= button('Complete registration now', [
            'icon' => 'shield',
            'href' => url(['page' => 'patients', 'detail' => 'profile', 'patient' => $ptName, 'modal' => 'Complete registration']),
        ]) ?>
    </div>
<?php endif; ?>

<div class="content-grid">
    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Patient Demographics &amp; Verification</h2>
                <p>Identity, contact, and registration audit trail</p>
            </div>
            <?= badge($regStatus, status_tone($regStatus)) ?>
        </div>

        <div class="form-grid" style="gap:14px;">
            <div class="panel" style="padding:14px; background:#f8fafc;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block;">Medical Record Number (MRN)</span>
                <?php if ($mrn !== ''): ?>
                    <strong style="font-size:16px; color:var(--teal-800); font-family:monospace;"><?= e($mrn) ?></strong>
                <?php else: ?>
                    <strong style="font-size:14px; color:var(--gray-400);">Not Issued (Provisional)</strong>
                <?php endif; ?>
            </div>
            <div class="panel" style="padding:14px; background:#f8fafc;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block;">Birthdate &amp; Age</span>
                <strong style="font-size:14px; color:var(--navy-900);"><?= e($dob) ?></strong>
                <span style="font-size:13px; color:var(--gray-600);"> (<?= e($age) ?>)</span>
            </div>
            <div class="panel" style="padding:14px; background:#f8fafc;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block;">Sex</span>
                <strong style="font-size:14px; color:var(--navy-900);"><?= e($sex) ?></strong>
            </div>
            <div class="panel" style="padding:14px; background:#f8fafc;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block;">Mobile Number</span>
                <?php if (!empty($pt['no_phone']) || empty($phone) || $phone === '—'): ?>
                    <strong style="font-size:14px; color:var(--gray-600);">None (No phone)</strong>
                    <div style="margin-top:4px;"><?= badge('No account (no phone)', 'neutral') ?></div>
                <?php else: ?>
                    <strong style="font-size:14px; color:var(--navy-900); font-family:monospace;"><?= e($phone) ?></strong>
                    <div><?= $phVer && !$isProv ? badge('OTP Verified', 'success') : badge('Unverified', 'warning') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($pt['no_phone'])): ?>
            <div class="panel" style="padding:16px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; margin-top:14px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--teal-800); letter-spacing:0.5px;">Emergency / Notification Contact Person</span>
                    <?= badge('Required Contact', 'warning') ?>
                </div>
                <div style="font-size:14.5px; color:var(--navy-900);">
                    <strong><?= e((string) ($pt['contact_name'] ?: 'None specified')) ?></strong>
                    <?php if (!empty($pt['contact_relationship'])): ?>
                        <span style="color:var(--gray-600); font-weight:600;"> (<?= e((string) $pt['contact_relationship']) ?>)</span>
                    <?php endif; ?>
                    <?php if (!empty($pt['contact_phone'])): ?>
                        · <span style="font-family:monospace; font-weight:700; color:var(--teal-800); font-size:14px;"><?= e((string) $pt['contact_phone']) ?></span>
                    <?php endif; ?>
                </div>
                <small style="display:block; margin-top:6px; font-size:11.5px; color:var(--gray-500); line-height:1.4;">
                    This contact number is for notifications and appointment reminders only. It cannot be used to activate an online portal account or log in.
                </small>
            </div>
        <?php endif; ?>

        <div class="panel" style="padding:14px; background:#f8fafc; margin-top:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block;">Residential Address</span>
                <?php if (!$isProv): ?>
                    <a href="<?= e(url(['page' => 'patients', 'detail' => 'profile', 'patient' => $ptName, 'modal' => ($role === 'Admin' ? 'Correct patient address' : 'Edit patient address')])) ?>" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:5px; font-size:12px; height:28px;">
                        <?= icon('edit', 14) ?>
                        <span>Edit Address</span>
                    </a>
                <?php endif; ?>
            </div>
            <strong style="font-size:14px; color:var(--navy-900); display:block;"><?= e($isProv ? 'Not yet collected (Provisional)' : $addr) ?></strong>
        </div>

        <div class="panel" style="padding:14px; background:#f8fafc; margin-top:14px;">
            <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--gray-500); display:block; margin-bottom:6px;">Verification &amp; Audit Trail</span>
            <p style="margin:0; font-size:13px; color:var(--gray-700); line-height:1.6;">
                <strong>Created by:</strong> <?= e($createdBy) ?> on <?= e($createdAt) ?><br>
                <?php if ($upgAt !== ''): ?>
                    <strong>Upgraded to Registered by:</strong> <?= e($upgBy ?: 'Staff') ?> on <?= e($upgAt) ?><br>
                <?php endif; ?>
                <?php if (!empty($pt['no_phone'])): ?>
                    <strong>Phone Verification:</strong> Skipped (Patient has no phone) · <strong>Identity:</strong> Verified in-person via physical ID<br>
                    <strong>Portal Account:</strong> <span style="color:var(--gray-600); font-weight:600;">No account (no phone)</span><br>
                <?php endif; ?>
                <strong>ID Checked:</strong> <?= $idChecked || !$isProv ? '✓ Yes' : 'Pending' ?> ·
                <strong>Patient Consent:</strong> <?= $consent || !$isProv ? '✓ Signed' : 'Pending' ?>
            </p>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Scheduled Appointments (<?= count($ptAppts) ?>)</h2>
                <p>Visits attached to this patient record</p>
            </div>
        </div>
        <?php if ($ptAppts): ?>
            <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($ptAppts as $a): ?>
                    <div class="panel" style="padding:14px; background:#f8fafc; display:flex; justify-content:space-between; align-items:center; gap:12px;">
                        <div>
                            <strong><?= e((string) $a[7]) ?> at <?= e(slot_label(normalize_slot((string) $a[0]))) ?></strong>
                            <p style="margin:2px 0 0; font-size:13px; color:var(--gray-600);"><?= e((string) $a[5]) ?> (<?= e((string) $a[6]) ?>) · <?= e((string) $a[4]) ?></p>
                        </div>
                        <?= badge((string) $a[3], status_tone((string) $a[3])) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?= filtered_empty('No appointments scheduled yet.') ?>
        <?php endif; ?>
    </section>
</div>
