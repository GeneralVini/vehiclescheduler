<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function plugin_vehiclescheduler_render_incident_form(
    PluginVehicleschedulerIncident $incident,
    int $incidentId,
    string $rootDoc,
    string $backUrl
): void {
    $fields = $incident->fields;
    $statuses = PluginVehicleschedulerIncident::getAllStatus();
    $types = PluginVehicleschedulerIncident::getAllTypes();
    $statusValue = (int) ($fields['status'] ?? PluginVehicleschedulerIncident::STATUS_OPEN);
    $statusLabel = $statuses[$statusValue] ?? 'Novo reporte';
    $statusClassMap = [
        PluginVehicleschedulerIncident::STATUS_OPEN      => 'vs-incident-form-pill-dot--open',
        PluginVehicleschedulerIncident::STATUS_ANALYZING => 'vs-incident-form-pill-dot--analyzing',
        PluginVehicleschedulerIncident::STATUS_RESOLVED  => 'vs-incident-form-pill-dot--resolved',
        PluginVehicleschedulerIncident::STATUS_CLOSED    => 'vs-incident-form-pill-dot--closed',
    ];
    $statusDotClass = $statusClassMap[$statusValue] ?? 'vs-incident-form-pill-dot--open';
    $formAction = plugin_vehiclescheduler_get_front_url('incident.form.php');

    echo "<div class='vs-incident-form-page' data-vs-incident-form>";
    echo "<div class='vs-incident-form-surface'>";
    echo "<div class='vs-incident-form-card'>";
    echo "<div class='vs-incident-form-head'>";
    echo '<div>';
    echo "<h3 class='vs-incident-form-title'><i class='ti ti-alert-triangle'></i>"
        . ($incidentId > 0 ? 'Detalhes do Incidente' : 'Reportar Novo Incidente')
        . '</h3>';
    echo "<div class='vs-incident-form-subtitle'>Registre e acompanhe ocorrencias com os veiculos da frota.</div>";
    echo '</div>';
    echo "<div class='vs-incident-form-pill'><span class='vs-incident-form-pill-dot " . plugin_vehiclescheduler_incident_escape($statusDotClass) . "'></span>"
        . plugin_vehiclescheduler_incident_escape($incidentId > 0 ? $statusLabel : 'Novo reporte')
        . '</div>';
    echo '</div>';

    echo "<div class='vs-incident-form-alert' data-incident-alert>";
    echo '<strong>Atencao:</strong> Incidentes graves, como acidentes e roubos, devem ser reportados imediatamente. ';
    echo 'Um chamado sera criado automaticamente para acompanhamento.';
    echo '</div>';

    echo "<form method='post' action='" . plugin_vehiclescheduler_incident_escape($formAction) . "' data-vs-incident-form-body>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    echo "<div class='vs-form-feedback' data-incident-validation hidden></div>";

    if ($incidentId > 0) {
        echo Html::hidden('id', ['value' => $incidentId]);
    }

    echo "<div class='vs-incident-form-grid'>";

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Solicitante <span class='red'>*</span></div>";
    User::dropdown([
        'name'   => 'users_id',
        'value'  => (int) ($fields['users_id'] ?? Session::getLoginUserID()),
        'entity' => (int) ($fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
        'right'  => 'all',
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Departamento/Setor (Grupo)</div>";
    Group::dropdown([
        'name'   => 'groups_id',
        'value'  => (int) ($fields['groups_id'] ?? 0),
        'entity' => (int) ($fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Veiculo <span class='red'>*</span></div>";
    PluginVehicleschedulerVehicle::dropdown([
        'name'  => 'plugin_vehiclescheduler_vehicles_id',
        'value' => (int) ($fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Motorista no momento</div>";
    PluginVehicleschedulerDriver::dropdown([
        'name'  => 'plugin_vehiclescheduler_drivers_id',
        'value' => (int) ($fields['plugin_vehiclescheduler_drivers_id'] ?? 0),
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-type'>Tipo de incidente <span class='red'>*</span></label>";
    echo "<select id='vs-incident-type' name='incident_type' data-incident-type>";

    foreach ($types as $typeId => $typeLabel) {
        $selected = ((int) ($fields['incident_type'] ?? PluginVehicleschedulerIncident::TYPE_OTHER) === (int) $typeId)
            ? ' selected'
            : '';
        echo "<option value='" . (int) $typeId . "'" . $selected . '>'
            . plugin_vehiclescheduler_incident_escape($typeLabel)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-date'>Data/Hora do incidente <span class='red'>*</span></label>";
    echo "<input type='datetime-local' id='vs-incident-date' name='incident_date' value='"
        . plugin_vehiclescheduler_incident_escape(plugin_vehiclescheduler_incident_to_datetime_local((string) ($fields['incident_date'] ?? date('Y-m-d H:i:s'))))
        . "'>";
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-location'>Local da ocorrencia</label>";
    echo "<input type='text' id='vs-incident-location' name='location' value='"
        . plugin_vehiclescheduler_incident_escape((string) ($fields['location'] ?? ''))
        . "' maxlength='255' placeholder='Onde aconteceu?'>";
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-phone'>Telefone para contato</label>";
    echo "<input type='tel' id='vs-incident-phone' name='contact_phone' value='"
        . plugin_vehiclescheduler_incident_escape((string) ($fields['contact_phone'] ?? ''))
        . "' maxlength='20' data-incident-phone>";
    echo '</div>';

    if ($incidentId > 0) {
        echo "<div class='vs-incident-form-field'>";
        echo "<label class='vs-incident-form-label' for='vs-incident-status'>Status</label>";
        echo "<select id='vs-incident-status' name='status'>";

        foreach ($statuses as $value => $label) {
            $selected = $statusValue === (int) $value ? ' selected' : '';
            echo "<option value='" . (int) $value . "'" . $selected . '>'
                . plugin_vehiclescheduler_incident_escape($label)
                . '</option>';
        }

        echo '</select>';
        echo '</div>';

        echo "<div class='vs-incident-form-field'>";
        echo "<label class='vs-incident-form-label' for='vs-incident-maintenance'>Requer manutencao?</label>";
        echo "<select id='vs-incident-maintenance' name='needs_maintenance'>";
        echo plugin_vehiclescheduler_render_incident_yes_no_options((int) ($fields['needs_maintenance'] ?? 0));
        echo '</select>';
        echo '</div>';

        echo "<div class='vs-incident-form-field'>";
        echo "<label class='vs-incident-form-label' for='vs-incident-insurance'>Requer seguro?</label>";
        echo "<select id='vs-incident-insurance' name='needs_insurance'>";
        echo plugin_vehiclescheduler_render_incident_yes_no_options((int) ($fields['needs_insurance'] ?? 0));
        echo '</select>';
        echo '</div>';
    }

    echo "<div class='vs-incident-form-field vs-incident-form-field--full'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-description'>Descricao detalhada <span class='red'>*</span></label>";
    echo "<textarea id='vs-incident-description' name='description' placeholder='Descreva o que aconteceu com o maximo de detalhes possivel...' required>"
        . plugin_vehiclescheduler_incident_escape((string) ($fields['description'] ?? ''))
        . '</textarea>';
    echo '</div>';

    echo '</div>';

    echo "<div class='vs-incident-form-actions'>";

    if ($incidentId > 0) {
        echo "<button type='submit' name='update' class='vs-incident-form-button vs-incident-form-button--primary'><i class='ti ti-device-floppy'></i>Salvar</button>";
        echo "<button type='submit' name='delete' class='vs-incident-form-button vs-incident-form-button--danger' data-confirm-message='Excluir este incidente?'><i class='ti ti-trash'></i>Excluir</button>";
    } else {
        echo "<button type='submit' name='add' class='vs-incident-form-button vs-incident-form-button--primary'><i class='ti ti-plus'></i>Reportar incidente</button>";
    }

    echo "<a href='" . plugin_vehiclescheduler_incident_escape($backUrl) . "' class='vs-incident-form-link'><i class='ti ti-arrow-left'></i>Voltar</a>";
    echo '</div>';
    echo '</form>';

    if ($incidentId > 0) {
        $vehicleId = (int) ($fields['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $maintenanceUrl = plugin_vehiclescheduler_get_front_url('maintenance.form.php') . '?plugin_vehiclescheduler_vehicles_id='
            . $vehicleId
            . '&plugin_vehiclescheduler_incidents_id='
            . $incidentId
            . '&maintenance_type=2';
        $insuranceUrl = plugin_vehiclescheduler_get_front_url('insuranceclaim.form.php') . '?plugin_vehiclescheduler_vehicles_id='
            . $vehicleId
            . '&plugin_vehiclescheduler_incidents_id='
            . $incidentId;

        echo "<div class='vs-incident-form-quick-actions'>";
        echo "<div class='vs-incident-form-quick-title'>Acoes rapidas</div>";
        echo "<a href='" . plugin_vehiclescheduler_incident_escape($maintenanceUrl) . "' class='vs-incident-form-quick-link'><i class='ti ti-tool'></i>Criar manutencao corretiva</a>";
        echo "<a href='" . plugin_vehiclescheduler_incident_escape($insuranceUrl) . "' class='vs-incident-form-quick-link'><i class='ti ti-shield'></i>Abrir sinistro</a>";
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_incident_to_datetime_local(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '' || $trimmed === '0000-00-00 00:00:00') {
        return '';
    }

    $timestamp = strtotime($trimmed);

    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $timestamp);
}

function plugin_vehiclescheduler_render_incident_yes_no_options(int $selected): string
{
    $html = '';

    foreach ([1 => 'Sim', 0 => 'Nao'] as $value => $label) {
        $isSelected = $selected === $value ? ' selected' : '';
        $html .= "<option value='" . $value . "'" . $isSelected . '>'
            . plugin_vehiclescheduler_incident_escape($label)
            . '</option>';
    }

    return $html;
}

function plugin_vehiclescheduler_incident_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
