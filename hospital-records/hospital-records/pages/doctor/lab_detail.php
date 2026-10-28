<?php
$results = [
    ['White blood cells', '7.2', '4.5–11.0', '10³/μL', 'Normal'],
    ['Red blood cells', '4.8', '4.2–5.4', '10⁶/μL', 'Normal'],
    ['Hemoglobin', '13.8', '12.0–16.0', 'g/dL', 'Normal'],
    ['Platelets', '245', '150–450', '10³/μL', 'Normal'],
];
?>
<a class="back-link" href="<?= e(url(['page' => 'lab-results'])) ?>">← Back to lab results</a>

<?= page_title(
    'Complete blood count',
    'Test #CBC-8421 · Collected March 20, 2025 at 08:15',
    notify_button('Print result', 'Press Ctrl/Cmd + P to print this result', ['variant' => 'secondary', 'icon' => 'printer'])
) ?>

<div class="lab-summary">
    <div><span>Patient</span><strong>Eleanor Rigby</strong><p>#PT-001842</p></div>
    <div><span>Ordered by</span><strong>Dr. James Wilson</strong><p>Cardiology</p></div>
    <div><span>Status</span><?= badge('Completed', 'success') ?><p>Mar 20 at 10:42</p></div>
</div>

<?= table_open(['Component', 'Result', 'Reference range', 'Unit', 'Flag']) ?>
<?php foreach ($results as $r): ?>
    <tr>
        <td><strong><?= e($r[0]) ?></strong></td>
        <td><strong><?= e($r[1]) ?></strong></td>
        <td><?= e($r[2]) ?></td>
        <td><?= e($r[3]) ?></td>
        <td><?= badge($r[4], 'success') ?></td>
    </tr>
<?php endforeach; ?>
<?= table_close('4 results', false) ?>

<div class="panel lab-note">
    <h2>Clinical note</h2>
    <p>All measured components are within the normal reference range. No immediate follow-up is required.</p>
</div>
