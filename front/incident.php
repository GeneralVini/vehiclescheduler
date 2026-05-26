<?php
// front/incident.php

include_once __DIR__ . '/../src/Bootstrap/common.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

function vs_incident_list_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_incident_list_format_datetime(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return __('Not informed', 'vehiclescheduler');
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y H:i', $timestamp);
}

function vs_incident_list_status_pill(string $label, string $modifier): string
{
    return '<span class="vs-driver-grid__pill vs-driver-grid__pill--' . vs_incident_list_escape($modifier) . '">'
        . vs_incident_list_escape($label)
        . '</span>';
}

$incidents = PluginVehicleschedulerIncident::getManagementGridRows();
$incidentFormUrl = plugin_vehiclescheduler_get_front_url('incident.form.php');
$statuses = PluginVehicleschedulerIncident::getAllStatus();
$t = static fn(string $message): string => __($message, 'vehiclescheduler');

Html::header(
    $t('Incidents'),
    $_SERVER['PHP_SELF'],
    'tools',
    PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css([
    'css/pages/driver-grid.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();

echo "<div class='vs-page-header'>";
echo "    <div class='vs-header-content'>";
echo "        <div class='vs-header-title'>";
echo "            <div class='vs-header-icon-wrapper'>";
echo "                <i class='ti ti-alert-triangle vs-header-icon'></i>";
echo "            </div>";
echo "            <div>";
echo "                <h2>" . vs_incident_list_escape($t('Incident Management')) . "</h2>";
echo "                <p class='vs-page-subtitle'>" . vs_incident_list_escape($t('Operational tracking of claims, damages, and fleet incidents.')) . "</p>";
echo "            </div>";
echo "        </div>";
echo "        <a href='" . vs_incident_list_escape($incidentFormUrl) . "' class='vs-btn-add'>";
echo "            <i class='ti ti-plus'></i>";
echo "            <span>" . vs_incident_list_escape($t('Report Claim')) . "</span>";
echo "        </a>";
echo "    </div>";
echo "</div>";

echo "<div class='vs-driver-grid' data-vs-driver-grid>";

echo "    <section class='vs-driver-grid__toolbar'>";
echo "        <div class='vs-driver-grid__search-wrap'>";
echo "            <i class='ti ti-search'></i>";
echo "            <input type='search' placeholder='" . vs_incident_list_escape($t('Search incident...')) . "' aria-label='" . vs_incident_list_escape($t('Search incidents')) . "' data-driver-filter-search>";
echo "        </div>";
echo "        <div class='vs-driver-grid__results-text' data-driver-result-count data-result-label='incidentes'>";
echo              vs_incident_list_escape(sprintf($t('Showing %d incidents'), (int) count($incidents)));
echo "        </div>";
echo "        <div class='vs-driver-grid__filters'>";
echo "            <label class='vs-driver-grid__filter'>";
echo "                <span>" . vs_incident_list_escape($t('Status')) . "</span>";
echo "                <select data-driver-filter-active>";
echo "                    <option value='all'>" . vs_incident_list_escape($t('All')) . "</option>";

foreach ($statuses as $statusId => $statusLabel) {
    echo "                    <option value='" . (int) $statusId . "'>"
        . vs_incident_list_escape((string) $statusLabel)
        . "</option>";
}

echo "                </select>";
echo "            </label>";
echo "            <button type='button' class='vs-driver-grid__clear' data-driver-clear-filters>";
echo "                <i class='ti ti-eraser'></i>";
echo "                <span>" . vs_incident_list_escape($t('Clear')) . "</span>";
echo "            </button>";
echo "        </div>";
echo "    </section>";

echo "    <div class='vs-driver-grid__table-wrap'>";
echo "        <table class='vs-driver-grid__table'>";
echo "            <thead>";
echo "                <tr>";
echo "                    <th>" . vs_incident_list_escape($t('Incident')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Trip / Request')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Vehicle')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Driver')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Requester')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Date / Time')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Location')) . "</th>";
echo "                    <th>" . vs_incident_list_escape($t('Status')) . "</th>";
echo "                    <th class='vs-driver-grid__actions-col'>" . vs_incident_list_escape($t('Actions')) . "</th>";
echo "                </tr>";
echo "            </thead>";
echo "            <tbody data-driver-row-list>";

foreach ($incidents as $incident) {
    $incidentUrl = $incidentFormUrl . '?id=' . (int) ($incident['id'] ?? 0);
    $searchIndex = implode(' ', [
        (string) ($incident['name'] ?? ''),
        (string) ($incident['type_label'] ?? ''),
        (string) ($incident['schedule_label'] ?? ''),
        (string) ($incident['vehicle_name'] ?? ''),
        (string) ($incident['vehicle_plate'] ?? ''),
        (string) ($incident['driver_name'] ?? ''),
        (string) ($incident['requester_name'] ?? ''),
        (string) ($incident['location'] ?? ''),
        (string) ($incident['status_label'] ?? ''),
    ]);

    echo "            <tr data-driver-row"
        . " data-search='" . vs_incident_list_escape(strtolower($searchIndex)) . "'"
        . " data-active='" . (int) ($incident['status'] ?? 0) . "'"
        . " data-expiry-status='all'"
        . ">";

    echo "                <td>";
    echo "                    <div class='vs-driver-grid__identity'>";
    echo "                        <div class='vs-driver-grid__avatar'><i class='ti ti-alert-triangle'></i></div>";
    echo "                        <div class='vs-driver-grid__identity-body'>";
    echo "                            <div class='vs-driver-grid__name'>" . vs_incident_list_escape((string) ($incident['type_label'] ?? $t('Incident'))) . "</div>";
    echo "                            <div class='vs-driver-grid__subline'>"
        . vs_incident_list_escape((string) ((($incident['name'] ?? '') !== '') ? $incident['name'] : $t('Untitled')))
        . "</div>";
    echo "                        </div>";
    echo "                    </div>";
    echo "                </td>";

    echo "                <td>"
        . vs_incident_list_escape((string) ((($incident['schedule_label'] ?? '') !== '') ? $incident['schedule_label'] : $t('No link')))
        . "</td>";
    echo "                <td>";
    echo vs_incident_list_escape((string) ((($incident['vehicle_name'] ?? '') !== '') ? $incident['vehicle_name'] : $t('Not informed')));
    echo "                    <div class='vs-driver-grid__subline'>"
        . vs_incident_list_escape((string) ((($incident['vehicle_plate'] ?? '') !== '') ? $incident['vehicle_plate'] : $t('No plate')))
        . "</div>";
    echo "                </td>";
    echo "                <td>" . vs_incident_list_escape((string) ((($incident['driver_name'] ?? '') !== '') ? $incident['driver_name'] : $t('Not informed'))) . "</td>";
    echo "                <td>" . vs_incident_list_escape((string) ((($incident['requester_name'] ?? '') !== '') ? $incident['requester_name'] : $t('Not informed'))) . "</td>";
    echo "                <td>" . vs_incident_list_escape(vs_incident_list_format_datetime((string) ($incident['incident_date'] ?? ''))) . "</td>";
    echo "                <td>" . vs_incident_list_escape((string) ((($incident['location'] ?? '') !== '') ? $incident['location'] : $t('Not informed'))) . "</td>";
    echo "                <td>"
        . vs_incident_list_status_pill(
            (string) ($incident['status_label'] ?? $t('Open')),
            (string) ($incident['status_modifier'] ?? 'active')
        )
        . "</td>";
    echo "                <td class='vs-driver-grid__actions-col'>";
    echo "                    <a href='" . vs_incident_list_escape($incidentUrl) . "' class='vs-driver-grid__action'>";
    echo "                        <i class='ti ti-pencil'></i>";
    echo "                        <span>" . vs_incident_list_escape($t('Open')) . "</span>";
    echo "                    </a>";
    echo "                </td>";
    echo "            </tr>";
}

echo "            </tbody>";
echo "        </table>";
echo "    </div>";
echo "    <div class='vs-driver-grid__empty' data-driver-empty hidden>";
echo "        <div class='vs-driver-grid__empty-icon'><i class='ti ti-alert-triangle'></i></div>";
echo "        <h3>" . vs_incident_list_escape($t('No incident found')) . "</h3>";
echo "        <p>" . vs_incident_list_escape($t('Review the applied filters.')) . "</p>";
echo "    </div>";
echo "</div>";

plugin_vehiclescheduler_load_script('js/driver-grid.js');
Html::footer();
