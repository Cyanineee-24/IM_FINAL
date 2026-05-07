<?php
require_once __DIR__ . '/php-app/includes/db.php';
$db = get_db();
$tsg = $db->query("SELECT u.Email FROM User u JOIN Faculty f ON f.UID = u.UID JOIN TSG_Personnel tp ON tp.FacultyID = f.FacultyID LIMIT 1")->fetch();
echo $tsg['Email'];
