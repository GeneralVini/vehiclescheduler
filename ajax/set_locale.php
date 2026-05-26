<?php

include_once(__DIR__ . '/../src/Bootstrap/common.php');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Verify GLPI session is valid
    if (!Session::getLoginUserID()) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    // Accept both JSON and form-data
    $new_locale = null;
    
    // Try JSON first
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['locale'])) {
        $new_locale = $data['locale'];
    }
    
    // Fall back to POST/FormData
    if (!$new_locale && isset($_POST['locale'])) {
        $new_locale = $_POST['locale'];
    }

    if (!$new_locale) {
        http_response_code(400);
        echo json_encode(['error' => 'Language not specified']);
        exit;
    }

    $supportedLocales = PluginVehicleschedulerConfig::getSupportedLocales();
    if (!array_key_exists($new_locale, $supportedLocales)) {
        http_response_code(400);
        echo json_encode(['error' => __('Unable to save plugin language.', 'vehiclescheduler')]);
        exit;
    }

    if (!PluginVehicleschedulerConfig::setPluginLocale($new_locale)) {
        http_response_code(500);
        echo json_encode(['error' => __('Unable to save plugin language.', 'vehiclescheduler')]);
        exit;
    }

    plugin_vehiclescheduler_apply_configured_locale();
    echo json_encode(['success' => true, 'locale' => $new_locale]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
