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

global $DB;
$user_id = Session::getLoginUserID();

$existing = $DB->request([
    'FROM' => 'glpi_plugin_vehiclescheduler_configs',
    'WHERE' => ['users_id' => $user_id]
])->current();

if ($existing) {
    $DB->update('glpi_plugin_vehiclescheduler_configs', [
        'theme' => $new_theme
    ], [
        'users_id' => $user_id
    ]);
} else {
    $DB->insert('glpi_plugin_vehiclescheduler_configs', [
        'users_id' => $user_id,
        'theme' => $new_theme
    ]);
}

echo json_encode(['success' => true, 'theme' => $new_theme]);
