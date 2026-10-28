<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icons.php';
require __DIR__ . '/includes/components.php';

/* ---------------------------------------------------------------
 * 1. POST actions (login, logout, state changes) -> redirect (PRG)
 * ------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back and try again.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $return = safe_return($_POST['return'] ?? null);
    $st     = &state();

    switch ($action) {
        case 'login':
            $username = strtolower(trim((string) ($_POST['username'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            if ($username === 'disabled') {
                $_SESSION['login_error'] = 'disabled';
                redirect('index.php');
            }
            if ($username === 'wrong' || $password === '') {
                $_SESSION['login_error'] = 'wrong';
                redirect('index.php');
            }
            session_regenerate_id(true);
            $_SESSION['role'] = str_contains($username, 'staff') ? 'Staff'
                : (str_contains($username, 'doctor') ? 'Doctor' : 'Admin');
            redirect('index.php');

        case 'forgot':
            $_SESSION['reset_sent'] = true;
            redirect('index.php');

        case 'logout':
            $_SESSION = [];
            session_destroy();
            redirect('index.php');

        case 'notify':
            flash((string) ($_POST['message'] ?? 'Done'));
            redirect($return);

        case 'toggle_user':
            foreach ($st['users'] as &$u) {
                if ($u[1] === ($_POST['email'] ?? '')) {
                    $u[3] = $u[3] === 'Active' ? 'Disabled' : 'Active';
                }
            }
            unset($u);
            redirect($return);

        case 'delete_crud':
            $type = (string) ($_POST['type'] ?? '');
            if (isset($st['crud'][$type])) {
                $st['crud'][$type] = array_values(array_filter(
                    $st['crud'][$type],
                    static fn ($r) => $r[0] !== ($_POST['key'] ?? '')
                ));
            }
            redirect($return);

        case 'discharge':
            $st['admissions'] = array_values(array_filter(
                $st['admissions'],
                static fn ($r) => $r[0] !== ($_POST['key'] ?? '')
            ));
            flash('Patient discharged successfully');
            redirect($return);

        case 'reset_demo':
            unset($_SESSION['state']);
            flash('Sample data restored');
            redirect($return);
    }
    redirect($return);
}

/* ---------------------------------------------------------------
 * 2. Not signed in -> login screen
 * ------------------------------------------------------------- */
$role = $_SESSION['role'] ?? null;
if (!in_array($role, ['Admin', 'Staff', 'Doctor'], true)) {
    require __DIR__ . '/pages/login.php';
    exit;
}

/* ---------------------------------------------------------------
 * 3. Routing
 * ------------------------------------------------------------- */
$nav    = nav_for($role);
$routes = routes_for($role);
$page   = (string) ($_GET['page'] ?? 'dashboard');
if (!isset($routes[$page])) {
    $page = 'dashboard';
}

$detail     = $_GET['detail'] ?? null;
$detailFile = $detail !== null ? (details_for($role)[$detail] ?? null) : null;
$modal      = isset($_GET['modal']) ? (string) $_GET['modal'] : null;

$state = &state();

// Archive CSV export (served before any HTML)
if ($role === 'Admin' && $page === 'archive' && isset($_GET['export'])) {
    $period = in_array($_GET['period'] ?? '', ['Daily', 'Monthly', 'Yearly'], true) ? $_GET['period'] : 'Daily';
    $limit  = ['Daily' => 2, 'Monthly' => 4, 'Yearly' => 6][$period];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hospital-archive-' . strtolower($period) . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Archive ID', 'Patient', 'Record type', 'Archived date', 'Archived by']);
    foreach (array_slice(archive_records(), 0, $limit) as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$toast = take_flash();
$title = 'Dashboard';
foreach ($nav as [$label, , $slug]) {
    if ($slug === $page) {
        $title = $label;
    }
}

/* ---------------------------------------------------------------
 * 4. Render
 * ------------------------------------------------------------- */
require __DIR__ . '/layouts/app_header.php';
require __DIR__ . '/' . ($detailFile ?? $routes[$page]);
require __DIR__ . '/layouts/app_footer.php';
