<?php
// register.php — mirrors create-view.fxml + CreateViewController.java
// Step 1: role selection + email + password
require_once __DIR__ . '/includes/session.php';

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);

// Pre-fill from session if user went back from step 2
$old = $_SESSION['register_step1'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — TSG Alert &amp; Repair Network</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Akshar:wght@500&family=DM+Sans:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<div class="login-page">
    <!-- Faded building background -->
    <div class="login-bg">
        <img src="assets/images/building-bg.png" alt="">
    </div>

    <!-- Centered container: logo + branding + card -->
    <div class="login-container">
        <!-- CIT Logo -->
        <div class="login-logo-wrap">
            <img src="assets/images/cit-logo.png" alt="CIT University Seal" class="login-seal">
        </div>

        <!-- Tech-Ops Branding -->
        <div class="login-brand">
            <img src="assets/images/techops_logo.png" alt="CIT Wildcats Tech-Ops" class="login-brand-img">
        </div>

        <!-- Registration card (maroon) -->
        <div class="login-card">
            <h1 class="login-title">CREATE AN ACCOUNT</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="actions/do_register.php" id="registerForm">
                <!-- Role selection -->
                <div class="login-form-group">
                    <label class="login-label">Select Role</label>
                    <div class="role-group">
                        <?php
                        $selectedRole = $old['role'] ?? '';
                        foreach (['Student','Teacher'] as $r):
                            $active = ($selectedRole === $r) ? ' selected' : '';
                        ?>
                        <button type="button" class="role-btn<?= $active ?>"
                                data-role="<?= $r ?>"
                                onclick="selectRole(this)">
                            <?= $r ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($selectedRole) ?>">
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="university_id">University ID:</label>
                    <input class="login-input" type="text" id="university_id" name="university_id"
                           placeholder="e.g. 20-3423-676"
                           pattern="\d{2}-\d{4}-\d{3}"
                           title="Format must be XX-XXXX-XXX"
                           value="<?= htmlspecialchars($old['university_id'] ?? '') ?>"
                           required>
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="email">University Email:</label>
                    <input class="login-input" type="email" id="email" name="email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           required>
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="password">Password:</label>
                    <input class="login-input" type="password" id="password" name="password"
                           required>
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="reEnter">Re-enter Password:</label>
                    <input class="login-input" type="password" id="reEnter" name="re_enter"
                           required>
                </div>

                <button type="submit" class="login-btn" id="register-next-btn">
                    NEXT
                </button>
            </form>
        </div>

        <!-- Footer link below card -->
        <p class="login-footer">
            Already have an account? <a href="login.php" class="login-create-link">Sign In</a>
        </p>
    </div>
</div>

<script>
function selectRole(btn) {
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('roleInput').value = btn.dataset.role;
}
</script>
</body>
</html>
