<?php
// do_submit_report.php — POST handler for reporter/report_defect.php
// Mirrors ReportDefectController::handleSubmit()
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$user = require_auth(); // any reporter role

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../reporter/report_defect.php');
    exit;
}

$workstationId = (int) ($_POST['workstation_id'] ?? 0);
$component     = trim($_POST['component']         ?? '');
$description   = trim($_POST['description']       ?? '');

$validComponents = [
    'MOUSE'       => 'Mouse',
    'KEYBOARD'    => 'Keyboard',
    'DISPLAY'     => 'Display',
    'RAM'         => 'RAM',
    'SYSTEM UNIT' => 'System Unit',
    'AUDIO'       => 'Audio'
];

if ($workstationId === 0 || $component === '' || !isset($validComponents[$component])) {
    $_SESSION['report_error'] = 'Please fill in all required fields (lab, workstation, and component).';
    header('Location: ../reporter/report_defect.php');
    exit;
}

$dbComponent = $validComponents[$component];

$db = get_db();

// Insert into DefectReport
$st = $db->prepare(
    'INSERT INTO DefectReport (Status, Component, Description, WorkstationID)
     VALUES (\'Pending\', ?, ?, ?)'
);
$st->execute([$dbComponent, $description ?: null, $workstationId]);
$reportId = (int) $db->lastInsertId();

// Link to reporter
if ($user['role'] === 'Student') {
    $st = $db->prepare('INSERT INTO ReportStudent (ReportID, StudentID) VALUES (?, ?)');
    $st->execute([$reportId, $user['roleID']]);
} else {
    // Faculty or TSG Personnel filing a report
    $st = $db->prepare('INSERT INTO ReportFaculty (ReportID, FacultyID) VALUES (?, ?)');
    $st->execute([$reportId, $user['roleID']]);
}

$_SESSION['report_success'] = 'Report submitted successfully!';
header('Location: ../reporter/dashboard.php');
exit;
