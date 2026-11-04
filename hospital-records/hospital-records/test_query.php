<?php
require_once __DIR__ . '/includes/db.php';
$pdo = db();

// Fix any patient_name with '+' in medical_records
$pdo->exec("UPDATE medical_records SET patient_name = REPLACE(patient_name, '+', ' ') WHERE patient_name LIKE '%+%'");

// Check if BILL-1004 exists, if not, create it for Meryll Joy Evangelista with the Lipid panel
$hasBill1004 = $pdo->query("SELECT COUNT(*) FROM bills WHERE bill_no = 'BILL-1004'")->fetchColumn();
if (!$hasBill1004) {
    $items = [
        ['Doctor Consultation Fee (Consultation)', 1, '$150.00', '$150.00'],
        ['Lipid panel (Diagnostic Lab Order)', 1, '$65.00', '$65.00']
    ];
    $ins = $pdo->prepare("INSERT INTO bills (bill_no, patient_name, amount, paid_amount, status, issued_date, items_json) VALUES ('BILL-1004', 'Meryll Joy Evangelista', '$215.00', '0.00', 'Unpaid', 'Oct 29, 2026', :it)");
    $ins->execute([':it' => json_encode($items)]);
    echo "Created BILL-1004 for Meryll Joy Evangelista with Consultation ($150) + Lipid panel ($65) = $215.00\n";
} else {
    echo "BILL-1004 already exists\n";
}

// Print all bills
print_r($pdo->query('SELECT bill_no, patient_name, amount, paid_amount, status, items_json FROM bills')->fetchAll(PDO::FETCH_ASSOC));
