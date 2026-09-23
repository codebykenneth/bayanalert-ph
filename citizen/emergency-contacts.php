<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$db = getDB();
$contacts = $db->query("SELECT * FROM emergency_contacts ORDER BY display_order ASC")->fetchAll();

$pageTitle = 'Emergency Contacts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <h4 class="fw-bold mb-4"><i class="fa-solid fa-phone text-warning"></i> Emergency Contacts</h4>
  <div class="row g-3">
    <?php foreach ($contacts as $c): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body d-flex align-items-center gap-3">
          <i class="fa-solid fa-phone-volume fa-2x text-primary"></i>
          <div>
            <h6 class="fw-bold mb-0"><?= e($c['label']) ?></h6>
            <p class="small text-muted mb-1"><?= e($c['category']) ?> &middot; <?= e($c['scope']) ?></p>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $c['number'])) ?>" class="fw-bold text-decoration-none"><?= e($c['number']) ?></a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="small text-muted mt-4">Contact numbers are managed by BayanAlert PH administrators and may be updated. Always verify with official sources during an actual emergency.</p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
