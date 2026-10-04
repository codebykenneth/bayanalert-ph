<?php
/** API: returns incident reports as JSON. Citizens see only their own; staff see all. */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$db = getDB();
$user = current_user();

if ($user['role'] === 'citizen') {
    $stmt = $db->prepare('SELECT * FROM incident_reports WHERE reporter_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
} else {
    $stmt = $db->query('SELECT * FROM incident_reports ORDER BY created_at DESC LIMIT 200');
}

echo json_encode(['reports' => $stmt->fetchAll()]);
