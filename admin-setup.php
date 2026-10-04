<?php
/**
 * BayanAlert PH - One-Time Admin Setup
 *
 * Use this ONLY after running database/go_live_cleanup.sql on your live
 * database (which deletes all demo accounts, including the demo admin).
 * It creates your first real administrator account.
 *
 * SECURITY:
 *  - This page refuses to run if an admin account already exists.
 *  - It also requires a setup key as an extra safeguard against someone
 *    finding this URL before you've had a chance to use it.
 *  - DELETE THIS FILE from your server immediately after creating your
 *    admin account. Leaving it on a live server is a security risk even
 *    with the protections above.
 *
 * Usage: https://yourdomain.com/admin-setup.php?key=YOUR_SETUP_KEY
 */

require_once __DIR__ . '/includes/auth.php';

// Change this to your own secret before deploying, or better: set it via
// an environment variable (SETUP_KEY) so it's never committed to code.
define('SETUP_KEY', getenv('SETUP_KEY') ?: 'change-this-setup-key-before-deploying');

$db = getDB();
$adminExists = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;

$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
$keyOk = hash_equals(SETUP_KEY, $providedKey);

$errors = [];
$success = false;

if ($adminExists) {
    http_response_code(403);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $keyOk) {
    csrf_verify();
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (mb_strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Re-check right before insert to close the race window.
        $adminExists = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
        if ($adminExists) {
            $errors[] = 'An admin account was just created by someone else. Setup is now locked.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, "admin", "active")'
            );
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            log_activity($db, (int) $db->lastInsertId(), 'admin_setup', 'First admin account created via admin-setup.php');
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup | BayanAlert PH</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

      <?php if ($adminExists && !$success): ?>
        <div class="alert alert-secondary">
          <h5 class="fw-bold">Setup already completed</h5>
          <p class="mb-2">An administrator account already exists. This setup page is now locked.</p>
          <p class="mb-0 small">For your security, please delete <code>admin-setup.php</code> from your server if you haven't already.</p>
        </div>
        <a href="login.php" class="btn btn-primary">Go to Login</a>

      <?php elseif ($success): ?>
        <div class="alert alert-success">
          <h5 class="fw-bold">Admin account created</h5>
          <p class="mb-0">You can now log in with the email and password you just set.</p>
        </div>
        <div class="alert alert-danger">
          <strong>Important:</strong> Delete <code>admin-setup.php</code> from your server now. This page should not remain on a live site.
        </div>
        <a href="login.php" class="btn btn-primary">Go to Login</a>

      <?php elseif (!$keyOk): ?>
        <div class="alert alert-warning">
          <h5 class="fw-bold">Setup key required</h5>
          <p class="mb-0">Add <code>?key=YOUR_SETUP_KEY</code> to the URL, matching the <code>SETUP_KEY</code> defined in this file (or the <code>SETUP_KEY</code> environment variable). See the comment at the top of <code>admin-setup.php</code>.</p>
        </div>

      <?php else: ?>
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h4 class="fw-bold mb-1 text-center">Create Your Admin Account</h4>
            <p class="text-muted text-center small mb-4">One-time setup for BayanAlert PH</p>

            <?php foreach ($errors as $err): ?>
              <div class="alert alert-danger py-2 small"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="key" value="<?= e($providedKey) ?>">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
              </div>
              <button type="submit" class="btn btn-primary w-100 fw-bold">Create Admin Account</button>
            </form>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
