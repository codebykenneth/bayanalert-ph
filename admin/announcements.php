<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','responder']);
$db = getDB();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['do'] ?? '';
    if ($action === 'delete') {
        $db->prepare('DELETE FROM announcements WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Announcement deleted.');
    } elseif ($action === 'save') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        if ($title === '' || $body === '') {
            flash('warning', 'Please complete the title and message.');
        } else {
            $db->prepare('INSERT INTO announcements (title, body, province, city, posted_by) VALUES (?,?,?,?,?)')
               ->execute([$title, $body, $province ?: null, $city ?: null, $user['id']]);
            notify_role($db, 'citizen', 'admin_announcement', $title, mb_strimwidth($body, 0, 120, '...'));
            flash('success', 'Announcement posted and citizens notified.');
        }
    }
    redirect('announcements.php');
}

$announcements = $db->query(
    "SELECT a.*, u.full_name AS author FROM announcements a LEFT JOIN users u ON u.id = a.posted_by ORDER BY a.created_at DESC"
)->fetchAll();

$pageTitle = 'Announcements';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-bullhorn"></i> Announcements</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#annModal"><i class="fa-solid fa-plus"></i> New Announcement</button>
  </div>

  <?php foreach ($announcements as $a): ?>
  <div class="card mb-2 shadow-sm">
    <div class="card-body">
      <div class="d-flex justify-content-between">
        <h6 class="fw-bold mb-1"><?= e($a['title']) ?></h6>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <button name="do" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this announcement?')"><i class="fa-solid fa-trash"></i></button>
        </form>
      </div>
      <p class="small mb-1"><?= nl2br(e($a['body'])) ?></p>
      <p class="small text-muted mb-0">By <?= e($a['author'] ?? 'Staff') ?> &middot; <?= e($a['city'] ?: 'Nationwide') ?> &middot; <?= time_ago($a['created_at']) ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="annModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="save">
        <div class="modal-header"><h5 class="modal-title">New Announcement</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Message</label><textarea name="body" class="form-control" rows="4" required></textarea></div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Province (optional)</label><input type="text" name="province" class="form-control"></div>
            <div class="col-md-6 mb-3"><label class="form-label">City (optional)</label><input type="text" name="city" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" type="submit">Post Announcement</button></div>
      </form>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
