<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','responder']);
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['do'] ?? '';
    if ($action === 'delete') {
        require_role('admin'); // only admin deletes
        $db->prepare('DELETE FROM evacuation_centers WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Evacuation center deleted.');
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $fields = [
            trim($_POST['name'] ?? ''), trim($_POST['address'] ?? ''), trim($_POST['barangay'] ?? ''),
            trim($_POST['city'] ?? ''), trim($_POST['province'] ?? ''),
            $_POST['latitude'] ?? 0, $_POST['longitude'] ?? 0,
            (int) ($_POST['capacity'] ?? 0), (int) ($_POST['current_occupancy'] ?? 0),
            trim($_POST['contact_number'] ?? ''), trim($_POST['facilities'] ?? ''), $_POST['status'] ?? 'Open',
        ];
        if ($fields[0] === '' || $fields[3] === '' || $fields[4] === '') {
            flash('warning', 'Please complete required fields (name, city, province).');
        } elseif ($id > 0) {
            $db->prepare('UPDATE evacuation_centers SET name=?,address=?,barangay=?,city=?,province=?,latitude=?,longitude=?,capacity=?,current_occupancy=?,contact_number=?,facilities=?,status=?, is_sample=0 WHERE id=?')
               ->execute([...$fields, $id]);
            flash('success', 'Evacuation center updated.');
        } else {
            $db->prepare('INSERT INTO evacuation_centers (name,address,barangay,city,province,latitude,longitude,capacity,current_occupancy,contact_number,facilities,status,is_sample) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0)')
               ->execute($fields);
            flash('success', 'Evacuation center added.');
        }
    }
    redirect('evacuation.php');
}

$centers = $db->query('SELECT * FROM evacuation_centers ORDER BY city, name')->fetchAll();
$isAdmin = $_SESSION['user_role'] === 'admin';

$pageTitle = 'Manage Evacuation Centers';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-house-chimney"></i> Manage Evacuation Centers</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#evacModal" onclick="resetEvacForm()"><i class="fa-solid fa-plus"></i> Add Center</button>
  </div>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead><tr><th>Name</th><th>Location</th><th>Capacity</th><th>Occupancy</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($centers as $c): ?>
        <tr>
          <td class="small fw-semibold"><?= e($c['name']) ?><?= $c['is_sample'] ? ' <span class="sample-tag">Sample</span>' : '' ?></td>
          <td class="small"><?= e($c['barangay']) ?>, <?= e($c['city']) ?>, <?= e($c['province']) ?></td>
          <td><?= (int)$c['capacity'] ?></td>
          <td><?= (int)$c['current_occupancy'] ?></td>
          <td><span class="badge <?= status_badge_class($c['status']) ?>"><?= e($c['status']) ?></span></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" onclick='editEvac(<?= json_encode($c) ?>)'><i class="fa-solid fa-pen"></i></button>
            <?php if ($isAdmin): ?>
            <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button name="do" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this center?')"><i class="fa-solid fa-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="evacModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="save"><input type="hidden" name="id" id="ev_id">
        <div class="modal-header"><h5 class="modal-title" id="evacModalTitle">Add Evacuation Center</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="ev_name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" id="ev_address" class="form-control" required></div>
          <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Barangay</label><input type="text" name="barangay" id="ev_barangay" class="form-control"></div>
            <div class="col-md-4 mb-3"><label class="form-label">City</label><input type="text" name="city" id="ev_city" class="form-control" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Province</label><input type="text" name="province" id="ev_province" class="form-control" required></div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input type="text" name="latitude" id="ev_lat" class="form-control" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input type="text" name="longitude" id="ev_lng" class="form-control" required></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Capacity</label><input type="number" name="capacity" id="ev_capacity" class="form-control" min="0"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Current Occupancy</label><input type="number" name="current_occupancy" id="ev_occ" class="form-control" min="0"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Status</label>
              <select name="status" id="ev_status" class="form-select"><option>Open</option><option>Full</option><option>Closed</option></select>
            </div>
          </div>
          <div class="mb-3"><label class="form-label">Contact Number</label><input type="text" name="contact_number" id="ev_contact" class="form-control"></div>
          <div class="mb-3"><label class="form-label">Facilities</label><textarea name="facilities" id="ev_facilities" class="form-control" rows="2" placeholder="e.g. Restrooms, Water Supply, Medical Station"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function resetEvacForm() {
  document.getElementById('evacModalTitle').textContent = 'Add Evacuation Center';
  ['id','name','address','barangay','city','province','lat','lng','contact','facilities'].forEach(f => document.getElementById('ev_'+f).value = '');
  document.getElementById('ev_capacity').value = 0;
  document.getElementById('ev_occ').value = 0;
  document.getElementById('ev_status').value = 'Open';
}
function editEvac(c) {
  document.getElementById('evacModalTitle').textContent = 'Edit Evacuation Center';
  document.getElementById('ev_id').value = c.id;
  document.getElementById('ev_name').value = c.name;
  document.getElementById('ev_address').value = c.address;
  document.getElementById('ev_barangay').value = c.barangay || '';
  document.getElementById('ev_city').value = c.city;
  document.getElementById('ev_province').value = c.province;
  document.getElementById('ev_lat').value = c.latitude;
  document.getElementById('ev_lng').value = c.longitude;
  document.getElementById('ev_capacity').value = c.capacity;
  document.getElementById('ev_occ').value = c.current_occupancy;
  document.getElementById('ev_contact').value = c.contact_number || '';
  document.getElementById('ev_facilities').value = c.facilities || '';
  document.getElementById('ev_status').value = c.status;
  new bootstrap.Modal(document.getElementById('evacModal')).show();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
