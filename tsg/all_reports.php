<?php
// tsg/all_reports.php — mirrors tsg-all-reports-view.fxml + TsgAllReportsController.java
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth('TSG Personnel');

$db = get_db();

// All reports with full join info (mirrors tblAllReports columns)
$reports = $db->query("
    SELECT
        dr.ReportID,
        l.LabName,
        COALESCE(
            CONCAT(su.FirstName,' ',su.LastName),
            CONCAT(fu.FirstName,' ',fu.LastName),
            'Unknown'
        )                                   AS Reporter,
        COALESCE(
            (SELECT 'Student' FROM ReportStudent rs2
             JOIN Student s2 ON s2.StudentID = rs2.StudentID
             WHERE rs2.ReportID = dr.ReportID LIMIT 1),
            (SELECT 'Faculty' FROM ReportFaculty rf2
             WHERE rf2.ReportID = dr.ReportID LIMIT 1),
            'Unknown'
        )                                   AS ReporterRole,
        w.WorkstationNo,
        dr.Component,
        dr.Status,
        COALESCE(CONCAT(pu.FirstName,' ',pu.LastName), 'Unassigned') AS AssignedTo,
        dr.DateFiled
    FROM DefectReport dr
    JOIN Workstation w ON w.WorkstationID = dr.WorkstationID
    JOIN Lab l         ON l.LabID         = w.LabID
    LEFT JOIN ReportStudent rs ON rs.ReportID  = dr.ReportID
    LEFT JOIN Student stu      ON stu.StudentID = rs.StudentID
    LEFT JOIN `User` su        ON su.UID        = stu.UID
    LEFT JOIN ReportFaculty rf ON rf.ReportID  = dr.ReportID
    LEFT JOIN Faculty fac2     ON fac2.FacultyID = rf.FacultyID
    LEFT JOIN `User` fu        ON fu.UID         = fac2.UID
    LEFT JOIN TSG_Personnel tp ON tp.PersonnelID = dr.AssignedPersonnelID
    LEFT JOIN Faculty pf       ON pf.FacultyID   = tp.FacultyID
    LEFT JOIN `User` pu        ON pu.UID          = pf.UID
    ORDER BY dr.DateFiled DESC
")->fetchAll();

layout_head('All Reports');
layout_sidebar($user, 'reports');
?>

<div class="page-header">
    <h1 class="page-title">All Reports</h1>
    <p class="page-subtitle">Complete list of every defect report in the system.</p>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Report No.</th>
                    <th>Laboratory</th>
                    <th>Reporter</th>
                    <th>Role</th>
                    <th>PC No.</th>
                    <th>Component</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Date Filed</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="10" class="text-center text-muted" style="padding:2rem">No reports in the system yet.</td></tr>
            <?php else: foreach ($reports as $r): ?>
                <tr>
                    <td class="col-id">#<?= $r['ReportID'] ?></td>
                    <td><?= htmlspecialchars($r['LabName']) ?></td>
                    <td><?= htmlspecialchars($r['Reporter']) ?></td>
                    <td class="<?= $r['ReporterRole'] === 'Student' ? 'col-student' : 'col-faculty' ?>">
                        <?= htmlspecialchars($r['ReporterRole']) ?>
                    </td>
                    <td><?= htmlspecialchars($r['WorkstationNo']) ?></td>
                    <td><?= htmlspecialchars($r['Component']) ?></td>
                    <td><?= status_pill($r['Status']) ?></td>
                    <td class="<?= $r['AssignedTo'] === 'Unassigned' ? 'col-unassigned' : 'col-assigned' ?>">
                        <?= htmlspecialchars($r['AssignedTo']) ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($r['DateFiled'])) ?></td>
                    <td>
                        <?php if ($r['Status'] === 'In-Progress'): ?>
                            <form method="POST" action="<?= base_url('actions/do_update_status.php') ?>" style="display:inline">
                                <input type="hidden" name="report_id" value="<?= $r['ReportID'] ?>">
                                <input type="hidden" name="new_status" value="Resolved">
                                <input type="hidden" name="redirect" value="<?= base_url('tsg/all_reports.php') ?>">
                                <button type="submit" class="btn btn-primary btn-sm"
                                    onclick="return confirm('Mark Report #<?= $r['ReportID'] ?> as Resolved?')">
                                    MARK RESOLVED
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
layout_foot();

function status_pill(string $status): string {
    $cls = match($status) {
        'In-Progress' => 'pill-inprogress',
        'Resolved'    => 'pill-resolved',
        default       => 'pill-pending',
    };
    return '<span class="pill ' . $cls . '">' . htmlspecialchars($status) . '</span>';
}
