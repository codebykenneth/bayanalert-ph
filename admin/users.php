<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$db = getDB();
$admin = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['do'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === $admin['id'] && in_array($action, ['suspend','delete'], true)) {
        flash('warning', 'You cannot suspend or delete your own account.');
    } elseif ($action === 'suspend') {
        $db->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$id]);
        flash('success', 'User suspended.');
    } elseif ($action === 'activate') {
        $db->prepare("UPDATE users SET status='active', failed_login_attempts=0, lockout_until=NULL WHERE id=?")->execute([$id]);
        flash('success', 'User activated.');
    } elseif ($action === 'delete') {
        $db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        flash('success', 'User deleted.');
    } elseif ($action === 'add_staff') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'responder';
        $pass = $_POST['password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 8 || !in_array($role, ['admin','responder'], true)) {
            flash('warning', 'Please complete all fields with a valid email and an 8+ character password.');
        } else {
            $check = $db->prepare('SELECT id FROM users WHERE email=?');
            $check->execute([$email]);
            if ($check->fetch()) {
                flash('warning', 'A user with this email already exists.');
            } else {
                $db->prepare('INSERT INTO users (full_name,email,phone,password_hash,role,must_change_password) VALUES (?,?,?,?,?,1)')
                   ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $role]);
                flash('success', ucfirst($role) . ' account created.');
            }
        }
    }
    redirect('users.php');
}

$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT * FROM users';
$params = [];
if ($roleFilter) { $sql .= ' WHERE role = ?'; $params[] = $roleFilter; }
$sql .= ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-users"></i> Manage Users</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#staffModal"><i class="fa-solid fa-user-plus"></i> Add Admin/Responder</button>
  </div>

  <div class="btn-group mb-3">
    <a href="users.php" class="btn btn-sm <?= $roleFilter==='' ? 'btn-primary':'btn-outline-primary' ?>">All</a>
    <a href="users.php?role=citizen" class="btn btn-sm <?= $roleFilter==='citizen' ? 'btn-primary':'btn-outline-primary' ?>">Citizens</a>
    <a href="users.php?role=responder" class="btn btn-sm <?= $roleFilter==='responder' ? 'btn-primary':'btn-outline-primary' ?>">Responders</a>
    <a href="users.php?role=admin" class="btn btn-sm <?= $roleFilter==='admin' ? 'btn-primary':'btn-outline-primary' ?>">Admins</a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Location</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="small"><?= e($u['full_name']) ?></td>
          <td class="small"><?= e($u['email']) ?></td>
          <td><span class="badge bg-light text-dark border text-uppercase"><?= e($u['role']) ?></span></td>
          <td class="small"><?= e($u['city'] ?: '—') ?></td>
          <td><span class="badge <?= $u['status']==='active' ? 'bg-success' : 'bg-danger' ?>"><?= e($u['status']) ?></span></td>
          <td class="small text-muted"><?= time_ago($u['created_at']) ?></td>
          <td class="text-nowrap">
            <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <?php if ($u['status'] === 'active'): ?>
                <button name="do" value="suspend" class="btn btn-sm btn-outline-warning" <?= $u['id']===$admin['id']?'disabled':'' ?>>Suspend</button>
              <?php else: ?>
                <button name="do" value="activate" class="btn btn-sm btn-outline-success">Activate</button>
              <?php endif; ?>
              <button name="do" value="delete" class="btn btn-sm btn-outline-danger" <?= $u['id']===$admin['id']?'disabled':'' ?> onclick="return confirm('Delete this user permanently?')">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="staffModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="add_staff">
        <div class="modal-header"><h5 class="modal-title">Add Admin / Responder</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
          <div class="mb-3"><label class="form-label">Role</label>
            <select name="role" class="form-select"><option value="responder">Responder</option><option value="admin">Admin</option></select>
          </div>
          <div class="mb-3"><label class="form-label">Temporary Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" type="submit">Create Account</button></div>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
