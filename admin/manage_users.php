<?php
define('ROOT', __DIR__ . '/..');
require_once ROOT . '/includes/session.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
require_once ROOT . '/includes/auth.php';

$user = require_auth('Admin');
$db = get_db();

// Handle form submission to create new TSG Personnel
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');

    if ($email && $password && $firstName && $lastName) {
        try {
            $db->beginTransaction();
            
            // Insert User
            $st = $db->prepare('INSERT INTO `User` (Email, Password, FirstName, LastName, Contact) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$email, password_hash($password, PASSWORD_DEFAULT), $firstName, $lastName, '00000000000']);
            $uid = $db->lastInsertId();
            
            // Insert Faculty
            $st = $db->prepare('INSERT INTO `Faculty` (UID) VALUES (?)');
            $st->execute([$uid]);
            $facultyId = $db->lastInsertId();
            
            // Insert TSG Personnel
            $st = $db->prepare('INSERT INTO `TSG_Personnel` (FacultyID) VALUES (?)');
            $st->execute([$facultyId]);
            
            $db->commit();
            $success_msg = "TSG Personnel '$firstName $lastName' created successfully!";
        } catch (Exception $e) {
            $db->rollBack();
            $error_msg = "Error creating personnel. Email may already exist.";
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch all TSG Personnel
$personnel = $db->query("
    SELECT tp.PersonnelID, u.FirstName, u.LastName, u.Email
    FROM TSG_Personnel tp
    JOIN Faculty f ON f.FacultyID = tp.FacultyID
    JOIN `User` u ON u.UID = f.UID
    ORDER BY tp.PersonnelID DESC
")->fetchAll();

layout_head('Manage Users');
layout_sidebar($user, 'manage');
?>

<div class="card logs-card" style="padding: 1.5rem; margin-top: 1.5rem;">
    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">CREATE TSG PERSONNEL</h3>
    <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
    
    <?php if ($success_msg): ?>
        <div style="padding: 1rem; background: #E8F5E9; color: #2E7D32; border-radius: 4px; margin-bottom: 1rem; font-weight: 600;">
            <?= htmlspecialchars($success_msg) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_msg): ?>
        <div style="padding: 1rem; background: #FFEBEE; color: #C62828; border-radius: 4px; margin-bottom: 1rem; font-weight: 600;">
            <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="profile-info-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 1.5rem;">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="firstName" required>
            </div>
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="lastName" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-maroon" style="padding: 0.75rem 2rem; font-weight: 700;">Create Personnel</button>
    </form>
</div>

<div class="card logs-card" style="padding: 1.5rem; margin-top: 1.5rem;">
    <h3 style="font-family: 'Montserrat', sans-serif; font-size: 13px; font-weight: 800; color: var(--maroon); margin-bottom: 0.5rem; letter-spacing: 0.5px;">TSG PERSONNEL LIST</h3>
    <hr style="border: 0; border-top: 2px solid var(--gold); margin-bottom: 1.5rem;">
    
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Personnel ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personnel)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:1.5rem">No TSG Personnel found.</td></tr>
                <?php else: foreach ($personnel as $p): ?>
                    <tr>
                        <td class="col-id">#<?= $p['PersonnelID'] ?></td>
                        <td><?= htmlspecialchars($p['FirstName'] . ' ' . $p['LastName']) ?></td>
                        <td><?= htmlspecialchars($p['Email']) ?></td>
                        <td><span class="pill pill-inprogress">TSG Personnel</span></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php layout_foot(); ?>
