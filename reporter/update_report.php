<?php
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';

$user = require_auth();

// Only students and faculty can edit reports
if ($user['role'] === 'TSG Personnel') {
    header('Location: ../tsg/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    $report_id   = (int)($_POST['report_id']   ?? 0);
    $component   = trim($_POST['component']    ?? '');
    $description = trim($_POST['description']  ?? '');

    // Validation
    if (!$report_id || empty($component) || empty($description)) {
        $_SESSION['report_error'] = "All fields are required.";
        header('Location: dashboard.php');
        exit;
    }

    // Verify the report belongs to this user and is still Pending
    $checkSQL = "
        SELECT dr.ReportID
        FROM DefectReport dr
        WHERE dr.ReportID = ?
        AND dr.Status = 'Pending'
        AND " . ($user['role'] === 'Student'
            ? "EXISTS (SELECT 1 FROM ReportStudent rs WHERE rs.ReportID = dr.ReportID AND rs.StudentID = ?)"
            : "EXISTS (SELECT 1 FROM ReportFaculty rf WHERE rf.ReportID = dr.ReportID AND rf.FacultyID = ?)") . "
    ";

    $db = get_db();
    $st = $db->prepare($checkSQL);
    $st->execute([$report_id, $user['roleID']]);

    if ($st->fetch()) {
        try {
            $stmt = $db->prepare("
                UPDATE DefectReport
                SET Component = :component, Description = :description
                WHERE ReportID = :report_id
            ");
            $stmt->execute([
                ':component'   => $component,
                ':description' => $description,
                ':report_id'   => $report_id,
            ]);

            $_SESSION['report_success'] = "Report #$report_id has been updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['report_error'] = "Failed to update report: " . $e->getMessage();
        }
    } else {
        $_SESSION['report_error'] = "You can only edit pending reports that belong to you.";
    }
}

header('Location: ' . base_url('reporter/dashboard.php'));
exit;
?>