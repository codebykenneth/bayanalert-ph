<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();

// Alerts relevant to the citizen's own location
$stmtAlerts = $db->prepare(
    "SELECT * FROM alerts WHERE status='Active'
     AND (province = ? OR province IS NULL)
     AND (city = ? OR city IS NULL)
     ORDER BY alert_level = 'CRITICAL' DESC, start_date DESC LIMIT 5"
);
$stmtAlerts->execute([$user['province'], $user['city']]);
$localAlerts = $stmtAlerts->fetchAll();

// Nearby incidents (same city, recent, non-rejected)
$stmtIncidents = $db->prepare(
    "SELECT * FROM incident_reports WHERE city = ? AND status NOT IN ('Rejected')
     ORDER BY created_at DESC LIMIT 5"
);
$stmtIncidents->execute([$user['city']]);
$nearbyIncidents = $stmtIncidents->fetchAll();

// Nearby evacuation centers
$stmtEvac = $db->prepare("SELECT * FROM evacuation_centers WHERE city = ? ORDER BY status='Open' DESC LIMIT 4");
$stmtEvac->execute([$user['city']]);
$nearbyEvac = $stmtEvac->fetchAll();

$myReportsCount = $db->prepare("SELECT COUNT(*) FROM incident_reports WHERE reporter_id = ?");
$myReportsCount->execute([$user['id']]);
$myReportsCount = (int) $myReportsCount->fetchColumn();

$pageTitle = 'Citizen Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
      <h4 class="fw-bold mb-0">Hi, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h4>
      <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot"></i> <?= e($user['barangay']) ?>, <?= e($user['city']) ?>, <?= e($user['province']) ?></p>
    </div>
  </div>

  <!-- Quick Actions -->
  <h6 class="fw-bold text-muted mb-3">QUICK ACTIONS</h6>
  <div class="row g-3 mb-5">
    <div class="col-6 col-md-3"><a href="sos.php" class="quick-action-btn qa-sos"><i class="fa-solid fa-triangle-exclamation"></i> SOS</a></div>
    <div class="col-6 col-md-3"><a href="report.php?type=Flood" class="quick-action-btn qa-flood"><i class="fa-solid fa-water"></i> Report Flood</a></div>
    <div class="col-6 col-md-3"><a href="report.php?type=Fire" class="quick-action-btn qa-fire"><i class="fa-solid fa-fire"></i> Report Fire</a></div>
    <div class="col-6 col-md-3"><a href="report.php?type=Road Accident" class="quick-action-btn qa-accident"><i class="fa-solid fa-car-burst"></i> Report Accident</a></div>
    <div class="col-6 col-md-3"><a href="report.php?type=Road Blockage" class="quick-action-btn qa-block"><i class="fa-solid fa-road-barrier"></i> Road Blockage</a></div>
    <div class="col-6 col-md-3"><a href="report.php" class="quick-action-btn qa-other"><i class="fa-solid fa-file-circle-plus"></i> Other Incident</a></div>
    <div class="col-6 col-md-3"><a href="evacuation.php" class="quick-action-btn qa-evac"><i class="fa-solid fa-house-chimney"></i> Evacuation Center</a></div>
    <div class="col-6 col-md-3"><a href="emergency-contacts.php" class="quick-action-btn qa-contacts"><i class="fa-solid fa-phone"></i> Emergency Contacts</a></div>
  </div>

  <div class="row g-4">
    <!-- Weather placeholder -->
    <div class="col-md-6 col-lg-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <i class="fa-solid fa-cloud-sun fa-2x text-info mb-2"></i>
          <h6 class="fw-bold">Weather Status</h6>
          <p class="small text-muted mb-1">Live weather is not connected in this prototype.</p>
          <a href="https://www.pagasa.dost.gov.ph" target="_blank" rel="noopener" class="small">Check PAGASA <i class="fa-solid fa-arrow-up-right-from-square fa-2xs"></i></a>
        </div>
      </div>
    </div>

    <!-- Active alerts -->
    <div class="col-md-6 col-lg-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <i class="fa-solid fa-bell fa-2x text-danger mb-2"></i>
          <h6 class="fw-bold">Active Alerts</h6>
          <p class="display-6 fw-bold mb-0"><?= count($localAlerts) ?></p>
          <a href="alerts.php" class="small">View all alerts <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
        </div>
      </div>
    </div>

    <!-- Nearby incidents -->
    <div class="col-md-6 col-lg-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <i class="fa-solid fa-siren fa-2x text-warning mb-2"></i>
          <h6 class="fw-bold">Nearby Incidents</h6>
          <p class="display-6 fw-bold mb-0"><?= count($nearbyIncidents) ?></p>
          <a href="map.php" class="small">View on map <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
        </div>
      </div>
    </div>

    <!-- My Reports -->
    <div class="col-md-6 col-lg-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <i class="fa-solid fa-file-lines fa-2x text-primary mb-2"></i>
          <h6 class="fw-bold">My Reports</h6>
          <p class="display-6 fw-bold mb-0"><?= $myReportsCount ?></p>
          <a href="reports.php" class="small">View my reports <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-1">
    <div class="col-lg-6">
      <div class="card dashboard-card">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="fa-solid fa-bell text-danger"></i> Alerts affecting <?= e($user['city']) ?></h6>
          <?php if (empty($localAlerts)): ?>
            <p class="text-muted small mb-0">No active alerts affecting your area right now.</p>
          <?php else: foreach ($localAlerts as $a): ?>
            <div class="d-flex justify-content-between align-items-start border-bottom py-2">
              <div>
                <span class="badge <?= alert_level_class($a['alert_level']) ?>"><?= e($a['alert_level']) ?></span>
                <span class="fw-semibold ms-1"><?= e($a['title']) ?></span>
                <div class="small text-muted"><?= e($a['category']) ?> &middot; Source: <?= e($a['source']) ?></div>
              </div>
              <span class="small text-muted"><?= time_ago($a['created_at']) ?></span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card dashboard-card">
        <div class="card-body">
          <h6 class="fw-bold mb-3"><i class="fa-solid fa-house-chimney text-success"></i> Evacuation Centers Near You</h6>
          <?php if (empty($nearbyEvac)): ?>
            <p class="text-muted small mb-0">No evacuation centers listed for your city yet.</p>
          <?php else: foreach ($nearbyEvac as $ev): ?>
            <div class="d-flex justify-content-between align-items-start border-bottom py-2">
              <div>
                <span class="fw-semibold"><?= e($ev['name']) ?></span>
                <div class="small text-muted"><?= e($ev['barangay']) ?>, <?= e($ev['city']) ?> &middot; Capacity <?= (int)$ev['capacity'] ?></div>
              </div>
              <span class="badge <?= status_badge_class($ev['status']) ?>"><?= e($ev['status']) ?></span>
            </div>
          <?php endforeach; endif; ?>
          <a href="evacuation.php" class="small d-inline-block mt-2">Find nearest evacuation center <i class="fa-solid fa-arrow-right fa-2xs"></i></a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
