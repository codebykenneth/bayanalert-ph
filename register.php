<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(role_dashboard_path($_SESSION['user_role']));
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'province' => '', 'city' => '', 'barangay' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'phone'     => trim($_POST['phone'] ?? ''),
        'province'  => trim($_POST['province'] ?? ''),
        'city'      => trim($_POST['city'] ?? ''),
        'barangay'  => trim($_POST['barangay'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (mb_strlen($old['full_name']) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!is_valid_phone($old['phone'])) $errors[] = 'Please enter a valid phone number.';
    if ($old['province'] === '' || $old['city'] === '' || $old['barangay'] === '') $errors[] = 'Please complete your location (province, city/municipality, barangay).';
    if (mb_strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $check = $db->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email address already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                'INSERT INTO users (full_name, email, phone, password_hash, role, province, city, barangay)
                 VALUES (?, ?, ?, ?, "citizen", ?, ?, ?)'
            );
            $stmt->execute([$old['full_name'], $old['email'], $old['phone'], $hash, $old['province'], $old['city'], $old['barangay']]);
            log_activity($db, (int) $db->lastInsertId(), 'register', 'New citizen account created');
            flash('success', 'Registration successful! You may now log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h4 class="fw-bold mb-1 text-center">Create a Citizen Account</h4>
          <p class="text-muted text-center small mb-4">Join BayanAlert PH to report incidents and receive local alerts</p>

          <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2 small"><?= e($err) ?></div>
          <?php endforeach; ?>

          <form method="POST" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" required value="<?= e($old['full_name']) ?>">
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" required placeholder="09XXXXXXXXX" value="<?= e($old['phone']) ?>">
              </div>
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Province</label>
                <input type="text" name="province" list="provinceList" class="form-control" required value="<?= e($old['province']) ?>">
                <datalist id="provinceList">
                  <?php foreach (ph_provinces() as $p): ?><option value="<?= e($p) ?>"><?php endforeach; ?>
                </datalist>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">City / Municipality</label>
                <input type="text" name="city" class="form-control" required value="<?= e($old['city']) ?>">
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Barangay</label>
                <input type="text" name="barangay" class="form-control" required value="<?= e($old['barangay']) ?>">
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <div class="form-text">At least 8 characters.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-bold">Create Account</button>
          </form>
          <p class="text-center small text-muted mt-4 mb-0">Already have an account? <a href="login.php">Log in</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
