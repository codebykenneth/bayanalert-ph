<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$db = getDB();
$user = current_user();
$categories = ['Typhoon','Flood','Heavy Rain','Earthquake','Tsunami','Volcanic Activity','Landslide','Extreme Heat','Fire','Other Emergency'];
$levels = ['LOW','MODERATE','HIGH','CRITICAL'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['do'] ?? '';

    if ($action === 'delete') {
        $db->prepare('DELETE FROM alerts WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Alert deleted.');
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $db->prepare('SELECT status FROM alerts WHERE id = ?');
        $stmt->execute([$id]);
        $cur = $stmt->fetchColumn();
        $new = $cur === 'Active' ? 'Inactive' : 'Active';
        $db->prepare('UPDATE alerts SET status = ? WHERE id = ?')->execute([$new, $id]);
        flash('success', "Alert status changed to $new.");
    } elseif ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? '';
        $level = $_POST['alert_level'] ?? 'LOW';
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $source = trim($_POST['source'] ?? 'BayanAlert PH Admin');
        $start = $_POST['start_date'] ?? date('Y-m-d\TH:i');
        $end = $_POST['end_date'] ?: null;

        if ($title === '' || $desc === '' || !in_array($category, $categories, true)) {
            flash('warning', 'Please complete all required alert fields.');
        } else {
            if ($id > 0) {
                $db->prepare('UPDATE alerts SET title=?,description=?,category=?,alert_level=?,province=?,city=?,source=?,start_date=?,end_date=? WHERE id=?')
                   ->execute([$title, $desc, $category, $level, $province ?: null, $city ?: null, $source, $start, $end, $id]);
                flash('success', 'Alert updated.');
            } else {
                $db->prepare('INSERT INTO alerts (title,description,category,alert_level,province,city,source,start_date,end_date,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,"Active",?)')
                   ->execute([$title, $desc, $category, $level, $province ?: null, $city ?: null, $source, $start, $end, $user['id']]);
                $newId = (int) $db->lastInsertId();
                notify_role($db, 'citizen', 'emergency_alert', 'New Alert: ' . $title, mb_strimwidth($desc, 0, 120, '...'), $newId);
                flash('success', 'Alert created and citizens notified.');
            }
        }
    }
    redirect('alerts.php');
}

$alerts = $db->query('SELECT * FROM alerts ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Manage Alerts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-bell"></i> Manage Alerts</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#alertModal" onclick="resetAlertForm()"><i class="fa-solid fa-plus"></i> Add Alert</button>
  </div>

  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead><tr><th>Title</th><th>Category</th><th>Level</th><th>Area</th><th>Status</th><th>Dates</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($alerts as $a): ?>
        <tr>
          <td class="small fw-semibold"><?= e($a['title']) ?></td>
          <td class="small"><?= e($a['category']) ?></td>
          <td><span class="badge <?= alert_level_class($a['alert_level']) ?>"><?= e($a['alert_level']) ?></span></td>
          <td class="small"><?= e($a['city'] ?: 'Nationwide') ?><?= $a['province'] ? ', '.e($a['province']) : '' ?></td>
          <td><span class="badge <?= status_badge_class($a['status']) ?>"><?= e($a['status']) ?></span></td>
          <td class="small text-muted"><?= date('M j', strtotime($a['start_date'])) ?><?= $a['end_date'] ? ' – '.date('M j', strtotime($a['end_date'])) : '' ?></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" onclick='editAlert(<?= json_encode($a) ?>)'><i class="fa-solid fa-pen"></i></button>
            <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button name="do" value="toggle" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-power-off"></i></button>
              <button name="do" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this alert?')"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="alertModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="save">
        <input type="hidden" name="id" id="af_id">
        <div class="modal-header"><h5 class="modal-title" id="alertModalTitle">Add Alert</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" id="af_title" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="af_description" class="form-control" rows="3" required></textarea></div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Category</label>
              <select name="category" id="af_category" class="form-select" required>
                <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Alert Level</label>
              <select name="alert_level" id="af_level" class="form-select">
                <?php foreach ($levels as $l): ?><option value="<?= e($l) ?>"><?= e($l) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Province</label><input type="text" name="province" id="af_province" class="form-control"></div>
            <div class="col-md-6 mb-3"><label class="form-label">City / Municipality</label><input type="text" name="city" id="af_city" class="form-control"></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Source</label><input type="text" name="source" id="af_source" class="form-control" value="BayanAlert PH Admin"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Start Date</label><input type="datetime-local" name="start_date" id="af_start" class="form-control"></div>
            <div class="col-md-4 mb-3"><label class="form-label">End Date</label><input type="datetime-local" name="end_date" id="af_end" class="form-control"></div>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-primary" type="submit">Save Alert</button></div>
      </form>
    </div>
  </div>
</div>

<script>
function resetAlertForm() {
  document.getElementById('alertModalTitle').textContent = 'Add Alert';
  document.getElementById('af_id').value = '';
  document.getElementById('af_title').value = '';
  document.getElementById('af_description').value = '';
  document.getElementById('af_province').value = '';
  document.getElementById('af_city').value = '';
  document.getElementById('af_source').value = 'BayanAlert PH Admin';
  document.getElementById('af_start').value = new Date().toISOString().slice(0,16);
  document.getElementById('af_end').value = '';
}
function editAlert(a) {
  document.getElementById('alertModalTitle').textContent = 'Edit Alert';
  document.getElementById('af_id').value = a.id;
  document.getElementById('af_title').value = a.title;
  document.getElementById('af_description').value = a.description;
  document.getElementById('af_category').value = a.category;
  document.getElementById('af_level').value = a.alert_level;
  document.getElementById('af_province').value = a.province || '';
  document.getElementById('af_city').value = a.city || '';
  document.getElementById('af_source').value = a.source;
  document.getElementById('af_start').value = a.start_date ? a.start_date.replace(' ', 'T').slice(0,16) : '';
  document.getElementById('af_end').value = a.end_date ? a.end_date.replace(' ', 'T').slice(0,16) : '';
  new bootstrap.Modal(document.getElementById('alertModal')).show();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
