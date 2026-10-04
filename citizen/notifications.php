<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['mark_all_read'])) {
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
        flash('success', 'All notifications marked as read.');
    } elseif (isset($_POST['mark_read_id'])) {
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
           ->execute([(int) $_POST['mark_read_id'], $user['id']]);
    }
    redirect('notifications.php');
}

$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-bell"></i> Notifications</h4>
    <form method="POST"><?= csrf_field() ?>
      <button type="submit" name="mark_all_read" value="1" class="btn btn-sm btn-outline-secondary">Mark all as read</button>
    </form>
  </div>

  <?php if (empty($notifications)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">No notifications yet.</div></div>
  <?php else: ?>
  <div class="list-group">
    <?php foreach ($notifications as $n): ?>
    <div class="list-group-item d-flex justify-content-between align-items-start <?= $n['is_read'] ? '' : 'bg-light' ?>">
      <div>
        <h6 class="mb-1 fw-bold"><?= $n['is_read'] ? '' : '<span class="badge bg-primary me-1">New</span>' ?><?= e($n['title']) ?></h6>
        <p class="mb-1 small"><?= e($n['message']) ?></p>
        <small class="text-muted"><?= time_ago($n['created_at']) ?></small>
      </div>
      <?php if (!$n['is_read']): ?>
      <form method="POST"><?= csrf_field() ?>
        <input type="hidden" name="mark_read_id" value="<?= (int) $n['id'] ?>">
        <button type="submit" class="btn btn-sm btn-link">Mark read</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
