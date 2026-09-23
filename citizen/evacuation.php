<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$db = getDB();
$centers = $db->query("SELECT * FROM evacuation_centers ORDER BY status='Open' DESC, city ASC")->fetchAll();

$pageTitle = 'Evacuation Centers';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-house-chimney text-success"></i> Evacuation Centers</h4>
    <button class="btn btn-success btn-sm" id="findNearestBtn"><i class="fa-solid fa-location-crosshairs"></i> Find Nearest Center</button>
  </div>
  <p class="small text-muted mb-3" id="nearestStatus"></p>

  <div class="row g-3" id="evacList">
    <?php foreach ($centers as $c): ?>
    <div class="col-md-6 col-lg-4 evac-card" data-lat="<?= e($c['latitude']) ?>" data-lng="<?= e($c['longitude']) ?>">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <span class="badge <?= status_badge_class($c['status']) ?> mb-2"><?= e($c['status']) ?></span>
            <span class="distance-label small text-muted"></span>
          </div>
          <h6 class="fw-bold"><?= e($c['name']) ?></h6>
          <p class="small text-muted mb-1"><i class="fa-solid fa-location-dot"></i> <?= e($c['address']) ?>, <?= e($c['barangay']) ?>, <?= e($c['city']) ?></p>
          <p class="small mb-1"><i class="fa-solid fa-users"></i> Occupancy: <?= (int)$c['current_occupancy'] ?> / <?= (int)$c['capacity'] ?></p>
          <p class="small mb-1"><i class="fa-solid fa-phone"></i> <?= e($c['contact_number'] ?: 'Not listed') ?></p>
          <p class="small text-muted mb-0"><i class="fa-solid fa-clipboard-list"></i> <?= e($c['facilities'] ?: 'No facility info listed') ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.getElementById('findNearestBtn').addEventListener('click', function () {
  var statusEl = document.getElementById('nearestStatus');
  if (!navigator.geolocation) { statusEl.textContent = 'Geolocation not supported by your browser.'; return; }
  statusEl.textContent = 'Locating you...';
  navigator.geolocation.getCurrentPosition(function (pos) {
    var userLat = pos.coords.latitude, userLng = pos.coords.longitude;
    var cards = document.querySelectorAll('.evac-card');
    var results = [];
    cards.forEach(function (card) {
      var lat = parseFloat(card.dataset.lat), lng = parseFloat(card.dataset.lng);
      var d = haversine(userLat, userLng, lat, lng);
      card.querySelector('.distance-label').textContent = d.toFixed(1) + ' km away';
      results.push({ card: card, dist: d });
    });
    results.sort(function (a, b) { return a.dist - b.dist; });
    var list = document.getElementById('evacList');
    results.forEach(function (r) { list.appendChild(r.card); });
    statusEl.textContent = 'Sorted by distance from your current location. Nearest: ' + results[0].card.querySelector('h6').textContent;
  }, function (err) {
    statusEl.textContent = 'Could not get your location: ' + err.message;
  });
});

function haversine(lat1, lon1, lat2, lon2) {
  var R = 6371;
  var dLat = (lat2 - lat1) * Math.PI / 180;
  var dLon = (lon2 - lon1) * Math.PI / 180;
  var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
