<?php
// do_register_details.php — POST handler for register_details.php (step 2)
// Mirrors RegisterDetailsViewController::handleConfirm() + AuthService::register()
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register_details.php');
    exit;
}

$step1 = $_SESSION['register_step1'] ?? null;
if (!$step1) {
    header('Location: ../register.php');
    exit;
}

$firstName  = trim($_POST['firstName']  ?? '');
$middleName = trim($_POST['middleName'] ?? '');
$lastName   = trim($_POST['lastName']   ?? '');
$contact    = trim($_POST['contact']    ?? '');
$course     = trim($_POST['course']     ?? '');
$yearLevel  = (int) ($_POST['yearLevel'] ?? 0);
$role       = $step1['role'];

// ── Validation (mirrors RegisterDetailsViewController::handleConfirm) ──
if ($firstName === '') {
    $_SESSION['register_details_error'] = 'Please enter your first name.';
    header('Location: ../register_details.php');
    exit;
}
if ($lastName === '') {
    $_SESSION['register_details_error'] = 'Please enter your last name.';
    header('Location: ../register_details.php');
    exit;
}
if ($role === 'Student') {
    if ($course === '') {
        $_SESSION['register_details_error'] = 'Please enter your course.';
        header('Location: ../register_details.php');
        exit;
    }
    if ($yearLevel === 0) {
        $_SESSION['register_details_error'] = 'Please select your year level.';
        header('Location: ../register_details.php');
        exit;
    }
}

$success = auth_register(
    $step1['email'], $step1['password'],
    $firstName, $middleName, $lastName,
    $contact, $role,
    $course, $yearLevel
);

if ($success) {
    unset($_SESSION['register_step1']);
    $_SESSION['login_success'] = 'Registration successful! Please sign in.';
    header('Location: ../login.php');
} else {
    $_SESSION['register_details_error'] = 'Registration failed. Email may already be in use.';
    header('Location: ../register_details.php');
}
exit;
