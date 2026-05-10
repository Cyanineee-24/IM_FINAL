<?php
require_once 'includes/db.php';
$db = get_db();
$stmt = $db->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// check User table if exists
try {
    $stmt2 = $db->query('DESCRIBE User');
    echo "\nUser table:\n";
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}

try {
    $stmt2 = $db->query('DESCRIBE Users');
    echo "\nUsers table:\n";
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}

try {
    $stmt3 = $db->query('DESCRIBE Student');
    echo "\nStudent table:\n";
    print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}

try {
    $stmt4 = $db->query('DESCRIBE Faculty');
    echo "\nFaculty table:\n";
    print_r($stmt4->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {}
