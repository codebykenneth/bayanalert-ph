<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    redirect(role_dashboard_path($_SESSION['user_role']));
}
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = getDB();
$activeAlerts = $db->query("SELECT COUNT(*) FROM alerts WHERE status='Active'")->fetchColumn();
$evacCenters = $db->query("SELECT COUNT(*) FROM evacuation_centers WHERE status != 'Closed'")->fetchColumn();
$facilities = $db->query("SELECT COUNT(*) FROM emergency_facilities")->fetchColumn();
$recentAlerts = $db->query("SELECT * FROM alerts WHERE status='Active' ORDER BY start_date DESC LIMIT 3")->fetchAll();
?>

<section class="hero-section text-white">
  <div class="container py-5">
    <div class="row align-items-center gy-4">
      <div class="col-lg-7">
        <h1 class="display-5 fw-bold">Alerto sa Bayan. <br>Ligtas ang Lahat.</h1>
        <p class="lead">BayanAlert PH helps Filipino citizens report incidents, find nearby help, and stay informed during emergencies &mdash; nationwide.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="register.php" class="btn btn-warning btn-lg fw-bold px-4"><i class="fa-solid fa-user-plus"></i> Get Started</a>
          <a href="login.php" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        </div>
        <div class="alert alert-light mt-4 small text-dark d-inline-block">
          <i class="fa-solid fa-circle-info"></i> This is a prototype system. It does not automatically dispatch emergency responders. In a real emergency, always call <strong>911</strong>.
        </div>
      </div>
      <div class="col-lg-5 text-center">
        <div class="stat-card-stack">
          <div class="stat-card">
            <i class="fa-solid fa-bell fa-2x text-warning"></i>
            <div class="stat-number"><?= (int)$activeAlerts ?></div>
            <div class="stat-label">Active Alerts</div>
          </div>
          <div class="stat-card">
            <i class="fa-solid fa-house-chimney fa-2x text-success"></i>
            <div class="stat-number"><?= (int)$evacCenters ?></div>
            <div class="stat-label">Evacuation Centers</div>
          </div>
          <div class="stat-card">
            <i class="fa-solid fa-hospital fa-2x text-info"></i>
            <div class="stat-number"><?= (int)$facilities ?></div>
            <div class="stat-label">Emergency Facilities</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container py-5">
  <h3 class="fw-bold mb-4"><i class="fa-solid fa-bullhorn text-danger"></i> Active Alerts (Sample Data)</h3>
  <?php if (empty($recentAlerts)): ?>
    <p class="text-muted">No active alerts at this time.</p>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($recentAlerts as $a): ?>
    <div class="col-md-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <span class="badge <?= alert_level_class($a['alert_level']) ?> mb-2"><?= e($a['alert_level']) ?></span>
          <h6 class="fw-bold"><?= e($a['title']) ?></h6>
          <p class="small text-muted mb-1"><i class="fa-solid fa-location-dot"></i> <?= e($a['city'] ?: 'Nationwide') ?>, <?= e($a['province'] ?: '') ?></p>
          <p class="small"><?= e(mb_strimwidth($a['description'], 0, 100, '...')) ?></p>
          <p class="small text-muted mb-0"><i class="fa-solid fa-building-shield"></i> Source: <?= e($a['source']) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="bg-light py-5">
  <div class="container">
    <h3 class="fw-bold mb-4 text-center">What Citizens Can Do</h3>
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3">
        <i class="fa-solid fa-triangle-exclamation fa-2x text-danger mb-2"></i>
        <h6>Send SOS</h6>
        <p class="small text-muted">Quickly share your location during an emergency.</p>
      </div>
      <div class="col-6 col-md-3">
        <i class="fa-solid fa-file-circle-plus fa-2x text-primary mb-2"></i>
        <h6>Report Incidents</h6>
        <p class="small text-muted">Flood, fire, accidents, road blockages, and more.</p>
      </div>
      <div class="col-6 col-md-3">
        <i class="fa-solid fa-map-location-dot fa-2x text-success mb-2"></i>
        <h6>Find Help Nearby</h6>
        <p class="small text-muted">Hospitals, police, fire stations, evacuation centers.</p>
      </div>
      <div class="col-6 col-md-3">
        <i class="fa-solid fa-book-open fa-2x text-warning mb-2"></i>
        <h6>Learn & Prepare</h6>
        <p class="small text-muted">Safety guides for common Philippine hazards.</p>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
