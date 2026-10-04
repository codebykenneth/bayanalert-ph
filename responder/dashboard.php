<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('responder');
$user = current_user();
$db = getDB();

$assigned = $db->prepare("SELECT COUNT(*) FROM incident_reports WHERE assigned_to = ? AND status = 'Responding'");
$assigned->execute([$user['id']]);
$assignedCount = (int) $assigned->fetchColumn();

$pendingArea = $db->prepare("SELECT COUNT(*) FROM incident_reports WHERE city = ? AND status IN ('Pending','Under Verification')");
$pendingArea->execute([$user['city']]);
$pendingCount = (int) $pendingArea->fetchColumn();

$myAssignments = $db->prepare("SELECT * FROM incident_reports WHERE assigned_to = ? ORDER BY updated_at DESC LIMIT 8");
$myAssignments->execute([$user['id']]);
$myAssignments = $myAssignments->fetchAll();

$areaReports = $db->prepare("SELECT * FROM incident_reports WHERE city = ? ORDER BY created_at DESC LIMIT 8");
$areaReports->execute([$user['city']]);
$areaReports = $areaReports->fetchAll();

$pageTitle = 'Responder Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <h4 class="fw-bold mb-1">Welcome, <?= e($user['name']) ?></h4>
  <p class="text-muted small mb-4"><i class="fa-solid fa-location-dot"></i> Assigned area: <?= e($user['city']) ?>, <?= e($user['province']) ?></p>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card dashboard-card"><div class="card-body">
        <i class="fa-solid fa-user-check fa-2x text-primary mb-2"></i>
        <div class="fs-4 fw-bold"><?= $assignedCount ?></div><div class="small text-muted">My Active Assignments</div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card dashboard-card"><div class="card-body">
        <i class="fa-solid fa-clock fa-2x text-warning mb-2"></i>
        <div class="fs-4 fw-bold"><?= $pendingCount ?></div><div class="small text-muted">Pending Reports in Your Area</div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card dashboard-card"><div class="card-body">
        <i class="fa-solid fa-bullhorn fa-2x text-info mb-2"></i>
        <div class="fs-6 fw-bold">Post Updates</div>
        <a href="../admin/announcements.php" class="small">Manage announcements <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
      </div></div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card dashboard-card"><div class="card-body">
        <h6 class="fw-bold">My Assignments</h6>
        <?php if (empty($myAssignments)): ?><p class="text-muted small mb-0">No assignments yet.</p>
        <?php else: foreach ($myAssignments as $r): ?>
          <div class="d-flex justify-content-between border-bottom py-2">
            <div class="small"><strong><?= e($r['incident_type']) ?></strong><br><span class="text-muted"><?= e($r['location_text'] ?: $r['city']) ?></span></div>
            <span class="badge <?= status_badge_class($r['status']) ?> align-self-center"><?= e($r['status']) ?></span>
          </div>
        <?php endforeach; endif; ?>
        <a href="reports.php" class="small d-inline-block mt-2">View all <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card dashboard-card"><div class="card-body">
        <h6 class="fw-bold">Reports in Your Area</h6>
        <?php if (empty($areaReports)): ?><p class="text-muted small mb-0">No reports for your area yet.</p>
        <?php else: foreach ($areaReports as $r): ?>
          <div class="d-flex justify-content-between border-bottom py-2">
            <div class="small"><strong><?= e($r['incident_type']) ?></strong><?= $r['is_sos'] ? ' <span class="badge bg-danger">SOS</span>' : '' ?><br><span class="text-muted"><?= time_ago($r['created_at']) ?></span></div>
            <span class="badge <?= status_badge_class($r['status']) ?> align-self-center"><?= e($r['status']) ?></span>
          </div>
        <?php endforeach; endif; ?>
        <a href="incidents.php" class="small d-inline-block mt-2">View all incidents <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
      </div></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
