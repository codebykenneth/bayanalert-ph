<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();

$showAll = isset($_GET['all']);
if ($showAll) {
    $alerts = $db->query("SELECT * FROM alerts WHERE status='Active' ORDER BY start_date DESC")->fetchAll();
} else {
    $stmt = $db->prepare("SELECT * FROM alerts WHERE status='Active' AND ((province = ? OR province IS NULL) AND (city = ? OR city IS NULL)) ORDER BY start_date DESC");
    $stmt->execute([$user['province'], $user['city']]);
    $alerts = $stmt->fetchAll();
}

$pageTitle = 'Alerts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-bell text-danger"></i>
      <?= $showAll ? 'All Active Alerts (Nationwide)' : 'Active alerts affecting ' . e($user['city']) ?>
    </h4>
    <a href="alerts.php<?= $showAll ? '' : '?all=1' ?>" class="btn btn-outline-primary btn-sm">
      <?= $showAll ? 'Show My Area Only' : 'Show All Alerts' ?>
    </a>
  </div>

  <?php if (empty($alerts)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
      <i class="fa-solid fa-shield-halved fa-2x mb-2"></i>
      <p class="mb-0">No active alerts <?= $showAll ? '' : 'affecting your area' ?> right now.</p>
    </div></div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($alerts as $a): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <span class="badge <?= alert_level_class($a['alert_level']) ?> mb-2"><?= e($a['alert_level']) ?></span>
            <span class="small text-muted"><?= time_ago($a['created_at']) ?></span>
          </div>
          <h6 class="fw-bold"><?= e($a['title']) ?></h6>
          <p class="small text-muted mb-1"><i class="fa-solid fa-tag"></i> <?= e($a['category']) ?></p>
          <p class="small text-muted mb-1"><i class="fa-solid fa-location-dot"></i> <?= e($a['city'] ?: 'Nationwide') ?>, <?= e($a['province'] ?: '') ?></p>
          <p class="small"><?= nl2br(e($a['description'])) ?></p>
          <hr>
          <p class="small text-muted mb-0"><i class="fa-solid fa-building-shield"></i> Source: <strong><?= e($a['source']) ?></strong></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
