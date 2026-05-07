<?php
// index.php — entry point, redirects to login
require_once __DIR__ . '/includes/session.php';

$user = session_user();
if ($user) {
    redirect_to_dashboard($user['role']);
}

header('Location: login.php');
exit;
