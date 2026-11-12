<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/data.php';
require_once __DIR__ . '/../../includes/db.php';

function execute_mrn_migration(): void
{
    $pdo = db();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Running migration on database driver: $driver\n";

    // 1. patients
    $pts = $pdo->query("SELECT id, patient_id, mrn FROM patients WHERE status != 'Archived'")->fetchAll(PDO::FETCH_ASSOC);
    $upPt = $pdo->prepare("UPDATE patients SET patient_id = :pid, mrn = :mrn WHERE id = :id");
    foreach ($pts as $p) {
        $clean = format_mrn($p['mrn'] ?: $p['patient_id']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upPt->execute([':pid' => $clean, ':mrn' => $clean, ':id' => $p['id']]);
        }
    }
    echo "Updated " . count($pts) . " patient records.\n";

    // 2. patient_account_links
    $pal = $pdo->query("SELECT id, patient_id, mrn FROM patient_account_links")->fetchAll(PDO::FETCH_ASSOC);
    $upPal = $pdo->prepare("UPDATE patient_account_links SET patient_id = :pid, mrn = :mrn WHERE id = :id");
    foreach ($pal as $l) {
        $clean = format_mrn($l['mrn'] ?: $l['patient_id']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upPal->execute([':pid' => $clean, ':mrn' => $clean, ':id' => $l['id']]);
        }
    }
    echo "Updated " . count($pal) . " patient account links.\n";

    // 3. appointments
    $app = $pdo->query("SELECT id, patient_id FROM appointments")->fetchAll(PDO::FETCH_ASSOC);
    $upApp = $pdo->prepare("UPDATE appointments SET patient_id = :pid WHERE id = :id");
    foreach ($app as $a) {
        $clean = format_mrn($a['patient_id']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upApp->execute([':pid' => $clean, ':id' => $a['id']]);
        }
    }
    echo "Updated " . count($app) . " appointments.\n";

    // 4. medical_records
    $mr = $pdo->query("SELECT id, patient_id FROM medical_records")->fetchAll(PDO::FETCH_ASSOC);
    $upMr = $pdo->prepare("UPDATE medical_records SET patient_id = :pid WHERE id = :id");
    foreach ($mr as $m) {
        $clean = format_mrn($m['patient_id']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upMr->execute([':pid' => $clean, ':id' => $m['id']]);
        }
    }
    echo "Updated " . count($mr) . " medical records.\n";

    // 5. prescriptions
    $pre = $pdo->query("SELECT id, patient_id FROM prescriptions")->fetchAll(PDO::FETCH_ASSOC);
    $upPre = $pdo->prepare("UPDATE prescriptions SET patient_id = :pid WHERE id = :id");
    foreach ($pre as $pr) {
        $clean = format_mrn($pr['patient_id']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upPre->execute([':pid' => $clean, ':id' => $pr['id']]);
        }
    }
    echo "Updated " . count($pre) . " prescriptions.\n";

    // 6. lab_results
    $lab = $pdo->query("SELECT id, patient_id FROM lab_results")->fetchAll(PDO::FETCH_ASSOC);
    $upLab = $pdo->prepare("UPDATE lab_results SET patient_id = :pid WHERE id = :id");
    foreach ($lab as $lb) {
        $clean = format_mrn($lb['patient_id']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upLab->execute([':pid' => $clean, ':id' => $lb['id']]);
        }
    }
    echo "Updated " . count($lab) . " lab results.\n";

    // 7. admissions
    $adm = $pdo->query("SELECT id, mrn FROM admissions WHERE mrn IS NOT NULL AND mrn != ''")->fetchAll(PDO::FETCH_ASSOC);
    $upAdm = $pdo->prepare("UPDATE admissions SET mrn = :mrn WHERE id = :id");
    foreach ($adm as $ad) {
        $clean = format_mrn($ad['mrn']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upAdm->execute([':mrn' => $clean, ':id' => $ad['id']]);
        }
    }
    echo "Updated " . count($adm) . " admissions.\n";

    // 8. discharges
    $dis = $pdo->query("SELECT id, mrn FROM discharges WHERE mrn IS NOT NULL AND mrn != ''")->fetchAll(PDO::FETCH_ASSOC);
    $upDis = $pdo->prepare("UPDATE discharges SET mrn = :mrn WHERE id = :id");
    foreach ($dis as $di) {
        $clean = format_mrn($di['mrn']);
        if ($clean !== '' && !str_starts_with($clean, 'PROV-')) {
            $upDis->execute([':mrn' => $clean, ':id' => $di['id']]);
        }
    }
    echo "Updated " . count($dis) . " discharges.\n";

    echo "All tables migrated to fixed-width 6-digit MRN format successfully!\n";
}

if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === '--run') {
    execute_mrn_migration();
}
