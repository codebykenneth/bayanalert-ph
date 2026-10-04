<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('citizen');
$user = current_user();
$db = getDB();
$errors = [];
$submitted = false;

$incidentTypes = ['Flood','Fire','Road Accident','Landslide','Earthquake Damage','Fallen Tree','Road Blockage','Power Outage','Missing Person','Other'];
$presetType = $_GET['type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $type = $_POST['incident_type'] ?? '';
    $desc = trim($_POST['description'] ?? '');
    $locationText = trim($_POST['location_text'] ?? '');
    $lat = $_POST['latitude'] ?? '';
    $lng = $_POST['longitude'] ?? '';
    $datetime = $_POST['incident_datetime'] ?? '';
    $severity = $_POST['severity'] ?? 'Moderate';

    if (!in_array($type, $incidentTypes, true)) $errors[] = 'Please select a valid incident type.';
    if ($desc === '') $errors[] = 'Please provide a description.';
    if ($locationText === '' && ($lat === '' || $lng === '')) $errors[] = 'Please provide a location (text or map coordinates).';
    if (!in_array($severity, ['Low','Moderate','High','Critical'], true)) $severity = 'Moderate';
    if ($datetime === '') $datetime = date('Y-m-d H:i:s');

    $imagePath = null;
    try {
        if (!empty($_FILES['photo']['name'])) {
            $imagePath = handle_image_upload($_FILES['photo']);
        }
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            'INSERT INTO incident_reports
             (reporter_id, incident_type, description, location_text, province, city, barangay, latitude, longitude, severity, status, incident_datetime)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Pending", ?)'
        );
        $stmt->execute([
            $user['id'], $type, $desc, $locationText,
            $user['province'], $user['city'], $user['barangay'],
            $lat !== '' ? $lat : null, $lng !== '' ? $lng : null,
            $severity, $datetime,
        ]);
        $reportId = (int) $db->lastInsertId();

        if ($imagePath) {
            $db->prepare('INSERT INTO incident_images (report_id, file_path) VALUES (?, ?)')->execute([$reportId, $imagePath]);
        }

        log_activity($db, $user['id'], 'report_submitted', "Type: $type");
        notify_role($db, 'admin', 'report_submitted', 'New Incident Report', "{$user['name']} reported: {$type}", $reportId);
        create_notification($db, $user['id'], 'report_submitted', 'Report Submitted', 'Your incident report has been submitted and is pending verification.', $reportId);
        $submitted = true;
    }
}

$pageTitle = 'Report Incident';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php if ($submitted): ?>
        <div class="card border-success shadow-sm">
          <div class="card-body text-center p-5">
            <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
            <h4 class="fw-bold">Report Submitted Successfully</h4>
            <p class="text-muted">Your report is now <strong>Pending</strong> review. It will be verified by an administrator before appearing as a confirmed incident.</p>
            <a href="reports.php" class="btn btn-primary mt-2">View My Reports</a>
            <a href="dashboard.php" class="btn btn-outline-secondary mt-2">Back to Dashboard</a>
          </div>
        </div>
      <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-header fw-bold"><i class="fa-solid fa-file-circle-plus"></i> Report an Incident</div>
        <div class="card-body">
          <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>

          <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Incident Type</label>
                <select name="incident_type" class="form-select" required>
                  <option value="">-- Select --</option>
                  <?php foreach ($incidentTypes as $t): ?>
                    <option value="<?= e($t) ?>" <?= $presetType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Severity</label>
                <select name="severity" class="form-select">
                  <option>Low</option>
                  <option selected>Moderate</option>
                  <option>High</option>
                  <option>Critical</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3" required placeholder="What happened? Provide as much detail as possible."></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">Location</label>
              <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="bayanGetLocation('repLat','repLng','repLocStatus')">
                  <i class="fa-solid fa-location-crosshairs"></i> Use My Current Location
                </button>
              </div>
              <div class="small text-muted mb-2" id="repLocStatus">Location not yet captured.</div>
              <input type="hidden" name="latitude" id="repLat">
              <input type="hidden" name="longitude" id="repLng">
              <input type="text" name="location_text" class="form-control" placeholder="Street, landmark, or barangay">
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Date / Time of Incident</label>
                <input type="datetime-local" name="incident_datetime" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Photo (optional)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
                <div class="form-text">JPG, PNG, GIF, or WEBP. Max 5MB.</div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-paper-plane"></i> Submit Report</button>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
