<?php
// do_assign_personnel.php — POST handler for TSG dashboard "Assign" action
// Mirrors TsgMainController overlay: btnAssignPersonnel + cmbAssignPersonnel
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_auth('TSG Personnel');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../tsg/dashboard.php');
    exit;
}

$reportId    = (int) ($_POST['report_id']     ?? 0);
$personnelId = (int) ($_POST['personnel_id']  ?? 0);

if ($reportId === 0 || $personnelId === 0) {
    header('Location: ../tsg/dashboard.php');
    exit;
}

$db = get_db();
$st = $db->prepare(
    'UPDATE DefectReport
     SET AssignedPersonnelID = ?, Status = \'In-Progress\'
     WHERE ReportID = ?'
);
$st->execute([$personnelId, $reportId]);

header('Location: ../tsg/dashboard.php');
exit;
