<?php
// tsg/dashboard.php — mirrors tsg-main-view.fxml + TsgMainController.java
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth('Admin');

$db = get_db();

// ── Stat counts ──────────────────────────────────────────────────────────────
$stats = $db->query("
    SELECT
        COUNT(*)                              AS total,
        SUM(Status = 'Pending')               AS unassigned,
        SUM(Status = 'In-Progress')           AS inprogress,
        SUM(Status = 'Resolved' AND DATE(DateFiled) = CURDATE()) AS resolvedToday
    FROM DefectReport
")->fetch();

// ── Assigned reports ─────────────────────────────────────────────────────────
$assigned = $db->query("
    SELECT dr.ReportID,
           COALESCE(CONCAT(ru.FirstName,' ',ru.LastName), 'Unknown') AS Reporter,
           COALESCE(
               (SELECT 'Student' FROM ReportStudent rs2
                JOIN Student s2 ON s2.StudentID = rs2.StudentID
                WHERE rs2.ReportID = dr.ReportID LIMIT 1),
               (SELECT 'Faculty' FROM ReportFaculty rf2
                WHERE rf2.ReportID = dr.ReportID LIMIT 1),
               'Unknown'
           ) AS ReporterRole,
           dr.Component,
           l.LabName,
           w.WorkstationNo,
           CONCAT(pu.FirstName,' ',pu.LastName)                       AS Personnel,
           dr.AssignedPersonnelID,
           dr.Status,
           dr.DateFiled,
           dr.Description
    FROM DefectReport dr
    JOIN Workstation w ON w.WorkstationID = dr.WorkstationID
    JOIN Lab l         ON l.LabID         = w.LabID
    LEFT JOIN ReportStudent rs ON rs.ReportID = dr.ReportID
    LEFT JOIN Student stu      ON stu.StudentID = rs.StudentID
    LEFT JOIN `User` ru        ON ru.UID = stu.UID
    LEFT JOIN TSG_Personnel tp ON tp.PersonnelID = dr.AssignedPersonnelID
    LEFT JOIN Faculty pf       ON pf.FacultyID   = tp.FacultyID
    LEFT JOIN `User` pu        ON pu.UID          = pf.UID
    WHERE dr.AssignedPersonnelID IS NOT NULL
    ORDER BY dr.DateFiled DESC
    LIMIT 20
")->fetchAll();

// ── Unassigned reports ───────────────────────────────────────────────────────
$unassigned = $db->query("
    SELECT dr.ReportID, dr.Component, l.LabName, w.WorkstationNo, dr.DateFiled,
           COALESCE(CONCAT(su.FirstName,' ',su.LastName),
                    CONCAT(fu.FirstName,' ',fu.LastName), 'Unknown') AS Reporter,
           COALESCE(
               (SELECT 'Student' FROM ReportStudent rs
                JOIN Student s ON s.StudentID = rs.StudentID
                WHERE rs.ReportID = dr.ReportID LIMIT 1),
               (SELECT 'Faculty' FROM ReportFaculty rf
                WHERE rf.ReportID = dr.ReportID LIMIT 1),
               'Unknown'
           ) AS ReporterRole,
           dr.Description
    FROM DefectReport dr
    JOIN Workstation w ON w.WorkstationID = dr.WorkstationID
    JOIN Lab l         ON l.LabID         = w.LabID
    LEFT JOIN ReportStudent rs ON rs.ReportID  = dr.ReportID
    LEFT JOIN Student stu      ON stu.StudentID = rs.StudentID
    LEFT JOIN `User` su        ON su.UID        = stu.UID
    LEFT JOIN ReportFaculty rf ON rf.ReportID  = dr.ReportID
    LEFT JOIN Faculty fac2     ON fac2.FacultyID = rf.FacultyID
    LEFT JOIN `User` fu        ON fu.UID         = fac2.UID
    WHERE dr.AssignedPersonnelID IS NULL
    ORDER BY dr.DateFiled ASC
")->fetchAll();

// ── TSG Personnel list for assign dropdown ────────────────────────────────────
$personnel = $db->query("
    SELECT tp.PersonnelID, CONCAT(u.FirstName,' ',u.LastName) AS FullName
    FROM TSG_Personnel tp
    JOIN Faculty f ON f.FacultyID = tp.FacultyID
    JOIN `User` u  ON u.UID       = f.UID
    ORDER BY u.FirstName
")->fetchAll();

// ── Reports per lab (bar chart) ───────────────────────────────────────────────
$perLab = $db->query("
    SELECT l.LabName, COUNT(*) AS cnt
    FROM DefectReport dr
    JOIN Workstation w ON w.WorkstationID = dr.WorkstationID
    JOIN Lab l         ON l.LabID         = w.LabID
    GROUP BY l.LabID, l.LabName
")->fetchAll();

// ── Status distribution (pie chart) ──────────────────────────────────────────
$byStatus = $db->query("
    SELECT Status, COUNT(*) AS cnt FROM DefectReport GROUP BY Status
")->fetchAll();

layout_head('TSG Dashboard');
layout_sidebar($user, 'dashboard');
?>



<!-- Alert banner when there are unassigned reports -->
<?php if ((int)$stats['unassigned'] > 0): ?>
<div class="tsg-alert-banner">
    <div class="alert-content">
        <span class="alert-title">ALERT!</span>
        <span class="alert-message"><?= (int)$stats['unassigned'] ?> UNASSIGNED REPORT<?= $stats['unassigned'] == 1 ? '' : 'S' ?> WAITING</span>
    </div>
</div>
<?php endif; ?>

<!-- System Summary Statistics -->
<h3 class="section-title" style="margin-top: 1rem; margin-bottom: 1rem;">SYSTEM SUMMARY STATISTICS</h3>
<div class="stats-row profile-stats-row" style="margin-bottom: 1.5rem;">
    <div class="stat-card border-purple">
        <span class="stat-value text-purple"><?= (int)$stats['total'] ?></span>
        <span class="stat-label">TOTAL REPORTS</span>
    </div>
    <div class="stat-card border-pink">
        <span class="stat-value text-pink"><?= (int)$stats['unassigned'] ?></span>
        <span class="stat-label">UNASSIGNED</span>
    </div>
    <div class="stat-card border-lightblue">
        <span class="stat-value text-lightblue"><?= (int)$stats['inprogress'] ?></span>
        <span class="stat-label">IN-PROGRESS</span>
    </div>
    <div class="stat-card border-lightgreen">
        <span class="stat-value text-lightgreen"><?= (int)$stats['resolvedToday'] ?></span>
        <span class="stat-label">RESOLVED TODAY</span>
    </div>
</div>

<!-- Charts row -->
<div class="charts-row">
    <div class="card logs-card" style="padding: 1.5rem;">
        <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">ISSUES PER LABORATORY</h3>
        <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
        <div class="card-body chart-container">
            <canvas id="barChart"></canvas>
        </div>
    </div>
    <div class="card logs-card" style="padding: 1.5rem;">
        <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">STATUS DISTRIBUTION</h3>
        <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
        <div class="card-body chart-container">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<!-- Assigned reports table -->
<div class="card logs-card" style="position:relative; margin-top: 1.5rem; padding: 1.5rem;" id="assignedCard">
    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">MY ASSIGNED TASKS</h3>
    <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Report No.</th><th>Reporter</th><th>Component</th>
                    <th>Assigned To</th><th>Status</th><th>Since</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($assigned)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:1.5rem">No assigned reports.</td></tr>
            <?php else: foreach ($assigned as $r): ?>
                <tr>
                    <td class="col-id">#<?= $r['ReportID'] ?></td>
                    <td><?= htmlspecialchars($r['Reporter']) ?></td>
                    <td><?= htmlspecialchars($r['Component']) ?></td>
                    <td class="col-assigned"><?= htmlspecialchars($r['Personnel']) ?></td>
                    <td><?= status_pill($r['Status']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['DateFiled'])) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm"
                            onclick="openAssignedDetail(<?= htmlspecialchars(json_encode($r)) ?>)">
                            VIEW
                        </button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Detail overlay for assigned reports (reassign personnel) -->
    <div class="detail-overlay hidden" id="assignedDetailOverlay">
        <div class="detail-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
                <div class="modal-title" style="margin:0">Report Details</div>
                <button onclick="closeAssignedDetail()" style="background:none;border:none;font-size:18px;cursor:pointer">✕</button>
            </div>

            <div class="profile-info-grid" style="grid-template-columns:1fr 1fr;gap:.75rem 1rem;margin-bottom:1.25rem">
                <div class="info-field"><label>Report No.</label><div class="value" id="aDetailId"></div></div>
                <div class="info-field"><label>Reporter</label><div class="value" id="aDetailReporter"></div></div>
                <div class="info-field"><label>Role</label><div class="value" id="aDetailRole"></div></div>
                <div class="info-field"><label>Laboratory</label><div class="value" id="aDetailLab"></div></div>
                <div class="info-field"><label>Workstation</label><div class="value" id="aDetailWs"></div></div>
                <div class="info-field"><label>Component</label><div class="value" id="aDetailComp"></div></div>
                <div class="info-field"><label>Status</label><div class="value" id="aDetailStatus"></div></div>
                <div class="info-field"><label>Date Filed</label><div class="value" id="aDetailDate"></div></div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Description</label>
                <div class="value" id="aDetailDesc" style="padding:.6rem .75rem;background:#f5f5f5;border-radius:6px;font-size:13px;min-height:48px"></div>
            </div>

            <!-- Resolved by (shown only when status is Resolved) -->
            <div id="aResolvedBySection" style="margin-top:1rem;display:none">
                <div class="form-group">
                    <label class="form-label">Resolved by</label>
                    <div class="value" id="aResolvedByName" style="padding:.6rem .75rem;background:#f5f5f5;border-radius:6px;font-size:13px"></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-primary" onclick="closeAssignedDetail()">Done</button>
                </div>
            </div>

            <!-- Action Forms (shown only when status is NOT Resolved) -->
            <div id="aReassignForm" style="margin-top:1rem; display: flex; gap: 1rem; align-items: flex-end;">
                <!-- Reassign personnel form -->
                <form method="POST" action="<?= base_url('actions/do_assign_personnel.php') ?>" style="flex: 1;">
                    <input type="hidden" name="report_id" id="aAssignReportId">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Reassign Personnel</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select class="form-control" name="personnel_id" id="aPersonnelSelect" required style="flex: 1;">
                                <option value="">Select TSG Personnel</option>
                                <?php foreach ($personnel as $p): ?>
                                    <option value="<?= $p['PersonnelID'] ?>"><?= htmlspecialchars($p['FullName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Reassign</button>
                        </div>
                    </div>
                </form>

                <!-- Mark as Resolved form -->
                <form method="POST" action="<?= base_url('actions/do_update_status.php') ?>">
                    <input type="hidden" name="report_id" id="aResolveReportId">
                    <input type="hidden" name="new_status" value="Resolved">
                    <button type="submit" class="btn btn-primary" style="background: #2E7D32; border-color: #2E7D32; height: 38px;">Mark Resolved</button>
                </form>
            </div>
            
            <div class="modal-actions" style="margin-top: 1.5rem;" id="aCancelAction">
                <button type="button" class="btn btn-outline" onclick="closeAssignedDetail()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Unassigned reports table -->
<div class="card logs-card" style="position:relative; margin-top: 1.5rem; padding: 1.5rem;" id="unassignedCard">
    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">UNASSIGNED REPORTS</h3>
    <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Report No.</th><th>Reporter</th><th>Laboratory</th>
                    <th>Component</th><th>Filed</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($unassigned)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:1.5rem">No unassigned reports. ✅</td></tr>
            <?php else: foreach ($unassigned as $r): ?>
                <tr>
                    <td class="col-id">#<?= $r['ReportID'] ?></td>
                    <td><?= htmlspecialchars($r['Reporter']) ?></td>
                    <td><?= htmlspecialchars($r['LabName']) ?></td>
                    <td><?= htmlspecialchars($r['Component']) ?></td>
                    <td><?= date('M d, Y', strtotime($r['DateFiled'])) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm"
                            onclick="openDetail(<?= htmlspecialchars(json_encode($r)) ?>)">
                            ASSIGN
                        </button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Detail overlay (mirrors overlayPane in tsg-main-view.fxml) -->
    <div class="detail-overlay hidden" id="detailOverlay">
        <div class="detail-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
                <div class="modal-title" style="margin:0">Report Details</div>
                <button onclick="closeDetail()" style="background:none;border:none;font-size:18px;cursor:pointer">✕</button>
            </div>

            <div class="profile-info-grid" style="grid-template-columns:1fr 1fr;gap:.75rem 1rem;margin-bottom:1.25rem">
                <div class="info-field"><label>Report No.</label><div class="value" id="detailId"></div></div>
                <div class="info-field"><label>Reporter</label><div class="value" id="detailReporter"></div></div>
                <div class="info-field"><label>Role</label><div class="value" id="detailRole"></div></div>
                <div class="info-field"><label>Laboratory</label><div class="value" id="detailLab"></div></div>
                <div class="info-field"><label>Workstation</label><div class="value" id="detailWs"></div></div>
                <div class="info-field"><label>Component</label><div class="value" id="detailComp"></div></div>
                <div class="info-field"><label>Status</label><div class="value" id="detailStatus"></div></div>
                <div class="info-field"><label>Date Filed</label><div class="value" id="detailDate"></div></div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Description</label>
                <div class="value" id="detailDesc" style="padding:.6rem .75rem;background:#f5f5f5;border-radius:6px;font-size:13px;min-height:48px"></div>
            </div>

            <!-- Assign personnel (mirrors cmbAssignPersonnel + btnAssignPersonnel) -->
            <form method="POST" action="<?= base_url('actions/do_assign_personnel.php') ?>" style="margin-top:1rem">
                <input type="hidden" name="report_id" id="assignReportId">
                <div class="form-group">
                    <label class="form-label">Assign Personnel</label>
                    <select class="form-control" name="personnel_id" required>
                        <option value="">Select TSG Personnel</option>
                        <?php foreach ($personnel as $p): ?>
                            <option value="<?= $p['PersonnelID'] ?>"><?= htmlspecialchars($p['FullName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeDetail()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Bar chart — reports per lab
const labLabels  = <?= json_encode(array_column($perLab, 'LabName')) ?>;
const labCounts  = <?= json_encode(array_column($perLab, 'cnt')) ?>;

// Pie chart — status distribution
const statusData = <?= json_encode(array_column($byStatus, 'cnt')) ?>;
const statusLabels = <?= json_encode(array_column($byStatus, 'Status')) ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Generate multiple colors for the bar chart as requested
    const chartColors = [
        '#780A0D', '#D32F2F', '#F57C00', '#FBC02D', 
        '#388E3C', '#1976D2', '#7B1FA2', '#455A64'
    ];
    
    // Assign colors to bars wrapping around if there are more labs than colors
    const backgroundColors = labCounts.map((_, i) => chartColors[i % chartColors.length]);

    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: labLabels,
            datasets: [{
                label: 'Reports',
                data: labCounts,
                backgroundColor: backgroundColors,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: ['#FFC107', '#1565C0', '#2E7D32'],
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});

function openDetail(r) {
    document.getElementById('detailId').textContent       = '#' + r.ReportID;
    document.getElementById('detailReporter').textContent = r.Reporter;
    document.getElementById('detailRole').textContent     = r.ReporterRole;
    document.getElementById('detailLab').textContent      = r.LabName;
    document.getElementById('detailWs').textContent       = r.WorkstationNo;
    document.getElementById('detailComp').textContent     = r.Component;
    document.getElementById('detailStatus').textContent   = r.Status || 'Pending';
    document.getElementById('detailDate').textContent     = r.DateFiled;
    document.getElementById('detailDesc').textContent     = r.Description || '—';
    document.getElementById('assignReportId').value       = r.ReportID;
    document.getElementById('detailOverlay').classList.remove('hidden');
}
function closeDetail() {
    document.getElementById('detailOverlay').classList.add('hidden');
}

function openAssignedDetail(r) {
    document.getElementById('aDetailId').textContent       = '#' + r.ReportID;
    document.getElementById('aDetailReporter').textContent = r.Reporter;
    document.getElementById('aDetailRole').textContent     = r.ReporterRole;
    document.getElementById('aDetailLab').textContent      = r.LabName;
    document.getElementById('aDetailWs').textContent       = r.WorkstationNo;
    document.getElementById('aDetailComp').textContent     = r.Component;
    document.getElementById('aDetailStatus').textContent   = r.Status;
    document.getElementById('aDetailDate').textContent     = r.DateFiled;
    document.getElementById('aDetailDesc').textContent     = r.Description || '—';
    document.getElementById('aAssignReportId').value       = r.ReportID;
    document.getElementById('aResolveReportId').value      = r.ReportID;

    // Toggle sections based on resolved status
    var isResolved = (r.Status === 'Resolved');
    document.getElementById('aResolvedBySection').style.display = isResolved ? '' : 'none';
    document.getElementById('aReassignForm').style.display      = isResolved ? 'none' : 'flex';
    document.getElementById('aCancelAction').style.display      = isResolved ? 'block' : 'none';

    if (isResolved) {
        document.getElementById('aResolvedByName').textContent = r.Personnel || '—';
    } else {
        // Pre-select the currently assigned personnel
        var sel = document.getElementById('aPersonnelSelect');
        if (r.AssignedPersonnelID) {
            sel.value = r.AssignedPersonnelID;
        }
    }
    document.getElementById('assignedDetailOverlay').classList.remove('hidden');
}
function closeAssignedDetail() {
    document.getElementById('assignedDetailOverlay').classList.add('hidden');
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
