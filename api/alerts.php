<?php
/** API: returns active alerts as JSON, optionally filtered by ?city= */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$db = getDB();
$city = trim($_GET['city'] ?? '');

if ($city !== '') {
    $stmt = $db->prepare("SELECT * FROM alerts WHERE status='Active' AND (city = ? OR city IS NULL) ORDER BY start_date DESC");
    $stmt->execute([$city]);
} else {
    $stmt = $db->query("SELECT * FROM alerts WHERE status='Active' ORDER BY start_date DESC");
}

echo json_encode(['alerts' => $stmt->fetchAll()]);
