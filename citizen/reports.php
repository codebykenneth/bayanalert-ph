<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();

$stmt = $db->prepare('SELECT * FROM incident_reports WHERE reporter_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();

$reportPhotos = [];
if (!empty($reports)) {
    $ids = array_column($reports, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $imgStmt = $db->prepare("SELECT report_id, file_path FROM incident_images WHERE report_id IN ($placeholders)");
    $imgStmt->execute($ids);
    foreach ($imgStmt->fetchAll() as $img) {
        $reportPhotos[$img['report_id']][] = $img['file_path'];
    }
}

$pageTitle = 'My Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-file-lines"></i> My Submitted Reports</h4>
    <a href="report.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Report</a>
  </div>

  <?php if (empty($reports)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="fa-solid fa-inbox fa-2x mb-2"></i>
      <p class="mb-0">You haven't submitted any reports yet.</p>
    </div></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead>
        <tr><th>Type</th><th>Location</th><th>Severity</th><th>Status</th><th>Submitted</th></tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><?= $r['is_sos'] ? '<span class="badge bg-danger me-1">SOS</span>' : '' ?><?= e($r['incident_type']) ?></td>
          <td class="small"><?= e($r['location_text'] ?: ($r['barangay'] . ', ' . $r['city'])) ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($r['severity']) ?></span></td>
          <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
        </tr>
        <?php if (!empty($reportPhotos[$r['id']])): ?>
        <tr><td colspan="5">
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($reportPhotos[$r['id']] as $photoPath):
                  $photoUrl = str_starts_with($photoPath, 'http') ? $photoPath : base_path() . $photoPath;
            ?>
              <a href="<?= e($photoUrl) ?>" target="_blank" rel="noopener">
                <img src="<?= e($photoUrl) ?>" alt="My report photo" style="width:70px;height:70px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">
              </a>
            <?php endforeach; ?>
          </div>
        </td></tr>
        <?php endif; ?>
        <?php if (!empty($r['admin_notes'])): ?>
        <tr class="table-light"><td colspan="5" class="small text-muted"><i class="fa-solid fa-comment"></i> Admin/Responder note: <?= e($r['admin_notes']) ?></td></tr>
        <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
