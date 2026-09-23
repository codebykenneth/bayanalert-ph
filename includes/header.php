<?php
/** Expects: $pageTitle (optional) to be set before including */
$pageTitle = $pageTitle ?? 'BayanAlert PH';
$bp = function_exists('base_path') ? base_path() : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | BayanAlert PH</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚨</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link href="<?= e($bp) ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<?php if (!empty($flashesInline ?? true)): ?>
<div class="flash-container">
<?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type'] === 'warning' ? 'warning' : ($f['type'] === 'error' ? 'danger' : $f['type'])) ?> alert-dismissible fade show flash-toast" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
