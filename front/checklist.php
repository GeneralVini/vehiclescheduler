<?php

include_once __DIR__ . '/../src/Bootstrap/common.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

require_once(__DIR__ . '/checklist.render.php');

global $DB;

$rootDoc = plugin_vehiclescheduler_get_root_doc();
$t = static fn(string $message): string => __($message, 'vehiclescheduler');

$checklists = iterator_to_array($DB->request([
    'FROM'  => PluginVehicleschedulerChecklist::getTable(),
    'ORDER' => ['date_creation DESC'],
]));

$stats = [
    'total'     => count($checklists),
    'active'    => 0,
    'departure' => 0,
    'arrival'   => 0,
];

foreach ($checklists as $checklist) {
    if ((int) ($checklist['is_active'] ?? 0) === 1) {
        $stats['active']++;
    }

    if ((int) ($checklist['checklist_type'] ?? 0) === PluginVehicleschedulerChecklist::TYPE_DEPARTURE) {
        $stats['departure']++;
    }

    if ((int) ($checklist['checklist_type'] ?? 0) === PluginVehicleschedulerChecklist::TYPE_ARRIVAL) {
        $stats['arrival']++;
    }
}

$types = PluginVehicleschedulerChecklist::getChecklistTypes();
$itemCounts = [];

foreach ($checklists as $checklist) {
    $checklistId = (int) ($checklist['id'] ?? 0);
    $itemCounts[$checklistId] = countElementsInTable(
        PluginVehicleschedulerChecklistitem::getTable(),
        ['plugin_vehiclescheduler_checklists_id' => $checklistId]
    );
}

plugin_vehiclescheduler_apply_configured_locale();

Html::header($t('Checklists'), $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'checklist');

plugin_vehiclescheduler_apply_configured_locale();

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>

<div class="vs-checklist-list-page">
    <div class="vs-checklist-list-surface">
        <div class="vs-checklist-list-card">
            <div class="vs-checklist-list-header">
                <div>
                    <h1><i class="ti ti-checkbox"></i> <?= plugin_vehiclescheduler_escape($t('Checklist Management')) ?></h1>
                    <p class="vs-checklist-list-subtitle"><?= plugin_vehiclescheduler_escape($t('Verification templates for vehicle departure and arrival.')) ?></p>
                </div>

                <?php if (Session::haveRight('plugin_vehiclescheduler', CREATE)): ?>
                    <a href="<?= plugin_vehiclescheduler_escape(plugin_vehiclescheduler_get_front_url('checklist.form.php')) ?>" class="vs-checklist-list-create">
                        <i class="ti ti-plus"></i>
                        <?= plugin_vehiclescheduler_escape($t('New template')) ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="vs-checklist-list-kpis">
                <div class="vs-checklist-list-kpi">
                    <div class="vs-checklist-list-kpi-value"><?= (int) $stats['total'] ?></div>
                    <div class="vs-checklist-list-kpi-label"><?= plugin_vehiclescheduler_escape($t('Total templates')) ?></div>
                </div>
                <div class="vs-checklist-list-kpi">
                    <div class="vs-checklist-list-kpi-value"><?= (int) $stats['active'] ?></div>
                    <div class="vs-checklist-list-kpi-label"><?= plugin_vehiclescheduler_escape($t('Active templates')) ?></div>
                </div>
                <div class="vs-checklist-list-kpi">
                    <div class="vs-checklist-list-kpi-value"><?= (int) $stats['departure'] ?></div>
                    <div class="vs-checklist-list-kpi-label"><?= plugin_vehiclescheduler_escape($t('Departure checklists')) ?></div>
                </div>
                <div class="vs-checklist-list-kpi">
                    <div class="vs-checklist-list-kpi-value"><?= (int) $stats['arrival'] ?></div>
                    <div class="vs-checklist-list-kpi-label"><?= plugin_vehiclescheduler_escape($t('Arrival checklists')) ?></div>
                </div>
            </div>

            <div class="vs-checklist-list-content">
                <?php if ($checklists === []): ?>
                    <div class="vs-checklist-list-empty">
                        <div class="vs-checklist-list-empty-icon"><i class="ti ti-checkbox"></i></div>
                        <div class="vs-checklist-list-empty-text"><?= plugin_vehiclescheduler_escape($t('No checklist template created yet.')) ?></div>

                        <?php if (Session::haveRight('plugin_vehiclescheduler', CREATE)): ?>
                            <a href="<?= plugin_vehiclescheduler_escape(plugin_vehiclescheduler_get_front_url('checklist.form.php')) ?>" class="vs-checklist-list-action">
                                <i class="ti ti-plus"></i>
                                <?= plugin_vehiclescheduler_escape($t('Create first template')) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="vs-checklist-list-grid">
                        <?php foreach ($checklists as $checklist): ?>
                            <?php
                            $checklistId = (int) ($checklist['id'] ?? 0);
                            $iconMap = [
                                PluginVehicleschedulerChecklist::TYPE_DEPARTURE => 'ti ti-logout-2',
                                PluginVehicleschedulerChecklist::TYPE_ARRIVAL   => 'ti ti-login-2',
                                PluginVehicleschedulerChecklist::TYPE_BOTH      => 'ti ti-arrows-shuffle',
                            ];
                            $icon = $iconMap[(int) ($checklist['checklist_type'] ?? 0)] ?? 'ti ti-checkbox';
                            $typeClass = 'vs-checklist-list-badge--type-' . (int) ($checklist['checklist_type'] ?? 0);
                            ?>

                            <div class="vs-checklist-list-item">
                                <div class="vs-checklist-list-icon">
                                    <i class="<?= plugin_vehiclescheduler_escape($icon) ?>"></i>
                                </div>

                                <div>
                                    <div class="vs-checklist-list-title">
                                        <?= plugin_vehiclescheduler_escape((string) ($checklist['name'] ?? '')) ?>
                                    </div>

                                    <div class="vs-checklist-list-meta">
                                        <div class="vs-checklist-list-meta-item">
                                            <i class="ti ti-list-check"></i>
                                            <strong><?= (int) ($itemCounts[$checklistId] ?? 0) ?></strong> <?= plugin_vehiclescheduler_escape($t('items')) ?>
                                        </div>

                                        <span class="vs-checklist-list-badge <?= plugin_vehiclescheduler_escape($typeClass) ?>">
                                            <?= plugin_vehiclescheduler_escape($types[(int) ($checklist['checklist_type'] ?? 0)] ?? 'N/A') ?>
                                        </span>

                                        <span class="vs-checklist-list-badge <?= (int) ($checklist['is_active'] ?? 0) === 1 ? 'vs-checklist-list-badge--active' : 'vs-checklist-list-badge--inactive' ?>">
                                            <?= plugin_vehiclescheduler_escape((int) ($checklist['is_active'] ?? 0) === 1 ? $t('Active') : $t('Inactive')) ?>
                                        </span>

                                        <?php if ((int) ($checklist['is_mandatory'] ?? 0) === 1): ?>
                                            <span class="vs-checklist-list-badge vs-checklist-list-badge--mandatory">
                                                <?= plugin_vehiclescheduler_escape($t('Mandatory')) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="vs-checklist-list-actions">
                                    <a href="<?= plugin_vehiclescheduler_escape(plugin_vehiclescheduler_get_front_url('checklist.form.php') . '?id=' . $checklistId) ?>" class="vs-checklist-list-action">
                                        <i class="ti ti-edit"></i>
                                        <?= plugin_vehiclescheduler_escape($t('Edit')) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php Html::footer(); ?>
