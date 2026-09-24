<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$db = getDB();
$currentAdmin = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $action = $_POST['do'] ?? '';

    $stmt = $db->prepare('SELECT * FROM incident_reports WHERE id = ?');
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if ($report) {
        if ($action === 'delete') {
            $db->prepare('DELETE FROM incident_reports WHERE id = ?')->execute([$reportId]);
            log_activity($db, $currentAdmin['id'], 'report_deleted', "Report #$reportId deleted");
            flash('success', 'Report deleted.');
        } else {
            $statusMap = [
                'verify'  => 'Verified',
                'reject'  => 'Rejected',
                'resolve' => 'Resolved',
                'respond' => 'Responding',
                'pending' => 'Pending',
            ];
            if (isset($statusMap[$action])) {
                $notes = trim($_POST['admin_notes'] ?? '');
                $db->prepare('UPDATE incident_reports SET status = ?, admin_notes = ? WHERE id = ?')
                   ->execute([$statusMap[$action], $notes ?: $report['admin_notes'], $reportId]);
                if ($report['reporter_id']) {
                    $notifType = $action === 'verify' ? 'report_verified' : ($action === 'reject' ? 'report_rejected' : 'responder_update');
                    create_notification($db, (int) $report['reporter_id'], $notifType, 'Report Update', "Your report (#$reportId, {$report['incident_type']}) is now: {$statusMap[$action]}", $reportId);
                }
                log_activity($db, $currentAdmin['id'], 'report_status_change', "Report #$reportId -> {$statusMap[$action]}");
                flash('success', "Report #$reportId marked as {$statusMap[$action]}.");
            } elseif ($action === 'assign') {
                $responderId = (int) ($_POST['responder_id'] ?? 0);
                if ($responderId > 0) {
                    $db->prepare('UPDATE incident_reports SET assigned_to = ?, status = "Responding" WHERE id = ?')->execute([$responderId, $reportId]);
                    create_notification($db, $responderId, 'responder_update', 'New Assignment', "You have been assigned report #$reportId ({$report['incident_type']}).", $reportId);
                    if ($report['reporter_id']) {
                        create_notification($db, (int) $report['reporter_id'], 'responder_update', 'Report Update', "A responder has been assigned to your report #$reportId.", $reportId);
                    }
                    flash('success', 'Report assigned to responder.');
                }
            }
        }
    }
    redirect('reports.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT r.*, u.full_name AS reporter_name, a.full_name AS assigned_name
        FROM incident_reports r
        LEFT JOIN users u ON u.id = r.reporter_id
        LEFT JOIN users a ON a.id = r.assigned_to";
$params = [];
if ($statusFilter !== '') {
    $sql .= " WHERE r.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY r.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
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

$responders = $db->query("SELECT id, full_name FROM users WHERE role='responder' AND status='active'")->fetchAll();
$statuses = ['Pending','Under Verification','Verified','Responding','Resolved','Rejected'];

$pageTitle = 'Manage Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-file-lines"></i> Manage Reports</h4>
    <div class="btn-group flex-wrap">
      <a href="reports.php" class="btn btn-sm <?= $statusFilter==='' ? 'btn-primary':'btn-outline-primary' ?>">All</a>
      <?php foreach ($statuses as $s): ?>
        <a href="reports.php?status=<?= urlencode($s) ?>" class="btn btn-sm <?= $statusFilter===$s ? 'btn-primary':'btn-outline-primary' ?>"><?= e($s) ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead><tr><th>ID</th><th>Reporter</th><th>Type</th><th>Location</th><th>Date</th><th>Severity</th><th>Status</th><th>Assigned</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td class="small"><?= e($r['reporter_name'] ?? 'Anonymous') ?><?= $r['is_sos'] ? ' <span class="badge bg-danger">SOS</span>' : '' ?></td>
          <td class="small"><?= e($r['incident_type']) ?></td>
          <td class="small"><?= e($r['location_text'] ?: ($r['city'] ?? '')) ?></td>
          <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($r['severity']) ?></span></td>
          <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td class="small"><?= e($r['assigned_name'] ?? '—') ?></td>
          <td>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $r['id'] ?>"><i class="fa-solid fa-eye"></i></button>
          </td>
        </tr>

        <!-- View/Action Modal -->
        <div class="modal fade" id="viewModal<?= $r['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Report #<?= (int)$r['id'] ?> &mdash; <?= e($r['incident_type']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>Reporter:</strong> <?= e($r['reporter_name'] ?? 'Anonymous') ?></p>
                <p><strong>Description:</strong> <?= nl2br(e($r['description'])) ?></p>
                <p><strong>Location:</strong> <?= e($r['location_text'] ?: '—') ?>, <?= e($r['barangay']) ?>, <?= e($r['city']) ?>, <?= e($r['province']) ?></p>
                <p><strong>Severity:</strong> <?= e($r['severity']) ?> &middot; <strong>Status:</strong> <?= e($r['status']) ?></p>
                <?php if (!empty($imagesByReport[$r['id']])): ?>
                  <div class="mb-3">
                    <strong>Photo<?= count($imagesByReport[$r['id']]) > 1 ? 's' : '' ?>:</strong><br>
                    <?php foreach ($imagesByReport[$r['id']] as $imgPath): ?>
                      <a href="<?= e(base_path() . $imgPath) ?>" target="_blank" rel="noopener">
                        <img src="<?= e(base_path() . $imgPath) ?>" alt="Report photo" class="img-thumbnail mt-1" style="max-height:280px;max-width:100%;">
                      </a>
                    <?php endforeach; ?>
                    <div class="small text-muted mt-1">Click the photo to open it full size.</div>
                  </div>
                <?php endif; ?>
                <?php if ($r['latitude'] && $r['longitude']): ?>
                  <div id="miniMap<?= $r['id'] ?>" class="bayan-map-small mb-3"></div>
                <?php endif; ?>

                <form method="POST" class="row g-2 align-items-end">
                  <?= csrf_field() ?>
                  <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                  <div class="col-12">
                    <label class="form-label small">Admin / Responder Note</label>
                    <textarea name="admin_notes" class="form-control form-control-sm" rows="2"><?= e($r['admin_notes']) ?></textarea>
                  </div>
                  <div class="col-md-6">
                    <select name="responder_id" class="form-select form-select-sm">
                      <option value="">-- Assign to responder --</option>
                      <?php foreach ($responders as $resp): ?>
                        <option value="<?= (int)$resp['id'] ?>"><?= e($resp['full_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 d-flex gap-1 flex-wrap">
                    <button name="do" value="assign" class="btn btn-sm btn-outline-primary">Assign</button>
                    <button name="do" value="verify" class="btn btn-sm btn-primary">Verify</button>
                    <button name="do" value="reject" class="btn btn-sm btn-danger">Reject</button>
                    <button name="do" value="resolve" class="btn btn-sm btn-success">Resolve</button>
                    <button name="do" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this report permanently?')">Delete</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.querySelectorAll('[id^="viewModal"]').forEach(function (modal) {
  modal.addEventListener('shown.bs.modal', function () {
    var mapDiv = modal.querySelector('[id^="miniMap"]');
    if (mapDiv && !mapDiv.dataset.rendered) {
      mapDiv.dataset.rendered = '1';
      <?php foreach ($reports as $r): if ($r['latitude'] && $r['longitude']): ?>
      if (mapDiv.id === 'miniMap<?= $r['id'] ?>') {
        var m = L.map(mapDiv.id).setView([<?= (float)$r['latitude'] ?>, <?= (float)$r['longitude'] ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(m);
        L.marker([<?= (float)$r['latitude'] ?>, <?= (float)$r['longitude'] ?>]).addTo(m);
      }
      <?php endif; endforeach; ?>
    }
  });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
