<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$pageTitle = 'Live Map';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 px-2">
    <h5 class="fw-bold mb-2"><i class="fa-solid fa-map-location-dot"></i> Live Map</h5>
    <div class="btn-group flex-wrap" role="group" id="mapFilters">
      <button type="button" class="btn btn-sm btn-primary" data-filter="all">All</button>
      <button type="button" class="btn btn-sm btn-outline-primary" data-filter="incident">Incidents</button>
      <button type="button" class="btn btn-sm btn-outline-primary" data-filter="Hospital">Hospitals</button>
      <button type="button" class="btn btn-sm btn-outline-primary" data-filter="Police Station">Police</button>
      <button type="button" class="btn btn-sm btn-outline-primary" data-filter="Fire Station">Fire Stations</button>
      <button type="button" class="btn btn-sm btn-outline-primary" data-filter="evacuation">Evacuation Centers</button>
    </div>
  </div>
  <div id="map" class="bayan-map mx-2"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= e(base_path()) ?>assets/js/map.js"></script>
<script>
  initBayanMap({
    dataUrl: '<?= e(base_path()) ?>api/locations.php',
    center: [12.8797, 121.7740],
    zoom: 6
  });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
