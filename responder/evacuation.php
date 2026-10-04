<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('responder');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $occupancy = (int) ($_POST['current_occupancy'] ?? 0);
    $status = $_POST['status'] ?? 'Open';
    if (in_array($status, ['Open','Full','Closed'], true)) {
        $db->prepare('UPDATE evacuation_centers SET current_occupancy = ?, status = ? WHERE id = ?')
           ->execute([$occupancy, $status, $id]);
        flash('success', 'Evacuation center occupancy updated.');
    }
    redirect('evacuation.php');
}

$centers = $db->query('SELECT * FROM evacuation_centers ORDER BY city, name')->fetchAll();

$pageTitle = 'Evacuation Centers';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <h4 class="fw-bold mb-3"><i class="fa-solid fa-house-chimney"></i> Evacuation Centers &mdash; Occupancy Updates</h4>
  <p class="text-muted small">As an authorized responder, you can update occupancy counts and status for evacuation centers.</p>

  <div class="row g-3">
    <?php foreach ($centers as $c): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-body">
          <span class="badge <?= status_badge_class($c['status']) ?> mb-2"><?= e($c['status']) ?></span>
          <h6 class="fw-bold"><?= e($c['name']) ?></h6>
          <p class="small text-muted mb-2"><?= e($c['barangay']) ?>, <?= e($c['city']) ?></p>
          <form method="POST" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <div class="col-6">
              <label class="form-label small">Occupancy</label>
              <input type="number" name="current_occupancy" class="form-control form-control-sm" value="<?= (int)$c['current_occupancy'] ?>" min="0" max="<?= (int)$c['capacity'] ?>">
            </div>
            <div class="col-6">
              <label class="form-label small">Status</label>
              <select name="status" class="form-select form-select-sm">
                <option <?= $c['status']==='Open'?'selected':'' ?>>Open</option>
                <option <?= $c['status']==='Full'?'selected':'' ?>>Full</option>
                <option <?= $c['status']==='Closed'?'selected':'' ?>>Closed</option>
              </select>
            </div>
            <div class="col-12"><button class="btn btn-sm btn-primary w-100 mt-1">Update</button></div>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
