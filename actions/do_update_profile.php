<?php
// do_update_profile.php — POST handler for Edit Profile modal
// Mirrors EditProfileModalController::handleSave() + the TODO in profile controllers
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$firstName  = trim($_POST['firstName']  ?? '');
$middleName = trim($_POST['middleName'] ?? '');
$lastName   = trim($_POST['lastName']   ?? '');
$email      = trim($_POST['email']      ?? '');
$contact    = trim($_POST['contact']    ?? '');

if ($firstName === '' || $lastName === '' || $email === '') {
    // Just redirect back — could add flash error
    $back = ($user['role'] === 'TSG Personnel') ? '../tsg/profile.php' : '../reporter/profile.php';
    header('Location: ' . $back);
    exit;
}

$db = get_db();
$st = $db->prepare(
    'UPDATE `User`
     SET FirstName = ?, MiddleName = ?, LastName = ?, Email = ?, Contact = ?
     WHERE UID = ?'
);
$st->execute([
    $firstName,
    $middleName !== '' ? $middleName : null,
    $lastName,
    $email,
    $contact    !== '' ? $contact    : null,
    $user['uid'],
]);

// Refresh session with updated data
$_SESSION['user'] = array_merge($_SESSION['user'], [
    'firstName'  => $firstName,
    'middleName' => $middleName,
    'lastName'   => $lastName,
    'email'      => $email,
]);

$back = ($user['role'] === 'TSG Personnel') ? '../tsg/profile.php' : '../reporter/profile.php';
header('Location: ' . $back);
exit;
