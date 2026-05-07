<?php
// reporter/dashboard.php
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth();
// Redirect TSG to their own dashboard
if ($user['role'] === 'TSG Personnel') redirect_to_dashboard('TSG Personnel');

$db = get_db();

// ── Stat counts
$statsSQL = "
    SELECT
        COUNT(*)                                            AS total,
        SUM(dr.Status = 'Pending')                          AS pending,
        SUM(dr.Status = 'In-Progress')                      AS inprogress,
        SUM(dr.Status = 'Resolved')                         AS resolved
    FROM DefectReport dr
    WHERE " . ($user['role'] === 'Student'
        ? "EXISTS (SELECT 1 FROM ReportStudent rs WHERE rs.ReportID = dr.ReportID AND rs.StudentID = ?)"
        : "EXISTS (SELECT 1 FROM ReportFaculty rf WHERE rf.ReportID = dr.ReportID AND rf.FacultyID = ?)") . "
";
$st = $db->prepare($statsSQL);
$st->execute([$user['roleID']]);
$stats = $st->fetch();

// ── Recent 5 reports
$recentSQL = "
    SELECT dr.ReportID, w.WorkstationNo, l.LabName, dr.Component, dr.Status, dr.DateFiled,
           CONCAT(u.FirstName, ' ', u.LastName) AS AssignedTo
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
    LIMIT 5
";
$st = $db->prepare($recentSQL);
$st->execute([$user['roleID']]);
$recent = $st->fetchAll();

$success = $_SESSION['report_success'] ?? null;
unset($_SESSION['report_success']);

layout_head('Dashboard');
layout_sidebar($user, 'dashboard');
?>

<!-- Dashboard content centered -->
<div class="dashboard-centered">

    <!-- /Main Column -->
    <div class="dashboard-main">
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Yellow Hero banner -->
        <div class="hero-banner reporter-hero">
            <div class="hero-text">
                <span class="hero-subtitle">NGE LABORATORY</span>
                <h2>PLEASE REPORT YOUR ISSUES WITH THE COMPUTERS HERE</h2>
                <p>Help keep the NGE labs running. If you notice a broken workstation or faulty component, submit a defect report so TSG can fix it right away.</p>
            </div>
            <a href="report_defect.php" class="btn btn-gold hero-btn">SUBMIT A REPORT</a>
        </div>

        <h3 class="section-title">MY REPORT SUMMARY</h3>
        
        <!-- Stats row with top colored borders -->
        <div class="stats-row">
            <div class="stat-card border-maroon">
                <span class="stat-value"><?= (int)($stats['total'] ?? 0) ?></span>
                <span class="stat-label">TOTAL FILED</span>
            </div>
            <div class="stat-card border-yellow">
                <span class="stat-value"><?= (int)($stats['pending'] ?? 0) ?></span>
                <span class="stat-label">PENDING</span>
            </div>
            <div class="stat-card border-blue">
                <span class="stat-value"><?= (int)($stats['inprogress'] ?? 0) ?></span>
                <span class="stat-label">IN-PROGRESS</span>
            </div>
            <div class="stat-card border-green">
                <span class="stat-value"><?= (int)($stats['resolved'] ?? 0) ?></span>
                <span class="stat-label">RESOLVED</span>
            </div>
        </div>

        <div class="card recent-reports-card">
            <div class="card-header">
                <span class="card-title">MY RECENT REPORTS</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>REPORT NO.</th>
                            <th>LABORATORY</th>
                            <th>COMPONENT</th>
                            <th>STATUS</th>
                            <th>DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="5" class="text-center text-muted" style="padding:2rem">No reports yet.</td></tr>
                    <?php else: foreach ($recent as $row): ?>
                        <tr>
                            <td class="col-id">#R-<?= str_pad($row['ReportID'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($row['LabName']) ?></td>
                            <td><?= htmlspecialchars($row['Component']) ?></td>
                            <td><?= status_pill($row['Status']) ?></td>
                            <td><?= date('m/d/y', strtotime($row['DateFiled'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer" style="text-align: right; padding: 1rem 1.5rem;">
                <a href="logs.php" class="view-more-link">VIEW MORE</a>
            </div>
        </div>
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
?>
