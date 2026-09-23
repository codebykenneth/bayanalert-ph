<?php
/** API: returns emergency facilities as JSON, optionally filtered by ?type= */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$db = getDB();
$type = trim($_GET['type'] ?? '');

if ($type !== '') {
    $stmt = $db->prepare('SELECT * FROM emergency_facilities WHERE type = ? ORDER BY city, name');
    $stmt->execute([$type]);
} else {
    $stmt = $db->query('SELECT * FROM emergency_facilities ORDER BY type, city, name');
}

echo json_encode(['facilities' => $stmt->fetchAll()]);
