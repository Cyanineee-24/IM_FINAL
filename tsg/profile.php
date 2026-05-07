<?php
// tsg/profile.php — mirrors tsg-profile-view.fxml + TsgProfileController.java
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';

$user = require_auth('TSG Personnel');

$db = get_db();

// Full user details
$st = $db->prepare('SELECT * FROM `User` WHERE UID = ?');
$st->execute([$user['uid']]);
$u = $st->fetch();

// Activity stats for TSG: reports they were assigned to
$stats = $db->query("
    SELECT
        COUNT(*)                              AS total,
        SUM(Status = 'Pending')               AS pending,
        SUM(Status = 'In-Progress')           AS inprogress,
        SUM(Status = 'Resolved')              AS resolved
    FROM DefectReport
    WHERE AssignedPersonnelID = " . (int)$user['roleID'] . "
")->fetch();

// Get PersonnelID (roleID for TSG = FacultyID, not PersonnelID)
$st2 = $db->prepare('SELECT tp.PersonnelID FROM TSG_Personnel tp WHERE tp.FacultyID = ?');
$st2->execute([$user['roleID']]);
$personnelRow = $st2->fetch();
$personnelId  = $personnelRow['PersonnelID'] ?? null;

$fullName = $u['FirstName'] . ' ' . $u['LastName'];
$initials  = strtoupper(substr($u['FirstName'],0,1) . substr($u['LastName'],0,1));

layout_head('TSG Profile');
layout_sidebar($user, 'profile');
?>

<div class="profile-card-container">
    <div class="card profile-card">
        
        <!-- Maroon Profile Hero -->
        <div class="profile-hero maroon-hero">
            <div class="profile-avatar-wrapper">
                <?= htmlspecialchars($initials) ?>
            </div>
            <div class="profile-hero-text">
                <h2><?= htmlspecialchars(strtoupper($fullName)) ?></h2>
                <p class="subtitle">TSG Personnel</p>
            </div>
            <button class="btn btn-outline-white" style="margin-left:auto; font-size: 10px; font-weight: 700; border-color: rgba(255,255,255,0.3); padding: 0.5rem 1rem;" onclick="openModal()">EDIT PROFILE</button>
        </div>

        <hr class="profile-divider">

        <div class="profile-card-body">
            
            <!-- Personal info -->
            <h3 class="section-title text-maroon" style="margin-bottom: 1rem;">PERSONAL INFORMATION</h3>
            
            <div class="profile-info-grid">
                <div class="info-field">
                    <label>PERSONNEL ID:</label>
                    <div class="value-box"><?= htmlspecialchars($personnelId ?? '—') ?></div>
                </div>
                <div class="info-field">
                    <label>FULL NAME:</label>
                    <div class="value-box"><?= htmlspecialchars($fullName) ?></div>
                </div>
                <div class="info-field">
                    <label>EMAIL:</label>
                    <div class="value-box"><?= htmlspecialchars($u['Email']) ?></div>
                </div>
                <div class="info-field">
                    <label>POSITION:</label>
                    <div class="value-box">TSG Personnel</div>
                </div>
                <div class="info-field">
                    <label>CONTACT:</label>
                    <div class="value-box"><?= htmlspecialchars($u['Contact'] ?? '—') ?></div>
                </div>
            </div>

            <!-- Report Activity Stats -->
            <h3 class="section-title text-maroon" style="margin-top: 2rem; margin-bottom: 1rem;">MY ASSIGNED ACTIVITY</h3>
            
            <div class="stats-row profile-stats-row">
                <div class="stat-card border-purple">
                    <span class="stat-value text-purple"><?= (int)($stats['total'] ?? 0) ?></span>
                    <span class="stat-label">TOTAL ASSIGNED</span>
                </div>
                <div class="stat-card border-pink">
                    <span class="stat-value text-pink"><?= (int)($stats['pending'] ?? 0) ?></span>
                    <span class="stat-label">PENDING</span>
                </div>
                <div class="stat-card border-lightblue">
                    <span class="stat-value text-lightblue"><?= (int)($stats['inprogress'] ?? 0) ?></span>
                    <span class="stat-label">IN-PROGRESS</span>
                </div>
                <div class="stat-card border-lightgreen">
                    <span class="stat-value text-lightgreen"><?= (int)($stats['resolved'] ?? 0) ?></span>
                    <span class="stat-label">RESOLVED</span>
                </div>
            </div>

            <div style="margin-top: 2rem; text-align: right;">
                <a href="../actions/do_logout.php" class="btn btn-maroon" style="font-size: 10px; font-weight: 700; padding: 0.5rem 1.5rem; letter-spacing: 0.5px; border-radius: 4px;">LOG OUT</a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal (mirrors EditProfileModalController) -->
<div class="modal-overlay hidden" id="editModal">
    <div class="modal-box">
        <div class="modal-title">Edit Profile</div>
        <form method="POST" action="../actions/do_update_profile.php">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input class="form-control" type="text" name="firstName"
                       value="<?= htmlspecialchars($u['FirstName']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Middle Name</label>
                <input class="form-control" type="text" name="middleName"
                       value="<?= htmlspecialchars($u['MiddleName'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input class="form-control" type="text" name="lastName"
                       value="<?= htmlspecialchars($u['LastName']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email"
                       value="<?= htmlspecialchars($u['Email']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact</label>
                <input class="form-control" type="tel" name="contact"
                       value="<?= htmlspecialchars($u['Contact'] ?? '') ?>">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal()  { document.getElementById('editModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('editModal').classList.add('hidden'); }
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php layout_foot(); ?>
