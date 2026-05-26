<?php

include_once __DIR__ . '/../src/Bootstrap/common.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

function vs_maintenance_list_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_maintenance_list_format_date(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return __('Not informed', 'vehiclescheduler');
    }

    $timestamp = strtotime($value);

    return $timestamp === false ? $value : date('d/m/Y', $timestamp);
}

function vs_maintenance_list_money($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function vs_maintenance_list_status_pill(string $label, string $modifier): string
{
    return '<span class="vs-driver-grid__pill vs-driver-grid__pill--'
        . vs_maintenance_list_escape($modifier)
        . '">'
        . vs_maintenance_list_escape($label)
        . '</span>';
}

global $DB;

$t = static fn(string $message): string => __($message, 'vehiclescheduler');
$maintenanceFormUrl = plugin_vehiclescheduler_get_front_url('maintenance.form.php');
$rows = [];

if ($DB->tableExists('glpi_plugin_vehiclescheduler_maintenances')) {
    $rows = iterator_to_array($DB->request([
        'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
        'ORDER' => ['id DESC'],
        'LIMIT' => 100,
    ]));
}

$vehicleIds = [];
$workshopIds = [];

foreach ($rows as $row) {
    $vehicleId = (int) ($row['plugin_vehiclescheduler_vehicles_id'] ?? 0);
    $workshopId = (int) ($row['plugin_vehiclescheduler_workshops_id'] ?? 0);

    if ($vehicleId > 0) {
        $vehicleIds[] = $vehicleId;
    }

    if ($workshopId > 0) {
        $workshopIds[] = $workshopId;
    }
}

$vehicleMap = [];
$vehicleIds = array_values(array_unique($vehicleIds));

if ($vehicleIds !== [] && $DB->tableExists('glpi_plugin_vehiclescheduler_vehicles')) {
    foreach ($DB->request([
        'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
        'WHERE' => ['id' => $vehicleIds],
    ]) as $vehicle) {
        $vehicleMap[(int) $vehicle['id']] = $vehicle;
    }
}

$workshopMap = [];
$workshopIds = array_values(array_unique($workshopIds));

if ($workshopIds !== [] && $DB->tableExists('glpi_plugin_vehiclescheduler_workshops')) {
    foreach ($DB->request([
        'FROM'  => 'glpi_plugin_vehiclescheduler_workshops',
        'WHERE' => ['id' => $workshopIds],
    ]) as $workshop) {
        $workshopMap[(int) $workshop['id']] = $workshop;
    }
}

$statuses = PluginVehicleschedulerMaintenance::getAllStatus();
$types = PluginVehicleschedulerMaintenance::getAllTypes();
$statusModifiers = [
    PluginVehicleschedulerMaintenance::STATUS_SCHEDULED => 'warning',
    PluginVehicleschedulerMaintenance::STATUS_IN_PROGRESS => 'active',
    PluginVehicleschedulerMaintenance::STATUS_DONE => 'inactive',
    PluginVehicleschedulerMaintenance::STATUS_CANCELLED => 'inactive',
];

Html::header(
    $t('Maintenances'),
    $_SERVER['PHP_SELF'],
    'tools',
    PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css([
    'css/pages/driver-grid.css',
    'css/core/flash.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();
?>

<div class="vs-page-header">
    <div class="vs-header-content">
        <div class="vs-header-title">
            <div class="vs-header-icon-wrapper">
                <i class="ti ti-tool vs-header-icon"></i>
            </div>
            <div>
                <h2><?= vs_maintenance_list_escape($t('Maintenance Work Orders')) ?></h2>
                <p class="vs-page-subtitle"><?= vs_maintenance_list_escape($t('Essential operational control for maintenance work orders.')) ?></p>
            </div>
        </div>

        <?php if (Session::haveRight('plugin_vehiclescheduler_management', CREATE)) : ?>
            <a href="<?= vs_maintenance_list_escape($maintenanceFormUrl) ?>" class="vs-btn-add">
                <i class="ti ti-plus"></i>
                <span><?= vs_maintenance_list_escape($t('New Work Order')) ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="vs-driver-grid" data-vs-driver-grid>
    <section class="vs-driver-grid__toolbar">
        <div class="vs-driver-grid__search-wrap">
            <i class="ti ti-search"></i>
            <input
                type="search"
                placeholder="<?= vs_maintenance_list_escape($t('Search maintenance...')) ?>"
                aria-label="<?= vs_maintenance_list_escape($t('Search maintenances')) ?>"
                data-driver-filter-search>
        </div>

        <div class="vs-driver-grid__results-text" data-driver-result-count>
            <?= vs_maintenance_list_escape(sprintf($t('Showing %d maintenances'), count($rows))) ?>
        </div>
    </section>

    <div class="vs-driver-grid__table-wrap">
        <table class="vs-driver-grid__table">
            <thead>
                <tr>
                    <th><?= vs_maintenance_list_escape($t('Work Order')) ?></th>
                    <th><?= vs_maintenance_list_escape($t('Vehicle')) ?></th>
                    <th><?= vs_maintenance_list_escape($t('Workshop')) ?></th>
                    <th><?= vs_maintenance_list_escape($t('Type')) ?></th>
                    <th><?= vs_maintenance_list_escape($t('Scheduled date')) ?></th>
                    <th><?= vs_maintenance_list_escape($t('Estimated cost')) ?></th>
                    <th><?= vs_maintenance_list_escape($t('Status')) ?></th>
                    <th class="vs-driver-grid__actions-col"><?= vs_maintenance_list_escape($t('Actions')) ?></th>
                </tr>
            </thead>
            <tbody data-driver-row-list>
                <?php foreach ($rows as $row) : ?>
                    <?php
                    $maintenanceId = (int) ($row['id'] ?? 0);
                    $vehicle = $vehicleMap[(int) ($row['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? [];
                    $workshop = $workshopMap[(int) ($row['plugin_vehiclescheduler_workshops_id'] ?? 0)] ?? [];
                    $status = (int) ($row['status'] ?? PluginVehicleschedulerMaintenance::STATUS_SCHEDULED);
                    $type = (int) ($row['type'] ?? PluginVehicleschedulerMaintenance::TYPE_PREVENTIVE);
                    $vehicleName = (string) (($vehicle['name'] ?? '') !== '' ? $vehicle['name'] : $t('Not informed'));
                    $vehiclePlate = (string) (($vehicle['plate'] ?? '') !== '' ? $vehicle['plate'] : $t('No plate'));
                    $workshopName = (string) (($workshop['name'] ?? '') !== '' ? $workshop['name'] : (($row['supplier'] ?? '') ?: $t('Not informed')));
                    $orderNumber = (string) (($row['service_order_number'] ?? '') !== '' ? $row['service_order_number'] : '#' . $maintenanceId);
                    $searchIndex = implode(' ', [
                        $orderNumber,
                        $vehicleName,
                        $vehiclePlate,
                        $workshopName,
                        (string) ($types[$type] ?? ''),
                        (string) ($statuses[$status] ?? ''),
                        (string) ($row['description'] ?? ''),
                    ]);
                    ?>
                    <tr
                        data-driver-row
                        data-search="<?= vs_maintenance_list_escape(strtolower($searchIndex)) ?>"
                        data-active="<?= $status ?>"
                        data-expiry-status="all">
                        <td>
                            <div class="vs-driver-grid__identity">
                                <div class="vs-driver-grid__avatar"><i class="ti ti-tool"></i></div>
                                <div class="vs-driver-grid__identity-body">
                                    <div class="vs-driver-grid__name"><?= vs_maintenance_list_escape($orderNumber) ?></div>
                                    <div class="vs-driver-grid__subline"><?= vs_maintenance_list_escape((string) ($row['description'] ?: $t('No description'))) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= vs_maintenance_list_escape($vehicleName) ?>
                            <div class="vs-driver-grid__subline"><?= vs_maintenance_list_escape($vehiclePlate) ?></div>
                        </td>
                        <td><?= vs_maintenance_list_escape($workshopName) ?></td>
                        <td><?= vs_maintenance_list_escape((string) ($types[$type] ?? $t('Not informed'))) ?></td>
                        <td><?= vs_maintenance_list_escape(vs_maintenance_list_format_date((string) ($row['scheduled_date'] ?? ''))) ?></td>
                        <td><?= vs_maintenance_list_escape(vs_maintenance_list_money($row['estimated_cost'] ?? $row['cost'] ?? 0)) ?></td>
                        <td>
                            <?= vs_maintenance_list_status_pill(
                                (string) ($statuses[$status] ?? $t('Not informed')),
                                (string) ($statusModifiers[$status] ?? 'warning')
                            ) ?>
                        </td>
                        <td class="vs-driver-grid__actions-col">
                            <a href="<?= vs_maintenance_list_escape($maintenanceFormUrl . '?id=' . $maintenanceId) ?>" class="vs-driver-grid__action">
                                <i class="ti ti-pencil"></i>
                                <span><?= vs_maintenance_list_escape($t('Open')) ?></span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="vs-driver-grid__empty" data-driver-empty <?= $rows === [] ? '' : 'hidden' ?>>
        <div class="vs-driver-grid__empty-icon"><i class="ti ti-tool"></i></div>
        <h3><?= vs_maintenance_list_escape($t('No maintenance found')) ?></h3>
        <p><?= vs_maintenance_list_escape($t('Create the first maintenance work order.')) ?></p>
    </div>
</div>

<?php
plugin_vehiclescheduler_load_script('js/driver-grid.js');
plugin_vehiclescheduler_load_script('js/flash.js');
Html::footer();
