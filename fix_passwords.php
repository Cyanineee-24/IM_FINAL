<?php
require_once __DIR__ . '/includes/db.php';
$db = get_db();

$updates = [
    1 => '1234',
    2 => '1234',
    3 => 'password123',
    4 => 'admin123',
    5 => 'sim123sim123',
    7 => '123',
    8 => '1234'
];

$stmt = $db->prepare("UPDATE `User` SET Password = ? WHERE UID = ?");

foreach ($updates as $uid => $plain) {
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt->execute([$hash, $uid]);
}

echo "Passwords fixed.\n";
