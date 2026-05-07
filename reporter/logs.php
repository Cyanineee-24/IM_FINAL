<?php
// reporter/logs.php — mirrors reporter-logs-view.fxml + ReporterLogsController.java
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth();
if ($user['role'] === 'TSG Personnel') redirect_to_dashboard('TSG Personnel');

$db = get_db();

$sql = "
    SELECT dr.ReportID, l.LabName, w.WorkstationNo, dr.Component, dr.Status,
           COALESCE(CONCAT(u.FirstName,' ',u.LastName), 'Unassigned') AS AssignedTo,
           dr.DateFiled
    FROM DefectReport dr
    JOIN Workstation w ON w.WorkstationID = dr.WorkstationID
    JOIN Lab l         ON l.LabID         = w.LabID
    LEFT JOIN TSG_Personnel tp ON tp.PersonnelID = dr.AssignedPersonnelID
    LEFT JOIN Faculty fac2     ON fac2.FacultyID = tp.FacultyID
    LEFT JOIN `User` u         ON u.UID          = fac2.UID
    WHERE " . ($user['role'] === 'Student'
        ? "EXISTS (SELECT 1 FROM ReportStudent rs WHERE rs.ReportID = dr.ReportID AND rs.StudentID = ?)"
        : "EXISTS (SELECT 1 FROM ReportFaculty rf WHERE rf.ReportID = dr.ReportID AND rf.FacultyID = ?)") . "
    ORDER BY dr.DateFiled DESC
";
$st = $db->prepare($sql);
$st->execute([$user['roleID']]);
$reports = $st->fetchAll();

layout_head('My Logs');
layout_sidebar($user, 'logs');
?>

<div class="card logs-card" style="margin-top: 1rem; padding: 1.5rem;">
    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">MY RECENT REPORTS</h3>
    <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
    
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>REPORT NO.</th>
                    <th>LABORATORY</th>
                    <th>COMPONENT</th>
                    <th>STATUS</th>
                    <th>ASSIGNED TO</th>
                    <th>DATE</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:2rem">You haven't submitted any reports yet.</td></tr>
            <?php else: foreach ($reports as $row): ?>
                <tr>
                    <td class="col-id">#R-<?= str_pad($row['ReportID'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['LabName']) ?> - <?= htmlspecialchars($row['WorkstationNo']) ?></td>
                    <td><?= htmlspecialchars($row['Component']) ?></td>
                    <td><?= status_pill($row['Status']) ?></td>
                    <td class="<?= $row['AssignedTo'] === 'Unassigned' ? 'col-unassigned' : 'col-assigned' ?>" style="font-family: 'DM Sans', sans-serif; font-size: 11px;">
                        <?= htmlspecialchars($row['AssignedTo']) ?>
                    </td>
                    <td><?= date('m/d/y', strtotime($row['DateFiled'])) ?></td>
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
