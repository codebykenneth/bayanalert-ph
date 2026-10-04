<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(role_dashboard_path($_SESSION['user_role']));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
            $mins = ceil((strtotime($user['lockout_until']) - time()) / 60);
            $errors[] = "Too many failed attempts. Please try again in {$mins} minute(s).";
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $errors[] = 'This account has been suspended. Please contact the administrator.';
            } else {
                // Reset failed attempts
                $db->prepare('UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?')->execute([$user['id']]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_province'] = $user['province'];
                $_SESSION['user_city'] = $user['city'];
                $_SESSION['user_barangay'] = $user['barangay'];
                $_SESSION['last_activity'] = time();

                log_activity($db, (int) $user['id'], 'login', 'Successful login');

                if ($user['must_change_password']) {
                    flash('warning', 'For your security, please change your password in Profile settings.');
                }
                flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect(role_dashboard_path($user['role']));
            }
        } else {
            $errors[] = 'Invalid email or password.';
            if ($user) {
                $attempts = $user['failed_login_attempts'] + 1;
                $lockout = null;
                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    $lockout = date('Y-m-d H:i:s', time() + LOCKOUT_MINUTES * 60);
                    $errors[] = 'Account locked due to repeated failed attempts. Try again in ' . LOCKOUT_MINUTES . ' minutes.';
                }
                $db->prepare('UPDATE users SET failed_login_attempts = ?, lockout_until = ? WHERE id = ?')
                   ->execute([$attempts, $lockout, $user['id']]);
            }
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h4 class="fw-bold mb-1 text-center">Welcome Back</h4>
          <p class="text-muted text-center small mb-4">Log in to BayanAlert PH</p>

          <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2 small"><?= e($err) ?></div>
          <?php endforeach; ?>

          <form method="POST" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Login</button>
          </form>

          <p class="text-center small text-muted mt-4 mb-0">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
