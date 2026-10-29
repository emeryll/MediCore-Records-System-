<?php
$ptName = current_user_name('Patient');
$patient = null;
foreach ($state['patients'] as $p) {
    if ($p[0] === $ptName) {
        $patient = $p;
        break;
    }
}
$patient ??= [$ptName, '—', '', '—', initials($ptName, 2), 'Not specified', 'Not specified', '', $_SESSION['user_id'] ?? 'P-1001'];
$myPtId = $patient[8] ?? ($_SESSION['user_id'] ?? 'P-1001');
$myDob  = format_birthdate($patient[1] ?? '');
$myAge  = patient_age_label($myDob, false, '—');
?>
<?= page_title(
    'My Profile',
    'Manage your contact details and account security. Medical identifiers are read-only.'
) ?>

<div class="profile-hero">
    <div class="large-avatar"><?= e($patient[4] ?? initials($ptName)) ?></div>
    <div>
        <?= badge('Verified Patient', 'success') ?>
        <h1><?= e($ptName) ?></h1>
        <p>Patient ID <strong><?= e($myPtId) ?></strong> · Birthday: <strong><?= e($myDob) ?></strong> · Age: <strong><?= e($myAge) ?></strong> (<?= e($patient[5] ?? 'Not specified') ?>)</p>
    </div>
</div>

<div class="pt-profile-grid">
    <!-- Editable Personal Contact Info + Read-Only Medical Identifiers -->
    <section class="panel pt-profile-card">
        <div class="panel-head">
            <div>
                <h2>Personal & medical details</h2>
                <p>Phone number and home address can be updated anytime</p>
            </div>
        </div>

        <form method="post" action="index.php" class="pt-profile-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_patient_profile">
            <input type="hidden" name="return" value="<?= e(url($_GET)) ?>">

            <div class="form-grid three-col">
                <label class="field">
                    <span class="field-label">Full name (read-only)</span>
                    <span class="input-wrap"><input type="text" value="<?= e($ptName) ?>" disabled></span>
                </label>
                <label class="field">
                    <span class="field-label">Birthday (read-only)</span>
                    <span class="input-wrap"><input type="text" value="<?= e($myDob) ?>" disabled></span>
                </label>
                <label class="field">
                    <span class="field-label">Age (read-only)</span>
                    <span class="input-wrap"><input type="text" value="<?= e($myAge) ?>" disabled></span>
                </label>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="field-label">Insurance number (read-only)</span>
                    <span class="input-wrap"><input type="text" value="<?= e($patient[3]) ?>" disabled></span>
                </label>
                <?= field('Phone number', 'phone', '(000) 000-0000', 'text', $patient[2]) ?>
            </div>

            <?= field('Home address', 'address', 'Street, City, State', 'text', $patient[7] ?? '') ?>

            <div class="pt-form-foot">
                <?= button('Save contact details', ['type' => 'submit']) ?>
            </div>
        </form>
    </section>

    <!-- Change Password -->
    <section class="panel pt-profile-card">
        <div class="panel-head">
            <div>
                <h2>Change password</h2>
                <p>Keep your patient portal account secure</p>
            </div>
        </div>

        <form method="post" action="index.php" class="pt-profile-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_patient_password">
            <input type="hidden" name="return" value="<?= e(url($_GET)) ?>">

            <?= field('Current password', 'current_password', 'Enter current password', 'password') ?>
            <?= field('New password', 'new_password', 'Minimum 8 characters', 'password') ?>
            <?= field('Confirm new password', 'confirm_password', 'Re-enter new password', 'password') ?>

            <div class="pt-form-foot">
                <?= button('Update password', ['type' => 'submit', 'variant' => 'secondary']) ?>
            </div>
        </form>
    </section>
</div>
