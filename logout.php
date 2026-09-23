<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    log_activity(getDB(), (int) $_SESSION['user_id'], 'logout', 'User logged out');
}
$_SESSION = [];
session_unset();
session_destroy();
session_start();
flash('success', 'You have been logged out.');
redirect('login.php');
