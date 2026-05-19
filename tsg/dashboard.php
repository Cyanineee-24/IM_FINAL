<?php
// tsg/dashboard.php — Simplified for the new 3-tier architecture
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth('TSG Personnel');

$db = get_db();

// ── Pagination settings ───────────────────────────────────────────────────────
$limit  = 6;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// ── Total count for this personnel ───────────────────────────────────────────
$totalCount = $db->query("
    SELECT COUNT(*) AS cnt
    FROM DefectReport dr
    JOIN TSG_Personnel tp ON tp.PersonnelID = dr.AssignedPersonnelID
    WHERE tp.FacultyID = " . (int)$user['roleID'] . "
")->fetch()['cnt'];

$totalPages = max(1, ceil($totalCount / $limit));

// ── Assigned reports to this specific TSG Personnel ──────────────────────────
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
           CONCAT(pu.FirstName,' ',pu.LastName) AS Personnel,
           dr.AssignedPersonnelID,
           dr.Status,
           dr.DateFiled,
           dr.Description
    FROM DefectReport dr
    JOIN Workstation w ON w.WorkstationID = dr.WorkstationID
    JOIN Lab l         ON l.LabID         = w.LabID
    LEFT JOIN ReportStudent rs ON rs.ReportID   = dr.ReportID
    LEFT JOIN Student stu      ON stu.StudentID = rs.StudentID
    LEFT JOIN `User` ru        ON ru.UID        = stu.UID
    LEFT JOIN TSG_Personnel tp ON tp.PersonnelID = dr.AssignedPersonnelID
    LEFT JOIN Faculty pf       ON pf.FacultyID  = tp.FacultyID
    LEFT JOIN `User` pu        ON pu.UID         = pf.UID
    WHERE tp.FacultyID = " . (int)$user['roleID'] . "
    ORDER BY dr.DateFiled DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

layout_head('TSG Dashboard');
layout_sidebar($user, 'dashboard');
?>

<!-- Assigned reports table -->
<div class="card logs-card" style="position:relative; margin-top: 1.5rem; padding: 1.5rem;" id="assignedCard">
    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">MY ASSIGNED TASKS</h3>
    <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>REPORT NO.</th><th>REPORTER</th><th>COMPONENT</th>
                    <th>ASSIGNED TO</th><th>STATUS</th><th>SINCE</th><th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($assigned)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:1.5rem">No assigned tasks right now. Great job! ✅</td></tr>
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

    <!-- Pagination controls -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-controls">
        <?php if ($page > 1): ?>
            <a class="page-btn" href="?page=<?= $page - 1 ?>#assignedCard">&lt;&lt;</a>
        <?php else: ?>
            <span class="page-btn disabled">&lt;&lt;</span>
        <?php endif; ?>

        <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>

        <?php if ($page < $totalPages): ?>
            <a class="page-btn" href="?page=<?= $page + 1 ?>#assignedCard">&gt;&gt;</a>
        <?php else: ?>
            <span class="page-btn disabled">&gt;&gt;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Detail overlay -->
    <div class="detail-overlay hidden" id="assignedDetailOverlay">
        <div class="detail-box">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
                <div class="modal-title" style="margin:0">Task Details</div>
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

            <div class="form-group">
                <label class="form-label">Description</label>
                <div class="value" id="aDetailDesc" style="padding:.6rem .75rem;background:#f5f5f5;border-radius:6px;font-size:13px;min-height:48px"></div>
            </div>

            <div class="modal-actions" style="margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeAssignedDetail()">Close</button>
                <!-- Triggers confirmation modal instead of submitting directly -->
                <button type="button" class="btn btn-primary" id="btnResolve"
                    style="background:#2E7D32; border-color:#2E7D32;"
                    onclick="openResolveConfirm()">Mark as Resolved</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Resolve Confirmation Modal ─────────────────────────────────────────── -->
<div id="resolveConfirmOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:2rem; width:100%; max-width:420px; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
            <h2 style="font-family:'Montserrat',sans-serif; font-size:15px; font-weight:800; color:var(--maroon); margin:0; letter-spacing:0.5px;">CONFIRM RESOLUTION</h2>
            <button onclick="closeResolveConfirm()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#999;">✕</button>
        </div>
        <hr style="border:0; border-top:2px solid var(--gold); margin-bottom:1.25rem;">
        <p style="font-size:14px; color:#1a1a1a; margin-bottom:0.5rem;">
            Mark Report <strong id="confirmResolveId"></strong> as <strong style="color:#2E7D32;">Resolved</strong>?
        </p>
        <p style="font-size:12px; color:#888; margin-bottom:1.5rem;">This confirms the issue has been fixed. This action cannot be undone.</p>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <button type="button" onclick="closeResolveConfirm()" class="btn btn-outline" style="font-size:11px; font-weight:700; padding:0.5rem 1.25rem;">CANCEL</button>
            <button type="button" onclick="submitResolve()" class="btn btn-primary" style="font-size:11px; font-weight:700; padding:0.5rem 1.25rem; background:#2E7D32; border-color:#2E7D32;">CONFIRM</button>
        </div>
    </div>
</div>

<!-- Hidden resolve form -->
<form id="resolveForm" method="POST" action="<?= base_url('actions/do_update_status.php') ?>" style="display:none;">
    <input type="hidden" name="report_id"  id="resolveReportId">
    <input type="hidden" name="new_status" value="Resolved">
</form>

<style>
.pagination-controls {
    display: flex; align-items: center; justify-content: flex-end;
    gap: 0.75rem; margin-top: 1rem; padding-top: 0.75rem;
    border-top: 1px solid #eee;
}
.page-btn {
    display: inline-block; padding: 0.3rem 0.75rem;
    background: var(--maroon, #780A0D); color: #fff;
    border-radius: 4px; text-decoration: none;
    font-size: 13px; font-weight: 700; letter-spacing: 1px;
}
.page-btn.disabled { background: #ccc; color: #999; cursor: not-allowed; pointer-events: none; }
.page-info { font-size: 13px; color: var(--maroon, #780A0D); font-weight: 600; }
</style>

<script>
let currentReportId = null;

function openAssignedDetail(r) {
    currentReportId = r.ReportID;
    document.getElementById('aDetailId').textContent       = '#' + r.ReportID;
    document.getElementById('aDetailReporter').textContent = r.Reporter;
    document.getElementById('aDetailRole').textContent     = r.ReporterRole;
    document.getElementById('aDetailLab').textContent      = r.LabName;
    document.getElementById('aDetailWs').textContent       = r.WorkstationNo;
    document.getElementById('aDetailComp').textContent     = r.Component;
    document.getElementById('aDetailStatus').textContent   = r.Status;
    document.getElementById('aDetailDate').textContent     = r.DateFiled;
    document.getElementById('aDetailDesc').textContent     = r.Description || '—';

    // Hide resolve button if already resolved
    document.getElementById('btnResolve').style.display = (r.Status === 'Resolved') ? 'none' : 'inline-block';

    document.getElementById('assignedDetailOverlay').classList.remove('hidden');
}

function closeAssignedDetail() {
    document.getElementById('assignedDetailOverlay').classList.add('hidden');
}

function openResolveConfirm() {
    document.getElementById('confirmResolveId').textContent = '#' + currentReportId;
    document.getElementById('resolveConfirmOverlay').style.display = 'flex';
}

function closeResolveConfirm() {
    document.getElementById('resolveConfirmOverlay').style.display = 'none';
}

function submitResolve() {
    document.getElementById('resolveReportId').value = currentReportId;
    closeResolveConfirm();
    closeAssignedDetail();
    document.getElementById('resolveForm').submit();
}

// Close on backdrop click
document.getElementById('resolveConfirmOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeResolveConfirm();
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