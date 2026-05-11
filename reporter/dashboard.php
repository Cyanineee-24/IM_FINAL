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

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report'])) {
    $report_id = (int)$_POST['report_id'];
    
    // Verify the report belongs to the current user and is pending
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
            
            // Delete from the appropriate reporter table first
            if ($user['role'] === 'Student') {
                $stmt = $db->prepare("DELETE FROM ReportStudent WHERE ReportID = ?");
                $stmt->execute([$report_id]);
            } else {
                $stmt = $db->prepare("DELETE FROM ReportFaculty WHERE ReportID = ?");
                $stmt->execute([$report_id]);
            }
            
            // Then delete from DefectReport
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

// ── Recent reports (increased limit to show more, but we'll limit display to 10)
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
$error = $_SESSION['report_error'] ?? null;
unset($_SESSION['report_success']);
unset($_SESSION['report_error']);

layout_head('Dashboard');
layout_sidebar($user, 'dashboard');
?>

<style>
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}
.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
    font-size: 14px;
}
.btn-edit {
    color: #ffc107;
    background: rgba(255, 193, 7, 0.1);
}
.btn-edit:hover {
    background: rgba(255, 193, 7, 0.2);
    transform: scale(1.05);
}
.btn-delete {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}
.btn-delete:hover {
    background: rgba(220, 53, 69, 0.2);
    transform: scale(1.05);
}
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}
.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    animation: slideDown 0.3s;
}
.modal-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #e0e0e0;
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
.edit-form-group {
    margin-bottom: 1rem;
}
.edit-form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}
.edit-form-group select,
.edit-form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}
.btn-modal-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
}
.btn-modal-save {
    background: var(--maroon, #800000);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
}
</style>

<!-- Dashboard content centered -->
<div class="dashboard-centered">

    <!-- /Main Column -->
    <div class="dashboard-main">
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:1rem;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:1rem;"><?= htmlspecialchars($error) ?></div>
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
                                    <button class="btn-icon btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                        'id' => $row['ReportID'],
                                        'component' => $row['Component'],
                                        'description' => $row['Description']
                                    ])) ?>)" title="Edit Report">
                                        ✏️
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="confirmDelete(<?= $row['ReportID'] ?>)" title="Delete Report">
                                        🗑️
                                    </button>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 12px;">No actions</span>
                                <?php endif; ?>
                            </td>
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

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="update_report.php">
            <div class="modal-header">
                <h3 style="margin:0; color: var(--maroon, #800000);">Edit Report</h3>
            </div>
            <div class="modal-body">
                <input type="hidden" name="report_id" id="edit_report_id">
                
                <div class="edit-form-group">
                    <label for="edit_component">Component *</label>
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
                    <label for="edit_description">Description *</label>
                    <textarea name="description" id="edit_description" rows="4" required placeholder="Describe the defect..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="update_report" class="btn-modal-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="delete_report" value="1">
    <input type="hidden" name="report_id" id="delete_report_id">
</form>

<script>
function openEditModal(report) {
    document.getElementById('edit_report_id').value = report.id;
    document.getElementById('edit_component').value = report.component;
    document.getElementById('edit_description').value = report.description;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function confirmDelete(reportId) {
    if (confirm('Are you sure you want to delete this report? This action cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        form.action = window.location.href;
        document.getElementById('delete_report_id').value = reportId;
        form.submit();
    }
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
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