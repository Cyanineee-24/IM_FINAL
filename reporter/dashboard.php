<?php
// reporter/dashboard.php
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth();
if ($user['role'] === 'TSG Personnel') redirect_to_dashboard('TSG Personnel');

$db = get_db();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    $report_id = (int)$_POST['report_id'];
    
    $checkSQL = "
        SELECT dr.ReportID, dr.Status
        FROM DefectReport dr
        WHERE dr.ReportID = ?
        AND dr.Status = 'Pending'
        AND " . ($user['role'] === 'Student'
            ? "EXISTS (SELECT 1 FROM ReportStudent rs WHERE rs.ReportID = dr.ReportID AND rs.StudentID = ?)"
            : "EXISTS (SELECT 1 FROM ReportFaculty rf WHERE rf.ReportID = dr.ReportID AND rf.FacultyID = ?)") . "
    ";
    $st = $db->prepare($checkSQL);
    $st->execute([$report_id, $user['roleID']]);
    
    if ($st->fetch()) {
        try {
            $db->beginTransaction();
            if ($user['role'] === 'Student') {
                $stmt = $db->prepare("DELETE FROM ReportStudent WHERE ReportID = ?");
                $stmt->execute([$report_id]);
            } else {
                $stmt = $db->prepare("DELETE FROM ReportFaculty WHERE ReportID = ?");
                $stmt->execute([$report_id]);
            }
            $stmt = $db->prepare("DELETE FROM DefectReport WHERE ReportID = ?");
            $stmt->execute([$report_id]);
            $db->commit();
            $_SESSION['report_success'] = "Report #$report_id has been deleted successfully.";
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['report_error'] = "Failed to delete report: " . $e->getMessage();
        }
    } else {
        $_SESSION['report_error'] = "You can only delete pending reports that belong to you.";
    }
    
    header('Location: dashboard.php');
    exit;
}

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

// ── Recent reports
$recentSQL = "
    SELECT dr.ReportID, w.WorkstationNo, l.LabName, dr.Component, dr.Status, dr.DateFiled, dr.Description,
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
    LIMIT 10
";
$st = $db->prepare($recentSQL);
$st->execute([$user['roleID']]);
$recent = $st->fetchAll();

$success = $_SESSION['report_success'] ?? null;
$error   = $_SESSION['report_error']   ?? null;
unset($_SESSION['report_success'], $_SESSION['report_error']);

layout_head('Dashboard');
layout_sidebar($user, 'dashboard');
?>

<style>
.action-buttons { display:flex; gap:8px; justify-content:center; }
.btn-icon { background:none; border:none; cursor:pointer; padding:4px 8px; border-radius:4px; transition:all 0.2s ease; font-size:14px; }
.btn-edit  { color:#ffc107; background:rgba(255,193,7,0.1); }
.btn-edit:hover  { background:rgba(255,193,7,0.2); transform:scale(1.05); }
.btn-delete { color:#dc3545; background:rgba(220,53,69,0.1); }
.btn-delete:hover { background:rgba(220,53,69,0.2); transform:scale(1.05); }

/* Shared overlay */
.custom-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s;
}
.custom-overlay.active { display: flex; }
.custom-modal {
    background: #fff;
    border-radius: 12px;
    padding: 2rem;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    animation: slideDown 0.25s;
}
.modal-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}
.modal-top h2 {
    font-family: 'Montserrat', sans-serif;
    font-size: 15px;
    font-weight: 800;
    color: var(--maroon);
    margin: 0;
    letter-spacing: 0.5px;
}
.modal-close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #999;
    line-height: 1;
}
.modal-divider { border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.25rem; }
.modal-actions { display:flex; gap:0.75rem; justify-content:flex-end; margin-top:1.5rem; }
.edit-form-group { margin-bottom: 1rem; }
.edit-form-group label { display:block; margin-bottom:0.4rem; font-size:10px; font-weight:700; color:#888; text-transform:uppercase; }
.edit-form-group select,
.edit-form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
}
.delete-message { font-size:14px; color:#1a1a1a; margin-bottom:0.5rem; }
.delete-warning { font-size:12px; color:#dc3545; font-weight:600; }
@keyframes fadeIn  { from{opacity:0} to{opacity:1} }
@keyframes slideDown { from{transform:translateY(-40px);opacity:0} to{transform:translateY(0);opacity:1} }
</style>

<div class="dashboard-centered">
    <div class="dashboard-main">

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="hero-banner reporter-hero">
            <div class="hero-text">
                <span class="hero-subtitle">NGE LABORATORY</span>
                <h2>PLEASE REPORT YOUR ISSUES WITH THE COMPUTERS HERE</h2>
                <p>Help keep the NGE labs running. If you notice a broken workstation or faulty component, submit a defect report so TSG can fix it right away.</p>
            </div>
            <a href="report_defect.php" class="btn btn-gold hero-btn">SUBMIT A REPORT</a>
        </div>

        <h3 class="section-title">MY REPORT SUMMARY</h3>

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
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recent)): ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding:2rem">No reports yet.</td></tr>
                    <?php else: foreach ($recent as $row): ?>
                        <tr>
                            <td class="col-id">#R-<?= str_pad($row['ReportID'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($row['LabName']) ?></td>
                            <td><?= htmlspecialchars($row['Component']) ?></td>
                            <td><?= status_pill($row['Status']) ?></td>
                            <td><?= date('m/d/y', strtotime($row['DateFiled'])) ?></td>
                            <td class="action-buttons">
                                <?php if ($row['Status'] === 'Pending'): ?>
                                    <button class="btn-icon btn-edit" title="Edit Report"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                            'id'          => $row['ReportID'],
                                            'component'   => $row['Component'],
                                            'description' => $row['Description']
                                        ])) ?>)">Edit</button>
                                    <button class="btn-icon btn-delete" title="Delete Report"
                                        onclick="openDeleteModal(<?= $row['ReportID'] ?>, '<?= htmlspecialchars($row['Component']) ?>')">Delete</button>
                                <?php else: ?>
                                    <span style="color:#999; font-size:12px;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer" style="text-align:right; padding:1rem 1.5rem;">
                <a href="logs.php" class="view-more-link">VIEW MORE</a>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Modal ──────────────────────────────────────────────────────────── -->
<div id="editOverlay" class="custom-overlay">
    <div class="custom-modal">
        <div class="modal-top">
            <h2>EDIT REPORT</h2>
            <button class="modal-close-btn" onclick="closeEditModal()">✕</button>
        </div>
        <hr class="modal-divider">
        <form method="POST" action="update_report.php">
            <input type="hidden" name="report_id" id="edit_report_id">
            <div class="edit-form-group">
                <label for="edit_component">Component</label>
                <select name="component" id="edit_component" required>
                    <option value="MOUSE">Mouse</option>
                    <option value="KEYBOARD">Keyboard</option>
                    <option value="DISPLAY">Display</option>
                    <option value="RAM">RAM</option>
                    <option value="SYSTEM UNIT">System Unit</option>
                    <option value="AUDIO">Audio</option>
                </select>
            </div>
            <div class="edit-form-group">
                <label for="edit_description">Description</label>
                <textarea name="description" id="edit_description" rows="4" required placeholder="Describe the defect..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">CANCEL</button>
                <button type="submit" name="update_report" class="btn btn-maroon">SAVE CHANGES</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Delete Confirmation Modal ──────────────────────────────────────────── -->
<div id="deleteOverlay" class="custom-overlay">
    <div class="custom-modal">
        <div class="modal-top">
            <h2>DELETE REPORT</h2>
            <button class="modal-close-btn" onclick="closeDeleteModal()">✕</button>
        </div>
        <hr class="modal-divider">
        <p class="delete-message">Are you sure you want to delete <strong id="deleteReportLabel"></strong>?</p>
        <p class="delete-warning">⚠ This action cannot be undone.</p>
        <form id="deleteForm" method="POST" action="">
            <input type="hidden" name="delete_report" value="1">
            <input type="hidden" name="report_id" id="delete_report_id">
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">CANCEL</button>
                <button type="submit" class="btn btn-maroon" style="background:#dc3545; border-color:#dc3545;">DELETE</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Edit modal
function openEditModal(report) {
    document.getElementById('edit_report_id').value   = report.id;
    document.getElementById('edit_component').value   = report.component;
    document.getElementById('edit_description').value = report.description;
    document.getElementById('editOverlay').classList.add('active');
}
function closeEditModal() {
    document.getElementById('editOverlay').classList.remove('active');
}

// ── Delete modal
function openDeleteModal(reportId, component) {
    document.getElementById('delete_report_id').value  = reportId;
    document.getElementById('deleteReportLabel').textContent = 'Report #' + reportId + ' (' + component + ')';
    document.getElementById('deleteForm').action = window.location.href;
    document.getElementById('deleteOverlay').classList.add('active');
}
function closeDeleteModal() {
    document.getElementById('deleteOverlay').classList.remove('active');
}

// Close on backdrop click
document.getElementById('editOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.getElementById('deleteOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

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