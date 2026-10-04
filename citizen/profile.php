<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();
$errors = [];
$success = false;

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$record = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');

        if (mb_strlen($fullName) < 2) $errors[] = 'Please enter your full name.';
        if (!is_valid_phone($phone)) $errors[] = 'Please enter a valid phone number.';
        if ($province === '' || $city === '' || $barangay === '') $errors[] = 'Please complete your location.';

        if (empty($errors)) {
            $db->prepare('UPDATE users SET full_name=?, phone=?, province=?, city=?, barangay=? WHERE id=?')
               ->execute([$fullName, $phone, $province, $city, $barangay, $user['id']]);
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_province'] = $province;
            $_SESSION['user_city'] = $city;
            $_SESSION['user_barangay'] = $barangay;
            flash('success', 'Profile updated successfully.');
            redirect('profile.php');
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $record['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (mb_strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $db->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
               ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <h4 class="fw-bold mb-4"><i class="fa-solid fa-user-pen"></i> My Profile</h4>

  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">Profile Information</div>
        <div class="card-body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= e($record['full_name']) ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email (cannot be changed)</label>
              <input type="email" class="form-control" value="<?= e($record['email']) ?>" disabled>
            </div>
            <div class="mb-3">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone" class="form-control" value="<?= e($record['phone']) ?>" required>
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label">Province</label>
                <input type="text" name="province" class="form-control" value="<?= e($record['province']) ?>" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?= e($record['city']) ?>" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">Barangay</label>
                <input type="text" name="barangay" class="form-control" value="<?= e($record['barangay']) ?>" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header fw-bold">Change Password</div>
        <div class="card-body">
          <?php if ($record['must_change_password']): ?>
            <div class="alert alert-warning small">For your security, please set a new password.</div>
          <?php endif; ?>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" required minlength="8">
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-warning fw-bold">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
