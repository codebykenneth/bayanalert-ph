<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('responder');
$user = current_user();
$db = getDB();

$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS reporter_name FROM incident_reports r
     LEFT JOIN users u ON u.id = r.reporter_id
     WHERE r.city = ? ORDER BY r.created_at DESC"
);
$stmt->execute([$user['city']]);
$incidents = $stmt->fetchAll();

$pageTitle = 'Incidents in My Area';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <h4 class="fw-bold mb-3"><i class="fa-solid fa-siren"></i> Incidents in <?= e($user['city']) ?></h4>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead><tr><th>Type</th><th>Reporter</th><th>Location</th><th>Severity</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($incidents as $r): ?>
        <tr>
          <td class="small"><?= e($r['incident_type']) ?><?= $r['is_sos'] ? ' <span class="badge bg-danger">SOS</span>' : '' ?></td>
          <td class="small"><?= e($r['reporter_name'] ?? 'Anonymous') ?></td>
          <td class="small"><?= e($r['location_text'] ?: '—') ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($r['severity']) ?></span></td>
          <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
          <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($incidents)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No incidents reported for your area yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="small text-muted mt-2">To take action on a report, ask an administrator to assign it to you, or view your <a href="reports.php">assigned reports</a>.</p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
