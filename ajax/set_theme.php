<?php

include_once(__DIR__ . '/../src/Bootstrap/common.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => __('Method not allowed', 'vehiclescheduler')]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$new_theme = $data['theme'] ?? null;

if (!$new_theme) {
    http_response_code(400);
    echo json_encode(['error' => __('Theme not specified', 'vehiclescheduler')]);
    exit;
}

if (!PluginVehicleschedulerTheme::saveTheme($new_theme)) {
    http_response_code(500);
    echo json_encode(['error' => __('Unable to save theme', 'vehiclescheduler')]);
    exit;
}

echo json_encode(['success' => true, 'theme' => $new_theme]);
