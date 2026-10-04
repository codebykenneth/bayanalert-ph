<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['do'] ?? '';
    if ($action === 'delete') {
        $db->prepare('DELETE FROM emergency_contacts WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Contact removed.');
    } elseif ($action === 'save') {
        $label = trim($_POST['label'] ?? '');
        $number = trim($_POST['number'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $scope = trim($_POST['scope'] ?? 'National');
        $order = (int) ($_POST['display_order'] ?? 0);
        $id = (int) ($_POST['id'] ?? 0);
        if ($label === '' || $number === '') {
            flash('warning', 'Please provide both a label and a number.');
        } elseif ($id > 0) {
            $db->prepare('UPDATE emergency_contacts SET label=?,number=?,category=?,scope=?,display_order=? WHERE id=?')
               ->execute([$label, $number, $category, $scope, $order, $id]);
            flash('success', 'Contact updated.');
        } else {
            $db->prepare('INSERT INTO emergency_contacts (label,number,category,scope,display_order) VALUES (?,?,?,?,?)')
               ->execute([$label, $number, $category, $scope, $order]);
            flash('success', 'Contact added.');
        }
    }
    redirect('settings.php');
}

$contacts = $db->query('SELECT * FROM emergency_contacts ORDER BY display_order')->fetchAll();

$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container-fluid py-4 px-4">
  <h4 class="fw-bold mb-3"><i class="fa-solid fa-gear"></i> Settings &mdash; Emergency Contacts</h4>
  <p class="text-muted small">These numbers appear on the citizen "Emergency Contacts" page. Keep them up to date.</p>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="table-responsive">
        <table class="table table-hover bg-white align-middle">
          <thead><tr><th>Label</th><th>Number</th><th>Category</th><th>Scope</th><th>Order</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
            <tr>
              <td class="small"><?= e($c['label']) ?></td>
              <td class="small fw-bold"><?= e($c['number']) ?></td>
              <td class="small"><?= e($c['category']) ?></td>
              <td class="small"><?= e($c['scope']) ?></td>
              <td class="small"><?= (int)$c['display_order'] ?></td>
              <td class="text-nowrap">
                <button class="btn btn-sm btn-outline-secondary" onclick='editContact(<?= json_encode($c) ?>)'><i class="fa-solid fa-pen"></i></button>
                <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button name="do" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this contact?')"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header fw-bold" id="contactFormTitle">Add Contact</div>
        <div class="card-body">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="do" value="save"><input type="hidden" name="id" id="c_id">
            <div class="mb-3"><label class="form-label">Label</label><input type="text" name="label" id="c_label" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Number</label><input type="text" name="number" id="c_number" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" id="c_category" class="form-control" placeholder="Police, Fire, Medical, etc."></div>
            <div class="mb-3"><label class="form-label">Scope</label><input type="text" name="scope" id="c_scope" class="form-control" value="National"></div>
            <div class="mb-3"><label class="form-label">Display Order</label><input type="number" name="display_order" id="c_order" class="form-control" value="0"></div>
            <button type="submit" class="btn btn-primary w-100">Save Contact</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function editContact(c) {
  document.getElementById('contactFormTitle').textContent = 'Edit Contact';
  document.getElementById('c_id').value = c.id;
  document.getElementById('c_label').value = c.label;
  document.getElementById('c_number').value = c.number;
  document.getElementById('c_category').value = c.category || '';
  document.getElementById('c_scope').value = c.scope;
  document.getElementById('c_order').value = c.display_order;
  window.scrollTo({ top: document.getElementById('contactFormTitle').offsetTop, behavior: 'smooth' });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
