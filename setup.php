<?php

/**
 * Vehicle Scheduler plugin setup.
 */

define('PLUGIN_VEHICLESCHEDULER_VERSION', '0.1.0-dev');
define('PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION', '12.0.0');

$vehicleschedulerAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($vehicleschedulerAutoload)) {
    require_once $vehicleschedulerAutoload;
}

/**
 * Initialize plugin hooks.
 *
 * @return void
 */
function plugin_init_vehiclescheduler(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['vehiclescheduler'] = true;

    // Force apply plugin language as early as possible
    $PLUGIN_HOOKS['init']['vehiclescheduler'] = 'plugin_vehiclescheduler_apply_locale_hook';

    Plugin::registerClass('PluginVehicleschedulerProfile', [
        'addtabon' => ['Profile']
    ]);
    Plugin::registerClass('PluginVehicleschedulerMenu');
    Plugin::registerClass('PluginVehicleschedulerMenug');

    $PLUGIN_HOOKS['change_profile']['vehiclescheduler'] = [
        'PluginVehicleschedulerProfile',
        'changeProfile'
    ];

    /**
     * Registra somente o menu de gestão em Ferramentas.
     *
     * O portal requester continuará sendo acessado por card externo.
     */
    $PLUGIN_HOOKS['menu_toadd']['vehiclescheduler'] = [
        'tools' => 'PluginVehicleschedulerMenu',
    ];

    Plugin::registerClass('PluginVehicleschedulerDashboard');
    Plugin::registerClass('PluginVehicleschedulerVehicle');
    Plugin::registerClass('PluginVehicleschedulerDriver');
    Plugin::registerClass('PluginVehicleschedulerSchedule');
    Plugin::registerClass('PluginVehicleschedulerMaintenance');
    Plugin::registerClass('PluginVehicleschedulerWorkshop');
    Plugin::registerClass('PluginVehicleschedulerIncident');
    Plugin::registerClass('PluginVehicleschedulerInsuranceclaim');
    Plugin::registerClass('PluginVehicleschedulerDriverfine');
    Plugin::registerClass('PluginVehicleschedulerChecklist');
    Plugin::registerClass('PluginVehicleschedulerChecklistitem');
    Plugin::registerClass('PluginVehicleschedulerVehiclereport');
    Plugin::registerClass('PluginVehicleschedulerTheme');
    Plugin::registerClass('PluginVehicleschedulerConfig');
}

/**
 * Plugin metadata.
 *
 * @return array<string,mixed>
 */
function plugin_version_vehiclescheduler(): array
{
    return [
        'name'         => 'Vehicle Scheduler',
        'version'      => PLUGIN_VEHICLESCHEDULER_VERSION,
        'author'       => 'Vinicius Lopes <generalvini@gmail.com> (@ViniciusHonorato)',
        'license'      => 'PolyForm Noncommercial 1.0.0',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION,
                'max' => PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION,
            ],
            'php' => [
                'min' => '8.1'
            ]
        ]
    ];
}

/**
 * Check plugin prerequisites.
 *
 * @return bool
 */
function plugin_vehiclescheduler_check_prerequisites(): bool
{
    if (version_compare(GLPI_VERSION, PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION, '<')) {
        echo __('This plugin requires GLPI >= ', 'vehiclescheduler') . PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION;
        return false;
    }

    if (version_compare(GLPI_VERSION, PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION, '>=')) {
        echo __('This plugin requires GLPI < ', 'vehiclescheduler') . PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION;
        return false;
    }

    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        echo __('This plugin requires PHP >= 8.1', 'vehiclescheduler');
        return false;
    }

    return true;
}

/**
 * Hook: Apply user's plugin language setting on init
 * Called early during GLPI initialization
 *
 * @return void
 */
function plugin_vehiclescheduler_apply_locale_hook(): void
{
    include_once(__DIR__ . '/src/Bootstrap/common.php');
    plugin_vehiclescheduler_apply_configured_locale();
}

/**
 * Check runtime configuration.
 *
 * @param bool $verbose
 * @return bool
 */
function plugin_vehiclescheduler_check_config(bool $verbose = false): bool
{
    return true;
}
