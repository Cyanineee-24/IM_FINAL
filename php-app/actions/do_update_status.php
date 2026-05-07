<?php
// do_update_status.php — POST handler for TSG to update report status
// Allows TSG Personnel to mark reports as In-Progress or Resolved
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_auth('TSG Personnel');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('tsg/dashboard.php'));
    exit;
}

$reportId  = (int) ($_POST['report_id'] ?? 0);
$newStatus = $_POST['new_status'] ?? '';

// Validate inputs
$allowed = ['In-Progress', 'Resolved'];
if ($reportId === 0 || !in_array($newStatus, $allowed, true)) {
    header('Location: ' . base_url('tsg/dashboard.php'));
    exit;
}

$db = get_db();
$st = $db->prepare(
    'UPDATE DefectReport SET Status = ? WHERE ReportID = ?'
);
$st->execute([$newStatus, $reportId]);

// Redirect back to wherever the user came from
$from = $_POST['redirect'] ?? base_url('tsg/dashboard.php');
header('Location: ' . $from);
exit;
