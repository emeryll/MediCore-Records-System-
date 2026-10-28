<?php
/** @var string $modal  Modal name from ?modal=... */
$closeUrl   = url_with(['modal' => null]);
$isEdit     = str_starts_with($modal, 'Edit');
$isUser     = str_contains($modal, 'user');
$isPatient  = $modal === 'Register patient';
$isAppt     = $modal === 'Book appointment';
$isAdmit    = $modal === 'Admit patient';

$subtitle = $isUser ? 'Create an account and assign access.'
    : ($isPatient ? 'Add a new patient to the hospital record.'
    : ($isAppt ? 'Schedule a new patient visit.'
    : ($isAdmit ? 'Assign a patient to an available room.' : 'Update this record.')));

$submit = $isEdit ? 'Save changes' : ($isAppt ? 'Book appointment' : ($isAdmit ? 'Admit patient' : 'Save'));
$doneMsg = $isEdit ? 'Changes saved successfully' : $modal . ' completed successfully';
$patientOptions = ['Select patient', 'Eleanor Rigby', 'Michael Torres', 'Ava Thompson'];
?>
<div class="modal-backdrop">
    <a class="modal-dismiss" href="<?= e($closeUrl) ?>" aria-label="Close dialog"></a>
    <form class="modal <?= ($isPatient || $isUser) ? 'modal-large' : '' ?>" method="post" action="index.php">
        <input type="hidden" name="action" value="notify">
        <input type="hidden" name="message" value="<?= e($doneMsg) ?>">
        <input type="hidden" name="return" value="<?= e($closeUrl) ?>">
        <?= csrf_field() ?>

        <div class="modal-head">
            <div>
                <h2><?= e($modal) ?></h2>
                <p><?= e($subtitle) ?></p>
            </div>
            <a class="icon-btn" href="<?= e($closeUrl) ?>" aria-label="Close"><?= icon('close') ?></a>
        </div>

        <div class="modal-body">
            <?php if ($isUser): ?>
                <div class="form-grid three-col">
                    <?= field('First name', 'first_name', 'First name') ?>
                    <?= field('Middle name', 'middle_name', 'Optional') ?>
                    <?= field('Last name', 'last_name', 'Last name') ?>
                </div>
                <div class="form-grid">
                    <?= field('Email', 'email', 'name@medicore.com', 'email') ?>
                    <?= field('Username', 'username', 'username') ?>
                </div>
                <div class="form-grid">
                    <?= select_field('Role', 'role', ['Doctor', 'Nurse', 'Front desk', 'Administrator']) ?>
                    <?= field('Password', 'password', 'Minimum 8 characters', 'password') ?>
                </div>
                <div class="conditional-fields">
                    <p>Doctor information</p>
                    <div class="form-grid three-col">
                        <?= field('Specialty', 'specialty', 'e.g. Cardiology') ?>
                        <?= field('License number', 'license', 'Medical license') ?>
                        <?= select_field('Department', 'department', ['Cardiology', 'Neurology', 'Pediatrics']) ?>
                    </div>
                </div>
                <label class="toggle-row">
                    <span><strong>Account status</strong><small>User can sign in and access records</small></span>
                    <input type="checkbox" name="active" checked><i></i>
                </label>

            <?php elseif ($isPatient): ?>
                <div class="form-grid three-col">
                    <?= field('First name', 'first_name', 'First name') ?>
                    <?= field('Middle name', 'middle_name', 'Optional') ?>
                    <?= field('Last name', 'last_name', 'Last name') ?>
                </div>
                <div class="form-grid">
                    <?= field('Birthdate', 'birthdate', '', 'date') ?>
                    <?= select_field('Sex', 'sex', ['Select sex', 'Female', 'Male', 'Other']) ?>
                </div>
                <div class="form-grid">
                    <?= field('Phone', 'phone', '(000) 000-0000') ?>
                    <?= select_field('Blood type', 'blood_type', ['Select blood type', 'O+', 'O-', 'A+', 'A-', 'B+', 'AB+']) ?>
                </div>
                <?= field('Address', 'address', 'Street, city, state, ZIP') ?>
                <?= field('Insurance number', 'insurance', 'HIN-000-000') ?>

            <?php elseif ($isAppt): ?>
                <?= select_field('Patient', 'patient', $patientOptions) ?>
                <div class="form-grid">
                    <?= select_field('Department', 'department', ['Cardiology', 'Neurology', 'Pediatrics']) ?>
                    <?= select_field('Doctor', 'doctor', ['Dr. Olivia Bennett', 'Dr. James Wilson']) ?>
                </div>
                <div class="form-grid">
                    <?= field('Date', 'date', '', 'date') ?>
                    <?= select_field('Time slot', 'slot', ['09:00', '10:30', '13:00', '14:30']) ?>
                </div>

            <?php elseif ($isAdmit): ?>
                <?= select_field('Patient', 'patient', $patientOptions) ?>
                <?= select_field('Available room', 'room', ['Room 202 · General Ward', 'Room 215 · Cardiology', 'Room 318 · Orthopedics']) ?>
                <?= textarea_field('Reason for admission', 'reason', 'Enter admission reason and notes...') ?>

            <?php else: ?>
                <?= field('Name', 'name', 'Enter name') ?>
                <?= field('Description', 'description', 'Enter details') ?>
            <?php endif; ?>
        </div>

        <div class="modal-actions">
            <?= button('Cancel', ['variant' => 'secondary', 'href' => $closeUrl]) ?>
            <?= button($submit, ['type' => 'submit']) ?>
        </div>
    </form>
</div>
