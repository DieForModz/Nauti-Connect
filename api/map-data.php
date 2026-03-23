<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$anchorages = [];
$sightings  = [];
$users      = [];

// Anchorages
$res = $conn->query("SELECT id, name, lat, lng, holding_quality, protection_rating, depth FROM anchorages ORDER BY created_at DESC LIMIT 200");
while ($row = $res->fetch_assoc()) {
    $anchorages[] = [
        'id'      => (int)$row['id'],
        'name'    => $row['name'],
        'lat'     => (float)$row['lat'],
        'lng'     => (float)$row['lng'],
        'holding' => $row['holding_quality'],
        'prot'    => (int)$row['protection_rating'],
        'depth'   => (float)$row['depth'],
        'url'     => SITE_URL . '/anchorages/view.php?id=' . $row['id'],
    ];
}

// Sightings (last 30 days)
$res = $conn->query("SELECT id, sighting_type, lat, lng, sighting_time FROM sightings WHERE sighting_time >= NOW() - INTERVAL 30 DAY ORDER BY sighting_time DESC LIMIT 200");
while ($row = $res->fetch_assoc()) {
    $sightings[] = [
        'id'      => (int)$row['id'],
        'species' => $row['sighting_type'],
        'lat'     => (float)$row['lat'],
        'lng'     => (float)$row['lng'],
        'time'    => $row['sighting_time'],
        'recent'  => strtotime($row['sighting_time']) > time() - 86400,
        'url'     => SITE_URL . '/sightings/view.php?id=' . $row['id'],
    ];
}

// Users with location
$res = $conn->query("SELECT id, username, location_lat, location_lng, boat_type FROM users WHERE location_lat IS NOT NULL AND location_lng IS NOT NULL LIMIT 100");
while ($row = $res->fetch_assoc()) {
    $users[] = [
        'id'        => (int)$row['id'],
        'username'  => $row['username'],
        'lat'       => (float)$row['location_lat'],
        'lng'       => (float)$row['location_lng'],
        'boat_type' => $row['boat_type'],
        'url'       => SITE_URL . '/profile/?id=' . $row['id'],
    ];
}

echo json_encode([
    'success'    => true,
    'anchorages' => $anchorages,
    'sightings'  => $sightings,
    'users'      => $users,
], JSON_UNESCAPED_UNICODE);
