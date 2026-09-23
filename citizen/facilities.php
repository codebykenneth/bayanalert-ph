<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$db = getDB();

$typeFilter = $_GET['type'] ?? '';
$types = ['Hospital','Police Station','Fire Station','Ambulance Station','Disaster Risk Reduction Office','Emergency Shelter'];

if ($typeFilter && in_array($typeFilter, $types, true)) {
    $stmt = $db->prepare("SELECT * FROM emergency_facilities WHERE type = ? ORDER BY city, name");
    $stmt->execute([$typeFilter]);
} else {
    $stmt = $db->query("SELECT * FROM emergency_facilities ORDER BY type, city, name");
}
$facilities = $stmt->fetchAll();

$pageTitle = 'Emergency Facilities';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <h4 class="fw-bold mb-3"><i class="fa-solid fa-hospital text-info"></i> Emergency Facilities Directory</h4>

  <div class="d-flex flex-wrap gap-2 mb-4">
    <a href="facilities.php" class="btn btn-sm <?= $typeFilter === '' ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
    <?php foreach ($types as $t): ?>
      <a href="facilities.php?type=<?= urlencode($t) ?>" class="btn btn-sm <?= $typeFilter === $t ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($t) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($facilities)): ?>
    <p class="text-muted">No facilities found for this filter.</p>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($facilities as $f): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <span class="badge bg-light text-dark border mb-2"><?= e($f['type']) ?></span>
          <h6 class="fw-bold"><?= e($f['name']) ?></h6>
          <p class="small text-muted mb-1"><i class="fa-solid fa-location-dot"></i> <?= e($f['address']) ?></p>
          <p class="small mb-1"><i class="fa-solid fa-phone"></i> <?= e($f['contact_number'] ?: 'Not listed') ?></p>
          <p class="small mb-1"><i class="fa-solid fa-clock"></i> <?= e($f['opening_status']) ?></p>
          <p class="small text-muted mb-0"><?= e($f['description']) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
