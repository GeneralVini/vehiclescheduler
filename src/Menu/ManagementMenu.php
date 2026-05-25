<?php

namespace GlpiPlugin\Vehiclescheduler\Menu;

use CommonGLPI;

class ManagementMenu extends CommonGLPI
{
    /**
     * ACL right used by the management menu.
     *
     * @var string
     */
    public static $rightname = 'plugin_vehiclescheduler_management';

    /**
     * Returns the management menu content for the Tools section.
     *
     * @return array|false
     */
    public static function getMenuContent()
    {
        if (!\PluginVehicleschedulerProfile::canViewManagement()) {
            return false;
        }

        return [
            'title' => __('Fleet Management', 'vehiclescheduler'),
            'page'  => '/plugins/vehiclescheduler/front/management.php',
            'icon'  => 'ti ti-car-suv',
            'links' => [
                'search' => '/plugins/vehiclescheduler/front/management.php',
            ],
        ];
    }
}
