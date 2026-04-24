<?php

include_once __DIR__ . '/../inc/common.inc.php';

plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

Session::checkRight('plugin_vehiclescheduler', READ);

$rootDoc = plugin_vehiclescheduler_get_root_doc();

Html::header('Incidentes', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'incidents');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>

<div class="vs-page-header">
    <div class="vs-header-content">
        <div class="vs-header-title">
            <div class="vs-header-icon-wrapper">
                <i class="ti ti-alert-triangle vs-header-icon"></i>
            </div>
            <h2>Gestao de Incidentes</h2>
        </div>

        <?php if (Session::haveRight('plugin_vehiclescheduler', CREATE)): ?>
            <a href="<?= htmlspecialchars(plugin_vehiclescheduler_get_front_url('incident.form.php'), ENT_QUOTES, 'UTF-8') ?>" class="vs-btn-add">
                <i class="ti ti-plus"></i>
                <span>Reportar Incidente</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
Search::show('PluginVehicleschedulerIncident');
Html::footer();
