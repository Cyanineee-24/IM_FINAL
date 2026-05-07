<?php
// register_details.php — mirrors register-details-view.fxml + RegisterDetailsViewController.java
// Step 2: personal details
require_once __DIR__ . '/includes/session.php';

// Must have step 1 data in session
$step1 = $_SESSION['register_step1'] ?? null;
if (!$step1) {
    header('Location: register.php');
    exit;
}

$role  = $step1['role']; // Student | Faculty | TSG Personnel
$error = $_SESSION['register_details_error'] ?? null;
unset($_SESSION['register_details_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile — TSG Alert &amp; Repair Network</title>
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

        <!-- Profile details card (maroon) -->
        <div class="login-card">
            <h1 class="login-title">COMPLETE YOUR PROFILE</h1>
            <p class="login-role-badge"><?= strtoupper(htmlspecialchars($role)) ?></p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="actions/do_register_details.php">
                <div class="login-form-group">
                    <label class="login-label" for="firstName">First Name: <span class="login-required">*</span></label>
                    <input class="login-input" type="text" id="firstName" name="firstName"
                           required autofocus>
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="middleName">Middle Name: <span class="login-optional">(optional)</span></label>
                    <input class="login-input" type="text" id="middleName" name="middleName">
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="lastName">Last Name: <span class="login-required">*</span></label>
                    <input class="login-input" type="text" id="lastName" name="lastName"
                           required>
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="contact">Contact Number: <span class="login-optional">(optional)</span></label>
                    <input class="login-input" type="tel" id="contact" name="contact">
                </div>

                <?php if ($role === 'Student'): ?>
                <!-- Student-only fields — mirrors vboxStudentFields -->
                <div class="login-form-group">
                    <label class="login-label" for="course">Course: <span class="login-required">*</span></label>
                    <input class="login-input" type="text" id="course" name="course"
                           required>
                </div>

                <div class="login-form-group">
                    <label class="login-label" for="yearLevel">Year Level: <span class="login-required">*</span></label>
                    <select class="login-input" id="yearLevel" name="yearLevel" required>
                        <option value="">Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="login-btn-row">
                    <a href="register.php" class="login-btn login-btn-outline" id="back-btn">← BACK</a>
                    <button type="submit" class="login-btn" id="confirm-btn">CONFIRM</button>
                </div>
            </form>
        </div>

        <!-- Footer link below card -->
        <p class="login-footer">
            Already have an account? <a href="login.php" class="login-create-link">Sign In</a>
        </p>
    </div>
</div>
</body>
</html>
