<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();
$errors = [];
$submitted = false;

$sosTypes = ['Medical Emergency', 'Fire', 'Accident', 'Flood', 'Earthquake', 'Landslide', 'Missing Person', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $type = $_POST['emergency_type'] ?? '';
    $desc = trim($_POST['description'] ?? '');
    $lat = $_POST['latitude'] ?? '';
    $lng = $_POST['longitude'] ?? '';
    $locationText = trim($_POST['location_text'] ?? '');

    if (!in_array($type, $sosTypes, true)) $errors[] = 'Please select an emergency type.';
    if ($lat === '' || $lng === '') {
        if ($locationText === '') {
            $errors[] = 'Please share your location or describe it manually.';
        }
    }

    if (empty($errors)) {
        $mappedType = in_array($type, ['Medical Emergency','Accident','Earthquake'], true) ? $type : $type;
        $stmt = $db->prepare(
            'INSERT INTO incident_reports
             (reporter_id, incident_type, is_sos, description, location_text, province, city, barangay, latitude, longitude, severity, status, incident_datetime)
             VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, "Critical", "Pending", NOW())'
        );
        $stmt->execute([
            $user['id'], $mappedType, $desc, $locationText,
            $user['province'], $user['city'], $user['barangay'],
            $lat !== '' ? $lat : null, $lng !== '' ? $lng : null,
        ]);
        $reportId = (int) $db->lastInsertId();
        log_activity($db, $user['id'], 'sos_submitted', 'SOS type: ' . $type);
        notify_role($db, 'admin', 'emergency_alert', 'New SOS Alert', "{$user['name']} sent an SOS: {$type}", $reportId);
        notify_role($db, 'responder', 'emergency_alert', 'New SOS Alert', "{$user['name']} sent an SOS: {$type}", $reportId);
        create_notification($db, $user['id'], 'report_submitted', 'SOS Submitted', 'Your SOS report has been submitted to BayanAlert PH.', $reportId);
        $submitted = true;
    }
}

$pageTitle = 'SOS';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">

      <?php if ($submitted): ?>
        <div class="card border-success shadow-sm">
          <div class="card-body text-center p-5">
            <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
            <h4 class="fw-bold">Your report has been submitted to BayanAlert PH.</h4>
            <p class="text-muted">Authorized responders and administrators can now view your SOS report. This system does not automatically contact emergency services &mdash; if you are in immediate danger, please call <strong>911</strong> right away.</p>
            <a href="dashboard.php" class="btn btn-primary mt-2">Back to Dashboard</a>
            <a href="reports.php" class="btn btn-outline-secondary mt-2">View My Reports</a>
          </div>
        </div>
      <?php else: ?>

      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
        <div class="small">This SOS form submits a report to BayanAlert PH responders and admins. It does <strong>not</strong> automatically call emergency services. If you are in immediate life-threatening danger, call <strong>911</strong> now.</div>
      </div>

      <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Send SOS</div>
        <div class="card-body">
          <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>

          <form method="POST" id="sosForm">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Emergency Type</label>
              <select name="emergency_type" class="form-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($sosTypes as $t): ?>
                  <option value="<?= e($t) ?>"><?= e($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Short Description</label>
              <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the emergency..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Your Location</label>
              <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="bayanGetLocation('sosLat','sosLng','sosLocStatus')">
                  <i class="fa-solid fa-location-crosshairs"></i> Share My Current Location
                </button>
              </div>
              <div class="small text-muted mb-2" id="sosLocStatus">Location not yet captured.</div>
              <input type="hidden" name="latitude" id="sosLat">
              <input type="hidden" name="longitude" id="sosLng">
              <input type="text" name="location_text" class="form-control" placeholder="Or describe your location (landmark, street, barangay)">
            </div>
            <button type="submit" class="btn btn-danger w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#confirmSosModal" onclick="return false;">
              <i class="fa-solid fa-paper-plane"></i> Submit SOS
            </button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmSosModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm SOS Submission</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to submit this SOS report? It will be sent to BayanAlert PH administrators and authorized responders.</p>
        <p class="small text-muted mb-0">This does not automatically dispatch emergency services.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmSosSubmit">Yes, Submit SOS</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('confirmSosSubmit').addEventListener('click', function () {
  document.getElementById('sosForm').submit();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
