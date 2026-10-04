<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('responder');
$user = current_user();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $allowed = ['Responding','Resolved','Under Verification'];

    $stmt = $db->prepare('SELECT * FROM incident_reports WHERE id = ? AND assigned_to = ?');
    $stmt->execute([$reportId, $user['id']]);
    $report = $stmt->fetch();

    if ($report && in_array($status, $allowed, true)) {
        $db->prepare('UPDATE incident_reports SET status = ?, admin_notes = ? WHERE id = ?')
           ->execute([$status, $notes ?: $report['admin_notes'], $reportId]);
        if ($report['reporter_id']) {
            create_notification($db, (int) $report['reporter_id'], 'responder_update', 'Report Update', "Responder update on report #$reportId: now {$status}.", $reportId);
        }
        log_activity($db, $user['id'], 'responder_status_update', "Report #$reportId -> $status");
        flash('success', 'Report status updated.');
    } else {
        flash('warning', 'You can only update reports assigned to you.');
    }
    redirect('reports.php');
}

$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS reporter_name FROM incident_reports r
     LEFT JOIN users u ON u.id = r.reporter_id
     WHERE r.assigned_to = ? ORDER BY r.updated_at DESC"
);
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();

$pageTitle = 'My Assigned Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <h4 class="fw-bold mb-3"><i class="fa-solid fa-file-lines"></i> My Assigned Reports</h4>

  <?php if (empty($reports)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">No reports assigned to you yet.</div></div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($reports as $r): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span>
            <span class="small text-muted"><?= time_ago($r['created_at']) ?></span>
          </div>
          <h6 class="fw-bold mt-2"><?= e($r['incident_type']) ?><?= $r['is_sos'] ? ' <span class="badge bg-danger">SOS</span>' : '' ?></h6>
          <p class="small text-muted mb-1">Reporter: <?= e($r['reporter_name'] ?? 'Anonymous') ?></p>
          <p class="small mb-1"><?= e(mb_strimwidth($r['description'] ?? '', 0, 100, '...')) ?></p>
          <p class="small text-muted mb-2"><i class="fa-solid fa-location-dot"></i> <?= e($r['location_text'] ?: $r['city']) ?></p>

          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
            <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a status update note..."><?= e($r['admin_notes']) ?></textarea>
            <div class="d-flex gap-1">
              <button name="status" value="Responding" class="btn btn-sm btn-warning flex-fill">Responding</button>
              <button name="status" value="Resolved" class="btn btn-sm btn-success flex-fill">Resolved</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
