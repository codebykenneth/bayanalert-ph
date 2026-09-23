<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$db = getDB();

$stats = [
    'users'      => $db->query("SELECT COUNT(*) FROM users WHERE role='citizen'")->fetchColumn(),
    'reports'    => $db->query("SELECT COUNT(*) FROM incident_reports")->fetchColumn(),
    'pending'    => $db->query("SELECT COUNT(*) FROM incident_reports WHERE status='Pending'")->fetchColumn(),
    'verified'   => $db->query("SELECT COUNT(*) FROM incident_reports WHERE status='Verified'")->fetchColumn(),
    'alerts'     => $db->query("SELECT COUNT(*) FROM alerts WHERE status='Active'")->fetchColumn(),
    'evac'       => $db->query("SELECT COUNT(*) FROM evacuation_centers")->fetchColumn(),
    'facilities' => $db->query("SELECT COUNT(*) FROM emergency_facilities")->fetchColumn(),
    'resolved'   => $db->query("SELECT COUNT(*) FROM incident_reports WHERE status='Resolved'")->fetchColumn(),
];

// Reports over last 14 days
$trend = $db->query(
    "SELECT DATE(created_at) d, COUNT(*) c FROM incident_reports
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
     GROUP BY DATE(created_at) ORDER BY d"
)->fetchAll();
$trendLabels = []; $trendData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $trendLabels[] = date('M j', strtotime($d));
    $match = array_filter($trend, fn($r) => $r['d'] === $d);
    $trendData[] = $match ? (int) array_values($match)[0]['c'] : 0;
}

// Category breakdown
$categories = $db->query("SELECT incident_type, COUNT(*) c FROM incident_reports GROUP BY incident_type")->fetchAll();

// By location
$byLocation = $db->query("SELECT city, COUNT(*) c FROM incident_reports WHERE city IS NOT NULL GROUP BY city ORDER BY c DESC LIMIT 8")->fetchAll();

$recentReports = $db->query("SELECT r.*, u.full_name FROM incident_reports r LEFT JOIN users u ON u.id = r.reporter_id ORDER BY r.created_at DESC LIMIT 8")->fetchAll();
$activeAlerts = $db->query("SELECT * FROM alerts WHERE status='Active' ORDER BY start_date DESC LIMIT 6")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <h4 class="fw-bold mb-4"><i class="fa-solid fa-gauge"></i> Admin Dashboard</h4>

  <div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Users', $stats['users'], 'fa-users', 'primary'],
        ['Total Reports', $stats['reports'], 'fa-file-lines', 'secondary'],
        ['Pending Reports', $stats['pending'], 'fa-clock', 'warning'],
        ['Verified Incidents', $stats['verified'], 'fa-circle-check', 'info'],
        ['Active Alerts', $stats['alerts'], 'fa-bell', 'danger'],
        ['Evacuation Centers', $stats['evac'], 'fa-house-chimney', 'success'],
        ['Emergency Facilities', $stats['facilities'], 'fa-hospital', 'dark'],
        ['Resolved Incidents', $stats['resolved'], 'fa-check-double', 'success'],
    ];
    foreach ($cards as [$label, $val, $icon, $color]): ?>
    <div class="col-6 col-md-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <i class="fa-solid <?= $icon ?> text-<?= $color ?> mb-2"></i>
          <div class="fs-4 fw-bold"><?= (int) $val ?></div>
          <div class="small text-muted"><?= e($label) ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h6 class="fw-bold">Reports Over Time (Last 14 Days)</h6>
          <canvas id="trendChart" height="180"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h6 class="fw-bold">Incident Categories</h6>
          <canvas id="catChart" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-3">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h6 class="fw-bold">Reports by Location</h6>
          <canvas id="locChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card dashboard-card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <h6 class="fw-bold">Recent Reports</h6>
            <a href="reports.php" class="small">View all</a>
          </div>
          <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Reporter</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recentReports as $r): ?>
              <tr>
                <td class="small"><?= e($r['full_name'] ?? 'Anonymous') ?><?= $r['is_sos'] ? ' <span class="badge bg-danger">SOS</span>' : '' ?></td>
                <td class="small"><?= e($r['incident_type']) ?></td>
                <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e($r['status']) ?></span></td>
                <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card dashboard-card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <h6 class="fw-bold">Active Alerts</h6>
            <a href="alerts.php" class="small">Manage</a>
          </div>
          <?php foreach ($activeAlerts as $a): ?>
          <div class="d-flex justify-content-between border-bottom py-2">
            <div>
              <span class="badge <?= alert_level_class($a['alert_level']) ?>"><?= e($a['alert_level']) ?></span>
              <span class="small fw-semibold ms-1"><?= e($a['title']) ?></span>
            </div>
            <span class="small text-muted"><?= e($a['city'] ?: 'Nationwide') ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: { labels: <?= json_encode($trendLabels) ?>, datasets: [{ label: 'Reports', data: <?= json_encode($trendData) ?>, borderColor: '#0038a8', backgroundColor: 'rgba(0,56,168,.1)', fill: true, tension: .3 }] },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
new Chart(document.getElementById('catChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($categories, 'incident_type')) ?>,
    datasets: [{ data: <?= json_encode(array_map('intval', array_column($categories, 'c'))) ?>,
      backgroundColor: ['#0038a8','#ce1126','#fcd116','#2b9348','#f4681c','#7048e8','#6c757d','#0077b6','#8d6e63','#1b3a8f'] }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } } }
});
new Chart(document.getElementById('locChart'), {
  type: 'bar',
  data: { labels: <?= json_encode(array_column($byLocation, 'city')) ?>, datasets: [{ label: 'Reports', data: <?= json_encode(array_map('intval', array_column($byLocation, 'c'))) ?>, backgroundColor: '#0038a8' }] },
  options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
