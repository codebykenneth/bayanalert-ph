<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$db = getDB();
$types = ['Hospital','Police Station','Fire Station','Ambulance Station','Disaster Risk Reduction Office','Emergency Shelter'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['do'] ?? '';
    if ($action === 'delete') {
        $db->prepare('DELETE FROM emergency_facilities WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Facility deleted.');
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $fields = [
            trim($_POST['name'] ?? ''), $_POST['type'] ?? 'Hospital', trim($_POST['address'] ?? ''),
            trim($_POST['province'] ?? ''), trim($_POST['city'] ?? ''), trim($_POST['contact_number'] ?? ''),
            $_POST['latitude'] ?? 0, $_POST['longitude'] ?? 0, $_POST['opening_status'] ?? 'Open 24/7',
            trim($_POST['description'] ?? ''),
        ];
        if ($fields[0] === '' || !in_array($fields[1], $types, true)) {
            flash('warning', 'Please complete required fields.');
        } elseif ($id > 0) {
            $db->prepare('UPDATE emergency_facilities SET name=?,type=?,address=?,province=?,city=?,contact_number=?,latitude=?,longitude=?,opening_status=?,description=?, is_sample=0 WHERE id=?')
               ->execute([...$fields, $id]);
            flash('success', 'Facility updated.');
        } else {
            $db->prepare('INSERT INTO emergency_facilities (name,type,address,province,city,contact_number,latitude,longitude,opening_status,description,is_sample) VALUES (?,?,?,?,?,?,?,?,?,?,0)')
               ->execute($fields);
            flash('success', 'Facility added.');
        }
    }
    redirect('facilities.php');
}

$facilities = $db->query('SELECT * FROM emergency_facilities ORDER BY type, city, name')->fetchAll();

$pageTitle = 'Manage Facilities';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-hospital"></i> Manage Emergency Facilities</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#facModal" onclick="resetFacForm()"><i class="fa-solid fa-plus"></i> Add Facility</button>
  </div>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead><tr><th>Name</th><th>Type</th><th>Location</th><th>Contact</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($facilities as $f): ?>
        <tr>
          <td class="small fw-semibold"><?= e($f['name']) ?><?= $f['is_sample'] ? ' <span class="sample-tag">Sample</span>' : '' ?></td>
          <td class="small"><?= e($f['type']) ?></td>
          <td class="small"><?= e($f['city']) ?>, <?= e($f['province']) ?></td>
          <td class="small"><?= e($f['contact_number']) ?></td>
          <td><span class="badge bg-light text-dark border"><?= e($f['opening_status']) ?></span></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" onclick='editFac(<?= json_encode($f) ?>)'><i class="fa-solid fa-pen"></i></button>
            <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button name="do" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this facility?')"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="facModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="save"><input type="hidden" name="id" id="fc_id">
        <div class="modal-header"><h5 class="modal-title" id="facModalTitle">Add Facility</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="fc_name" class="form-control" required></div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Type</label>
              <select name="type" id="fc_type" class="form-select">
                <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Opening Status</label>
              <select name="opening_status" id="fc_status" class="form-select"><option>Open 24/7</option><option>Open</option><option>Closed</option></select>
            </div>
          </div>
          <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" id="fc_address" class="form-control" required></div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Province</label><input type="text" name="province" id="fc_province" class="form-control"></div>
            <div class="col-md-6 mb-3"><label class="form-label">City</label><input type="text" name="city" id="fc_city" class="form-control"></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Contact Number</label><input type="text" name="contact_number" id="fc_contact" class="form-control"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Latitude</label><input type="text" name="latitude" id="fc_lat" class="form-control" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Longitude</label><input type="text" name="longitude" id="fc_lng" class="form-control" required></div>
          </div>
          <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="fc_description" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function resetFacForm() {
  document.getElementById('facModalTitle').textContent = 'Add Facility';
  ['id','name','address','province','city','contact','lat','lng','description'].forEach(f => document.getElementById('fc_'+f).value = '');
  document.getElementById('fc_status').value = 'Open 24/7';
}
function editFac(f) {
  document.getElementById('facModalTitle').textContent = 'Edit Facility';
  document.getElementById('fc_id').value = f.id;
  document.getElementById('fc_name').value = f.name;
  document.getElementById('fc_type').value = f.type;
  document.getElementById('fc_address').value = f.address;
  document.getElementById('fc_province').value = f.province || '';
  document.getElementById('fc_city').value = f.city || '';
  document.getElementById('fc_contact').value = f.contact_number || '';
  document.getElementById('fc_lat').value = f.latitude;
  document.getElementById('fc_lng').value = f.longitude;
  document.getElementById('fc_status').value = f.opening_status;
  document.getElementById('fc_description').value = f.description || '';
  new bootstrap.Modal(document.getElementById('facModal')).show();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
