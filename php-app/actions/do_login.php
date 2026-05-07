<?php
// do_login.php — POST handler for login.php
// Mirrors LoginController::handleSignIn() + AuthService::login()
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter your email and password.';
    header('Location: ../login.php');
    exit;
}

$user = auth_login($email, $password);

if ($user === null) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ../login.php');
    exit;
}

session_set_user($user);
redirect_to_dashboard($user['role']);
