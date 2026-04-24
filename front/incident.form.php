<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

require_once(__DIR__ . '/incident.render.php');

Session::checkRight('plugin_vehiclescheduler', READ);

$item = new PluginVehicleschedulerIncident();

$rootDoc = plugin_vehiclescheduler_get_root_doc();

$post = $_POST;
$postId = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$getId = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$id = $postId > 0 ? $postId : $getId;

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['id'] = $postId;
    $post['users_id'] = PluginVehicleschedulerInput::int($_POST, 'users_id', Session::getLoginUserID(), 0);
    $post['groups_id'] = PluginVehicleschedulerInput::int($_POST, 'groups_id', 0, 0);
    $post['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'plugin_vehiclescheduler_vehicles_id',
        0,
        0
    );
    $post['plugin_vehiclescheduler_drivers_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'plugin_vehiclescheduler_drivers_id',
        0,
        0
    );
    $post['incident_type'] = PluginVehicleschedulerInput::int(
        $_POST,
        'incident_type',
        PluginVehicleschedulerIncident::TYPE_OTHER,
        PluginVehicleschedulerIncident::TYPE_ACCIDENT,
        PluginVehicleschedulerIncident::TYPE_OTHER
    );
    $post['status'] = PluginVehicleschedulerInput::int(
        $_POST,
        'status',
        PluginVehicleschedulerIncident::STATUS_OPEN,
        PluginVehicleschedulerIncident::STATUS_OPEN,
        PluginVehicleschedulerIncident::STATUS_CLOSED
    );
    $post['needs_maintenance'] = PluginVehicleschedulerInput::bool($_POST, 'needs_maintenance', false);
    $post['needs_insurance'] = PluginVehicleschedulerInput::bool($_POST, 'needs_insurance', false);
    $post['name'] = PluginVehicleschedulerInput::string($_POST, 'name', 255);
    $post['location'] = PluginVehicleschedulerInput::string($_POST, 'location', 255);
    $post['contact_phone'] = PluginVehicleschedulerInput::string($_POST, 'contact_phone', 20);
    $post['description'] = PluginVehicleschedulerInput::text($_POST, 'description', 5000);
    $post['incident_date'] = PluginVehicleschedulerInput::datetime($_POST, 'incident_date', '') ?? '';
}

if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $post);

    if ($newId = $item->add($post)) {
        if (isset($_SESSION['vehiclescheduler_created_ticket_id'])) {
            $ticketId = (int) $_SESSION['vehiclescheduler_created_ticket_id'];
            unset($_SESSION['vehiclescheduler_created_ticket_id']);

            Session::addMessageAfterRedirect(
                'Incidente reportado! Chamado #' . $ticketId . ' criado automaticamente.',
                false,
                INFO
            );

            Html::redirect(Ticket::getFormURLWithID($ticketId));
        }

        Html::redirect(plugin_vehiclescheduler_get_front_url('incident.form.php') . '?id=' . (int) $newId);
    }

    Html::back();
} elseif (isset($_POST['update'])) {
    $item->check($postId, UPDATE);
    $item->update($post);
    Html::redirect(plugin_vehiclescheduler_get_front_url('incident.form.php') . '?id=' . $postId);
} elseif (isset($_POST['delete'])) {
    $deleteInput = ['id' => $postId];
    $item->check($postId, DELETE);
    $item->delete($deleteInput);
    Html::redirect(plugin_vehiclescheduler_get_front_url('incident.php'));
} elseif (isset($_POST['purge'])) {
    $purgeInput = ['id' => $postId];
    $item->check($postId, PURGE);
    $item->delete($purgeInput, 1);
    Html::redirect(plugin_vehiclescheduler_get_front_url('incident.php'));
} else {
    $item->checkGlobal(READ);

    if ($id > 0) {
        $item->check($id, READ);
        $item->getFromDB($id);
    } else {
        $item->fields = [
            'users_id'                           => Session::getLoginUserID(),
            'groups_id'                          => 0,
            'plugin_vehiclescheduler_vehicles_id' => 0,
            'plugin_vehiclescheduler_drivers_id' => 0,
            'incident_type'                      => PluginVehicleschedulerIncident::TYPE_OTHER,
            'incident_date'                      => date('Y-m-d H:i:s'),
            'location'                           => '',
            'contact_phone'                      => '',
            'status'                             => PluginVehicleschedulerIncident::STATUS_OPEN,
            'needs_maintenance'                  => 0,
            'needs_insurance'                    => 0,
            'description'                        => '',
            'entities_id'                        => (int) ($_SESSION['glpiactive_entity'] ?? 0),
        ];
    }

    Html::header(
        PluginVehicleschedulerIncident::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'tools',
        'PluginVehicleschedulerMenug',
        'incidents'
    );

    plugin_vehiclescheduler_load_css();
    plugin_vehiclescheduler_enhance_ui();

    $isManager = PluginVehicleschedulerProfile::canEditManagement();
    $backUrl = $isManager
        ? plugin_vehiclescheduler_get_front_url('management.php')
        : plugin_vehiclescheduler_get_front_url('requester.php');

    plugin_vehiclescheduler_render_incident_form($item, $id, $rootDoc, $backUrl);

    $feedbackJsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/form-feedback.js';
    $feedbackJsVer = is_file($feedbackJsFile) ? filemtime($feedbackJsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $feedbackJsUrl = plugin_vehiclescheduler_get_public_url('js/form-feedback.js') . '?v=' . $feedbackJsVer;

    $jsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/incident-form.js';
    $jsVer = is_file($jsFile) ? filemtime($jsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $jsUrl = plugin_vehiclescheduler_get_public_url('js/incident-form.js') . '?v=' . $jsVer;

    echo "<script src='" . plugin_vehiclescheduler_incident_escape($feedbackJsUrl) . "' defer></script>";
    echo "<script src='" . plugin_vehiclescheduler_incident_escape($jsUrl) . "' defer></script>";

    Html::footer();
}
