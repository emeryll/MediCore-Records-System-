<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/components.php';

$_SESSION['role'] = 'Admin';
$_SESSION['user_id'] = 'A-0001';
$_SESSION['user_email'] = 'solonmedea582@gmail.com';
$_SESSION['name'] = 'Medea Solon';

$state = db_load_state();

// Catch all warnings and notices as exceptions to be 100% sure
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "ERROR/WARNING [$errno]: $errstr in $errfile:$errline\n";
    exit(1);
});

ob_start();
require __DIR__ . '/../pages/admin/users.php';
$output = ob_get_clean();

restore_error_handler();

echo "Users page rendered without any warnings or notices! Render length: " . strlen($output) . " bytes\n";
