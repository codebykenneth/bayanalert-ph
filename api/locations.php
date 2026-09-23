<?php
/**
 * API: returns JSON locations for the map (incidents, facilities, evacuation centers).
 * Requires an authenticated session (citizen, admin, or responder).
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
header('Content-Type: application/json');

$db = getDB();
$locations = [];

// Verified / active incident reports only (not raw pending noise on the public map)
$incidents = $db->query(
    "SELECT id, incident_type, description, location_text, latitude, longitude, status, severity, created_at
     FROM incident_reports
     WHERE status IN ('Verified','Responding','Resolved') AND latitude IS NOT NULL AND longitude IS NOT NULL
     ORDER BY created_at DESC LIMIT 200"
)->fetchAll();
foreach ($incidents as $i) {
    $locations[] = [
        'category'    => 'incident',
        'type'        => $i['incident_type'],
        'name'        => $i['incident_type'] . ' Report',
        'address'     => $i['location_text'],
        'status'      => $i['status'],
        'description' => $i['description'],
        'lat'         => (float) $i['latitude'],
        'lng'         => (float) $i['longitude'],
    ];
}

// Evacuation centers
$evac = $db->query("SELECT * FROM evacuation_centers")->fetchAll();
foreach ($evac as $ev) {
    $locations[] = [
        'category'    => 'evacuation',
        'type'        => 'Evacuation Center',
        'name'        => $ev['name'],
        'address'     => $ev['address'] . ', ' . $ev['barangay'] . ', ' . $ev['city'],
        'status'      => $ev['status'],
        'description' => 'Capacity: ' . $ev['capacity'] . ' | Occupancy: ' . $ev['current_occupancy'],
        'contact'     => $ev['contact_number'],
        'lat'         => (float) $ev['latitude'],
        'lng'         => (float) $ev['longitude'],
    ];
}

// Emergency facilities
$facilities = $db->query("SELECT * FROM emergency_facilities")->fetchAll();
foreach ($facilities as $f) {
    $locations[] = [
        'category'    => 'facility',
        'type'        => $f['type'],
        'name'        => $f['name'],
        'address'     => $f['address'],
        'status'      => $f['opening_status'],
        'description' => $f['description'],
        'contact'     => $f['contact_number'],
        'lat'         => (float) $f['latitude'],
        'lng'         => (float) $f['longitude'],
    ];
}

echo json_encode(['locations' => $locations]);
