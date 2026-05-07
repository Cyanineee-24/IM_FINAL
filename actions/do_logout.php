<?php
// do_logout.php — mirrors handleLogOut() in both profile controllers
require_once __DIR__ . '/../includes/session.php';
session_logout();
header('Location: ../login.php');
exit;
