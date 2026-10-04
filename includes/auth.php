<?php
/**
 * BayanAlert PH - Authentication & session guard
 * Include this at the top of every protected page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/** Idle session timeout */
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
        session_unset();
        session_destroy();
        session_start();
        flash('warning', 'Your session expired due to inactivity. Please log in again.');
        redirect(base_path() . 'login.php');
    }
    $_SESSION['last_activity'] = time();
}

/** Compute relative base path so includes work from any subfolder depth */
function base_path(): string
{
    // Pages in citizen/, admin/, responder/, api/ are one level deep
    $script = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(trim(str_replace('/bayanalert-ph', '', $script), '/'), '/');
    return $depth > 0 ? str_repeat('../', $depth) : '';
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role'  => $_SESSION['user_role'],
        'province' => $_SESSION['user_province'] ?? null,
        'city'     => $_SESSION['user_city'] ?? null,
        'barangay' => $_SESSION['user_barangay'] ?? null,
    ];
}

/** Require login, otherwise redirect to login page */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect(base_path() . 'login.php');
    }
}

/** Require a specific role (or one of several roles) */
function require_role(string|array $roles): void
{
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:24px;border:1px solid #f3c1c1;background:#fff5f5;border-radius:8px;">
                <h2 style="color:#b91c1c;">Access Denied</h2>
                <p>You do not have permission to view this page.</p>
                <a href="' . e(base_path() . 'index.php') . '">Return to Home</a>
             </div>');
    }
}

function role_dashboard_path(string $role): string
{
    return match ($role) {
        'admin'     => 'admin/dashboard.php',
        'responder' => 'responder/dashboard.php',
        default     => 'citizen/dashboard.php',
    };
}
