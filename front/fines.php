<?php

include_once __DIR__ . '/../src/Bootstrap/common.php';

PluginVehicleschedulerDriverfine::requireAdminFines();

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$t = static fn(string $text): string => __($text, 'vehiclescheduler');

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$statusFilter = PluginVehicleschedulerInput::int($_GET, 'status', PluginVehicleschedulerDriverfine::STATUS_OPEN, 0);
$validStatuses = array_keys(PluginVehicleschedulerDriverfine::getAllStatus());

if ($statusFilter > 0 && !in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = PluginVehicleschedulerDriverfine::STATUS_OPEN;
}

if (isset($_POST['quick_fine_action'])) {
    PluginVehicleschedulerDriverfine::requireAdminFines();

    $fineId = PluginVehicleschedulerInput::int($_POST, 'fine_id', 0, 1);
    $action = PluginVehicleschedulerInput::enum($_POST, 'quick_fine_action', ['paid', 'appealed', 'cancel'], '');
    $statusMap = [
        'paid'    => PluginVehicleschedulerDriverfine::STATUS_PAID,
        'appealed' => PluginVehicleschedulerDriverfine::STATUS_APPEALED,
        'cancel'  => PluginVehicleschedulerDriverfine::STATUS_CANCELLED,
    ];

    if ($fineId > 0 && isset($statusMap[$action])) {
        $fine = new PluginVehicleschedulerDriverfine();
        if ($fine->getFromDB($fineId)) {
            $fine->update([
                'id'     => $fineId,
                'status' => $statusMap[$action],
            ]);
            Session::addMessageAfterRedirect($t('Fine updated successfully.'), false, INFO);
        }
    }

    Html::redirect(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . $statusFilter);
}

$rows = PluginVehicleschedulerDriverfine::getManagementRows($statusFilter);
$summary = PluginVehicleschedulerDriverfine::buildManagementSummary(
    PluginVehicleschedulerDriverfine::getManagementRows(0)
);
$severities = PluginVehicleschedulerDriverfine::getAllSeverities();
$statuses = PluginVehicleschedulerDriverfine::getAllStatus();
$pointsMap = PluginVehicleschedulerDriverfine::getSeverityPoints();

plugin_vehiclescheduler_apply_configured_locale();

Html::header($t('Fines'), $self, 'tools', PluginVehicleschedulerMenu::class, 'management');

plugin_vehiclescheduler_apply_configured_locale();

plugin_vehiclescheduler_load_css([
    'css/pages/fines.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();

$filters = [
    0 => ['label' => $t('All'), 'count' => $summary['total']],
    PluginVehicleschedulerDriverfine::STATUS_OPEN => ['label' => $t('Open'), 'count' => $summary['open']],
    PluginVehicleschedulerDriverfine::STATUS_APPEALED => ['label' => $t('Appealed'), 'count' => $summary['appealed']],
    PluginVehicleschedulerDriverfine::STATUS_PAID => ['label' => $t('Paid'), 'count' => $summary['paid']],
    PluginVehicleschedulerDriverfine::STATUS_CANCELLED => ['label' => $t('Cancelled'), 'count' => $summary['cancelled']],
];

?>
<div class="vs-fines-page">
    <div class="vs-page-header">
        <div class="vs-header-content">
            <div class="vs-header-title">
                <div class="vs-header-icon-wrapper">
                    <i class="ti ti-file-alert vs-header-icon"></i>
                </div>
                <div>
                    <h2><?= $h($t('Fine Management')) ?></h2>
                    <p class="vs-page-subtitle"><?= $h($t('Administrative control of infractions, points, and processing status.')) ?></p>
                </div>
            </div>

            <a href="<?= $h(plugin_vehiclescheduler_get_front_url('driverfine.form.php')) ?>" class="vs-btn-add">
                <i class="ti ti-plus"></i>
                <span><?= $h($t('New fine')) ?></span>
            </a>
        </div>
    </div>

    <section class="vs-fines-status-grid" aria-label="<?= $h($t('Fine summary')) ?>">
        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . PluginVehicleschedulerDriverfine::STATUS_OPEN) ?>" class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-alert-triangle"></i></span>
            <strong><?= (int) $summary['open'] ?></strong>
            <span><?= $h($t('Open')) ?></span>
        </a>
        <div class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-id-badge"></i></span>
            <strong><?= (int) $summary['activePoints'] ?></strong>
            <span><?= $h($t('Active points')) ?></span>
        </div>
        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . PluginVehicleschedulerDriverfine::STATUS_APPEALED) ?>" class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-file-pencil"></i></span>
            <strong><?= (int) $summary['appealed'] ?></strong>
            <span><?= $h($t('Appealed')) ?></span>
        </a>
        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . PluginVehicleschedulerDriverfine::STATUS_PAID) ?>" class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-circle-check"></i></span>
            <strong><?= (int) $summary['paid'] ?></strong>
            <span><?= $h($t('Paid')) ?></span>
        </a>
    </section>

    <section class="vs-fines-toolbar">
        <div class="vs-fines-filter-list">
            <?php foreach ($filters as $status => $filter): ?>
                <?php $url = plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . (int) $status; ?>
                <a href="<?= $h($url) ?>" class="vs-fines-filter<?= (int) $status === (int) $statusFilter ? ' is-active' : '' ?>">
                    <span><?= $h($filter['label']) ?></span>
                    <strong><?= (int) $filter['count'] ?></strong>
                </a>
            <?php endforeach; ?>
        </div>

        <span class="vs-fines-results"><?= $h(sprintf($t('%d records'), count($rows))) ?></span>
    </section>

    <section class="vs-fines-table-card">
        <div class="vs-fines-table-card__header">
            <span class="vs-fines-table-card__title"><i class="ti ti-list-details"></i> <?= $h($t('Registered fines')) ?></span>
        </div>

        <?php if ($rows === []): ?>
            <div class="vs-fines-empty">
                <div class="vs-fines-empty__icon"><i class="ti ti-file-alert"></i></div>
                <h3><?= $h($t('No fine found')) ?></h3>
                <p><?= $h($t('There are no records for the selected filter.')) ?></p>
            </div>
        <?php else: ?>
            <div class="vs-fines-table-wrap">
                <table class="vs-fines-table">
                    <thead>
                        <tr>
                            <th><?= $h($t('Date')) ?></th>
                            <th><?= $h($t('Code')) ?></th>
                            <th><?= $h($t('Driver')) ?></th>
                            <th><?= $h($t('Vehicle')) ?></th>
                            <th><?= $h($t('Severity')) ?></th>
                            <th><?= $h($t('Points')) ?></th>
                            <th><?= $h($t('Status')) ?></th>
                            <th><?= $h($t('Description')) ?></th>
                            <th><?= $h($t('Actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $fine): ?>
                            <?php
                            $severity = (int) ($fine['severity'] ?? 0);
                            $status = (int) ($fine['status'] ?? 0);
                            $driverId = (int) ($fine['plugin_vehiclescheduler_drivers_id'] ?? 0);
                            $violationCode = trim((string) ($fine['violation_code'] ?? ''));
                            $violationSplit = trim((string) ($fine['violation_split'] ?? ''));
                            $violationDisplay = $violationCode !== ''
                                ? $violationCode . ($violationSplit !== '' ? '-' . $violationSplit : '')
                                : 'Manual';
                            $vehicleName = trim((string) ($fine['vehicle_name'] ?? ''));
                            $vehiclePlate = trim((string) ($fine['vehicle_plate'] ?? ''));
                            $vehicleDisplay = $vehicleName !== ''
                                ? trim($vehicleName . ($vehiclePlate !== '' ? ' / ' . $vehiclePlate : ''))
                                : $t('Unlinked');
                            $description = PluginVehicleschedulerInput::text(
                                ['description' => $fine['description'] ?? ''],
                                'description',
                                160,
                                ''
                            );
                            ?>
                            <tr>
                                <td><?= $h(Html::convDate((string) ($fine['fine_date'] ?? ''))) ?></td>
                                <td><span class="vs-fines-code"><?= $h($violationDisplay) ?></span></td>
                                <td>
                                    <?php if ($driverId > 0): ?>
                                        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('driver.form.php') . '?id=' . $driverId) ?>" class="vs-fines-driver-link">
                                            <?= $h((string) ($fine['driver_name'] ?? $t('Not informed'))) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= $h($t('Not informed')) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= $h($vehicleDisplay) ?></td>
                                <td>
                                    <span class="vs-driverfine-badge vs-driverfine-badge--<?= $h(PluginVehicleschedulerDriverfine::getSeverityModifier($severity)) ?>">
                                        <?= $h($severities[$severity] ?? $t('Not defined')) ?>
                                    </span>
                                </td>
                                <td><span class="vs-fines-points"><?= (int) ($pointsMap[$severity] ?? 0) ?></span></td>
                                <td>
                                    <span class="vs-driverfine-badge vs-driverfine-badge--<?= $h(PluginVehicleschedulerDriverfine::getStatusModifier($status)) ?>">
                                        <?= $h($statuses[$status] ?? $t('No status')) ?>
                                    </span>
                                </td>
                                <td><?= $h($description) ?></td>
                                <td>
                                    <div class="vs-fines-actions">
                                        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('driverfine.form.php') . '?id=' . (int) $fine['id']) ?>" class="vs-fines-action">
                                            <i class="ti ti-pencil"></i>
                                            <span><?= $h($t('Open')) ?></span>
                                        </a>
                                        <?php if ($status === PluginVehicleschedulerDriverfine::STATUS_OPEN || $status === PluginVehicleschedulerDriverfine::STATUS_APPEALED): ?>
                                            <form method="post">
                                                <input type="hidden" name="fine_id" value="<?= (int) $fine['id'] ?>">
                                                <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                                                <button type="submit" name="quick_fine_action" value="paid" class="vs-fines-action vs-fines-action--success">
                                                    <?= $h($t('Mark as paid')) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php
Html::footer();
