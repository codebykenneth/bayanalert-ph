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

// Load photos attached to the listed reports (one query, grouped by report)
$imagesByReport = [];
if (!empty($reports)) {
    $ids = array_column($reports, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt = $db->prepare("SELECT report_id, file_path FROM incident_images WHERE report_id IN ($in) ORDER BY id");
    $imgStmt->execute($ids);
    foreach ($imgStmt->fetchAll() as $img) {
        $imagesByReport[$img['report_id']][] = $img['file_path'];
    }
}

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
          <?php if (!empty($imagesByReport[$r['id']])): ?>
            <div class="mt-2 mb-2">
              <?php foreach ($imagesByReport[$r['id']] as $imgPath): ?>
                <a href="<?= e(base_path() . $imgPath) ?>" target="_blank" rel="noopener">
                  <img src="<?= e(base_path() . $imgPath) ?>" alt="Report photo" class="img-fluid rounded border" style="max-height:220px;width:100%;object-fit:cover;">
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <h6 class="fw-bold mt-2"><?= e($r['incident_type']) ?><?= $r['is_sos'] ? ' <span class="badge bg-danger">SOS</span>' : '' ?></h6>
          <p class="small text-muted mb-1">Reporter: <?= e($r['reporter_name'] ?? 'Anonymous') ?></p>
          <p class="small mb-1"><?= e(mb_strimwidth($r['description'] ?? '', 0, 100, '...')) ?></p>
          <?php
            // Build a Google Maps directions link: exact coordinates if we have them, otherwise the typed address
            if (!empty($r['latitude']) && !empty($r['longitude'])) {
                $dest = (float) $r['latitude'] . ',' . (float) $r['longitude'];
            } else {
                $dest = implode(', ', array_filter([$r['location_text'], $r['barangay'], $r['city'], $r['province']]));
            }
            $navUrl = $dest !== ''
                ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($dest) . '&travelmode=driving'
                : '';
          ?>
          <p class="small text-muted mb-2">
            <?php if ($navUrl): ?>
              <a href="<?= e($navUrl) ?>" target="_blank" rel="noopener" class="text-decoration-none">
                <i class="fa-solid fa-location-dot text-danger"></i> <?= e($r['location_text'] ?: $r['city']) ?>
              </a>
            <?php else: ?>
              <i class="fa-solid fa-location-dot"></i> <?= e($r['location_text'] ?: $r['city']) ?>
            <?php endif; ?>
          </p>
          <?php if ($navUrl): ?>
            <a href="<?= e($navUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-primary w-100 mb-2">
              <i class="fa-solid fa-diamond-turn-right"></i> Navigate to location
            </a>
          <?php endif; ?>

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