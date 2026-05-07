<?php
// login.php — mirrors login-view.fxml + LoginController.java
require_once __DIR__ . '/includes/session.php';

// Already logged in → redirect to dashboard
if (session_user()) {
    redirect_to_dashboard(session_user()['role']);
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — TSG Alert & Repair Network</title>
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

    <!-- Centered login container: logo + branding + card -->
    <div class="login-container">
        <!-- CIT Logo -->
        <div class="login-logo-wrap">
            <img src="assets/images/cit-logo.png" alt="CIT University Seal" class="login-seal">
        </div>

        <!-- Tech-Ops Branding -->
        <div class="login-brand">
            <img src="assets/images/techops_logo.png" alt="CIT Wildcats Tech-Ops" class="login-brand-img">
        </div>

        <!-- Login form card (maroon) -->
        <div class="login-card">
            <h1 class="login-title">LOGIN</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="actions/do_login.php">
                <div class="login-form-group">
                    <label class="login-label" for="email">University Email:</label>
                    <input class="login-input" type="email" id="email" name="email"
                           required autofocus>
                </div>
                <div class="login-form-group">
                    <label class="login-label" for="password">Password:</label>
                    <input class="login-input" type="password" id="password" name="password"
                           required>
                </div>
                <button type="submit" class="login-btn" id="sign-in-btn">
                    SIGN IN
                </button>
            </form>
        </div>

        <!-- Footer link below card -->
        <p class="login-footer">
            No account? <a href="register.php" class="login-create-link">Create One</a>
        </p>
    </div>
</div>
</body>
</html>
