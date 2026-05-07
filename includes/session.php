<?php
// Session helpers — replaces JavaFX's in-memory user state

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Store logged-in user into session. */
function session_set_user(array $user): void {
    $_SESSION['user'] = $user;
}

/** Return the logged-in user array, or null. */
function session_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/** Destroy session (log out). */
function session_logout(): void {
    $_SESSION = [];
    session_destroy();
}

/**
 * Guard: redirect to login if not authenticated.
 * Optionally restrict to a specific role.
 *
 * Usage at top of every protected page:
 *   require_auth();          // any role
 *   require_auth('Student'); // students only
 *   require_auth('TSG Personnel');
 */
function require_auth(?string $role = null): array {
    $user = session_user();
    if (!$user) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
    if ($role !== null && $user['role'] !== $role) {
        // Wrong role — send them to their correct dashboard
        redirect_to_dashboard($user['role']);
    }
    return $user;
}

/** Redirect to the correct dashboard for a role. */
function redirect_to_dashboard(string $role): void {
    if ($role === 'Admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
    } elseif ($role === 'TSG Personnel') {
        header('Location: ' . base_url('tsg/dashboard.php'));
    } else {
        header('Location: ' . base_url('reporter/dashboard.php'));
    }
    exit;
}

/**
 * Build an absolute URL relative to the php-app root.
 * Works whether php-app is at /php-app/ or the doc root.
 */
function base_url(string $path = ''): string {
    // Hardcode the base folder name to fix redirection issues in XAMPP
    return '/php-app/' . ltrim($path, '/');
}

