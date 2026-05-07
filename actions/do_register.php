<?php
// do_register.php — POST handler for register.php (step 1)
// Mirrors CreateViewController::handleRegister()
require_once __DIR__ . '/../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';
$reEnter  =      $_POST['re_enter'] ?? '';
$roleBtn  = trim($_POST['role']     ?? '');

// ── Validation (mirrors CreateViewController) ──────────────
if ($email === '') {
    $_SESSION['register_error'] = 'Please enter your university email.';
    header('Location: ../register.php');
    exit;
}
if ($password === '') {
    $_SESSION['register_error'] = 'Please enter a password.';
    header('Location: ../register.php');
    exit;
}
if ($password !== $reEnter) {
    $_SESSION['register_error'] = 'Passwords do not match.';
    header('Location: ../register.php');
    exit;
}
if ($roleBtn === '') {
    $_SESSION['register_error'] = 'Please select a role: Student, Teacher, or Personnel.';
    header('Location: ../register.php');
    exit;
}

// Map button text to AuthService role strings (mirrors switch in CreateViewController)
$roleMap = [
    'Teacher'   => 'Faculty',
    'Personnel' => 'TSG Personnel',
    'Student'   => 'Student',
];
$role = $roleMap[$roleBtn] ?? 'Student';

// Stash step-1 data in session for step 2
$_SESSION['register_step1'] = [
    'email'    => $email,
    'password' => $password,
    'role'     => $role,
];

header('Location: ../register_details.php');
exit;
