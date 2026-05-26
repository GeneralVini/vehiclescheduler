<?php

/**
 * Fleet operational management controller.
 *
 * Scope:
 * - operational queues;
 * - critical alerts;
 * - quick actions;
 * - CRUD shortcuts;
 * - access to executive dashboard and wallboard;
 * - visual accessibility controls.
 */

include_once __DIR__ . '/../src/Bootstrap/common.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

$root_doc = plugin_vehiclescheduler_get_root_doc();

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$t = static function (string $message): string {
    return __($message, 'vehiclescheduler');
};

$urls = [
    'executive'     => plugin_vehiclescheduler_get_front_url('admin_dashboard.php'),
    'wallboard'     => plugin_vehiclescheduler_get_front_url('admin_dashboard.php') . '?standalone=1',
    'schedule'      => plugin_vehiclescheduler_get_front_url('schedule.php'),
    'schedule_form' => plugin_vehiclescheduler_get_front_url('schedule.form.php'),
    'incident'      => plugin_vehiclescheduler_get_front_url('incident.php'),
    'maintenance'   => plugin_vehiclescheduler_get_front_url('maintenance.php'),
    'vehicle'       => plugin_vehiclescheduler_get_front_url('vehicle.php'),
    'driver'        => plugin_vehiclescheduler_get_front_url('driver.php'),
    'driver_form'   => plugin_vehiclescheduler_get_front_url('driver.form.php'),
    'checklist'     => plugin_vehiclescheduler_get_front_url('checklist.php'),
    'config'        => plugin_vehiclescheduler_get_front_url('config.form.php'),
    'fines'         => plugin_vehiclescheduler_get_front_url('fines.php'),
];

$request_method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($request_method === 'POST') {
    $action = PluginVehicleschedulerInput::enum(
        $_POST,
        'action',
        ['approve_schedule', 'reject_schedule'],
        ''
    );
    $schedule_id = PluginVehicleschedulerInput::int($_POST, 'schedule_id', 0, 1);

    try {
        switch ($action) {
            case 'approve_schedule':
                Session::checkRight('plugin_vehiclescheduler_approve', READ);

                if ($schedule_id <= 0) {
                    throw new RuntimeException($t('Invalid reservation for approval.'));
                }

                PluginVehicleschedulerDashboard::approveSchedule((int) $schedule_id);
                Session::addMessageAfterRedirect($t('Reservation approved successfully'), false, INFO);
                break;

            case 'reject_schedule':
                Session::checkRight('plugin_vehiclescheduler_approve', READ);

                if ($schedule_id <= 0) {
                    throw new RuntimeException($t('Invalid reservation for rejection.'));
                }

                PluginVehicleschedulerDashboard::rejectSchedule((int) $schedule_id);
                Session::addMessageAfterRedirect($t('Reservation rejected successfully'), false, INFO);
                break;

            default:
                Session::addMessageAfterRedirect($t('Invalid action.'), true, ERROR);
                break;
        }
    } catch (RuntimeException $e) {
        Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
    } catch (Throwable $e) {
        Toolbox::logInFile(
            'php-errors',
            '[vehiclescheduler] Operational dashboard error: ' . $e->getMessage() . PHP_EOL
        );

        Session::addMessageAfterRedirect(
            $t('Unable to execute the requested action.'),
            true,
            ERROR
        );
    }

    Html::redirect($self !== '' ? $self : $urls['schedule']);
    exit;
}

$data = PluginVehicleschedulerDashboard::getDashboardData();

$kpi = is_array($data['kpi'] ?? null) ? $data['kpi'] : [];
$lists = is_array($data['lists'] ?? null) ? $data['lists'] : [];

$pending_reservations = is_array($lists['pending_reservations'] ?? null) ? $lists['pending_reservations'] : [];
$cnh_alerts           = is_array($lists['cnh_alerts'] ?? null) ? $lists['cnh_alerts'] : [];
$recent_incidents     = is_array($lists['recent_incidents'] ?? null) ? $lists['recent_incidents'] : [];
$checklists_enabled   = !empty($data['checklists_enabled']);

$can_approve_reservations = Session::haveRight('plugin_vehiclescheduler_approve', READ);

$get_cnh_badge_class = static function (int $days): string {
    if ($days <= 30) {
        return 'vs-badge vs-badge--danger';
    }

    if ($days <= 60) {
        return 'vs-badge vs-badge--warning';
    }

    return 'vs-badge vs-badge--info';
};

$status_items = [
    [
        'label' => $t('Pending reservations'),
        'value' => (string) (int) ($kpi['reservations_new'] ?? 0),
        'href'  => $urls['schedule'] . '?status=1',
        'icon'  => 'ti ti-clock-check',
        'tone'  => 'primary',
    ],
    [
        'label' => $t('CNH alerts'),
        'value' => (string) count($cnh_alerts),
        'href'  => $urls['driver'],
        'icon'  => 'ti ti-id-badge',
        'tone'  => count($cnh_alerts) > 0 ? 'warning' : 'neutral',
    ],
    [
        'label' => $t('Open incidents'),
        'value' => (string) (int) ($kpi['incidents_open'] ?? 0),
        'href'  => $urls['incident'],
        'icon'  => 'ti ti-alert-triangle',
        'tone'  => ((int) ($kpi['incidents_open'] ?? 0)) > 0 ? 'danger' : 'neutral',
    ],
    [
        'label' => $t('Active maintenances'),
        'value' => (string) (int) ($kpi['maintenances_active'] ?? 0),
        'href'  => $urls['maintenance'],
        'icon'  => 'ti ti-tool',
        'tone'  => ((int) ($kpi['maintenances_active'] ?? 0)) > 0 ? 'warning' : 'neutral',
    ],
    [
        'label' => $t('Pending checklists'),
        'value' => $checklists_enabled ? (string) (int) ($kpi['checklist_pending'] ?? 0) : '-',
        'href'  => $checklists_enabled ? $urls['schedule'] . '?checklist_pending=1' : $urls['checklist'],
        'icon'  => 'ti ti-checklist',
        'tone'  => $checklists_enabled && ((int) ($kpi['checklist_pending'] ?? 0)) > 0 ? 'warning' : 'neutral',
    ],
    [
        'label' => $t('Active vehicles'),
        'value' => (int) ($kpi['vehicles_active'] ?? 0) . '/' . (int) ($kpi['vehicles_total'] ?? 0),
        'href'  => $urls['vehicle'],
        'icon'  => 'ti ti-car',
        'tone'  => 'primary',
    ],
];

$module_links = [
    [
        'label' => $t('Vehicles'),
        'desc'  => $t('Registry and availability'),
        'href'  => $urls['vehicle'],
        'icon'  => 'ti ti-car',
    ],
    [
        'label' => $t('Drivers'),
        'desc'  => $t('Registry and CNH'),
        'href'  => $urls['driver'],
        'icon'  => 'ti ti-steering-wheel',
    ],
    [
        'label' => $t('Reservations'),
        'desc'  => $t('Requests and approvals'),
        'href'  => $urls['schedule'],
        'icon'  => 'ti ti-calendar-event',
    ],
    [
        'label' => $t('Incidents'),
        'desc'  => $t('Claims and occurrences'),
        'href'  => $urls['incident'],
        'icon'  => 'ti ti-alert-triangle',
    ],
    [
        'label' => $t('Maintenances'),
        'desc'  => $t('Preventive and corrective'),
        'href'  => $urls['maintenance'],
        'icon'  => 'ti ti-tool',
    ],
    [
        'label' => $t('Checklist'),
        'desc'  => $t('Departure and return'),
        'href'  => $urls['checklist'],
        'icon'  => 'ti ti-checklist',
    ],
    [
        'label' => $t('Settings'),
        'desc'  => $t('Plugin flags'),
        'href'  => $urls['config'],
        'icon'  => 'ti ti-settings',
    ],
];

if (PluginVehicleschedulerDriverfine::canAdminFines()) {
    $module_links[] = [
        'label' => $t('Fines'),
        'desc'  => $t('Infractions and points'),
        'href'  => $urls['fines'],
        'icon'  => 'ti ti-file-alert',
    ];
}

Html::header(
    $t('Fleet Management'),
    $self,
    'tools',
    \PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$js_file = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/management.js';
$js_ver  = is_file($js_file) ? filemtime($js_file) : \PLUGIN_VEHICLESCHEDULER_VERSION;
$js_url  = plugin_vehiclescheduler_get_public_url('js/management.js') . '?v=' . $js_ver;

?>
<div class="vs-page vs-page-management">
    <div class="vs-dashboard-wrap">
        <section class="vs-management-header">
            <div class="vs-management-header__main">
                <div class="vs-management-header__mark">
                    <i class="ti ti-steering-wheel"></i>
                </div>
                <div class="vs-management-header__copy">
                    <h2><?php echo $h($t('Fleet Management')); ?></h2>
                    <p><?php echo $h($t('Operational queue for reservations, alerts, and fleet resources.')); ?></p>
                </div>
            </div>

            <div class="vs-management-header__actions">
                <div class="vs-visual-controls">
                    <span class="vs-visual-controls__label"><?php echo $h($t('Visual')); ?></span>
                    <div class="vs-theme-toggle" title="<?php echo $h($t('Toggle theme')); ?>">
                        <input type="checkbox" id="vsMgmtThemeToggle" aria-label="<?php echo $h($t('Toggle theme')); ?>">
                        <label for="vsMgmtThemeToggle">
                            <span class="vs-theme-toggle__sun"><i class="ti ti-sun"></i></span>
                            <span class="vs-theme-toggle__moon"><i class="ti ti-moon"></i></span>
                        </label>
                    </div>

                    <div class="vs-control-group" aria-label="<?php echo $h($t('Font controls')); ?>">
                        <button type="button" class="vs-control-btn" id="vsFontDecrease" title="<?php echo $h($t('Decrease font')); ?>" aria-label="<?php echo $h($t('Decrease font')); ?>">
                            <i class="ti ti-minus"></i>
                        </button>
                        <button type="button" class="vs-control-btn" id="vsFontReset" title="<?php echo $h($t('Reset font')); ?>" aria-label="<?php echo $h($t('Reset font')); ?>">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button type="button" class="vs-control-btn" id="vsFontIncrease" title="<?php echo $h($t('Increase font')); ?>" aria-label="<?php echo $h($t('Increase font')); ?>">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>

                    <button type="button" class="vs-control-btn vs-control-btn--label" id="vsVisualReset" title="<?php echo $h($t('Reset visual')); ?>" aria-label="<?php echo $h($t('Reset visual')); ?>">
                        Reset
                    </button>
                </div>

                <a href="<?php echo $h($urls['wallboard']); ?>" target="_blank" rel="noopener" class="vs-action-btn vs-action-btn--ghost">
                    <i class="ti ti-screen-share"></i>
                    <span><?php echo $h($t('Wallboard')); ?></span>
                </a>

                <a href="<?php echo $h($urls['schedule']); ?>" class="vs-action-btn">
                    <i class="ti ti-calendar-event"></i>
                    <span><?php echo $h($t('Reservations')); ?></span>
                </a>
            </div>
        </section>

        <section class="vs-management-status" aria-label="<?php echo $h($t('Operational indicators')); ?>">
            <?php foreach ($status_items as $item): ?>
                <a href="<?php echo $h($item['href']); ?>" class="vs-status-item vs-status-item--<?php echo $h($item['tone']); ?>">
                    <span class="vs-status-item__icon"><i class="<?php echo $h($item['icon']); ?>"></i></span>
                    <span class="vs-status-item__body">
                        <strong><?php echo $h($item['value']); ?></strong>
                        <span><?php echo $h($item['label']); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </section>

        <section class="vs-management-workbench">
            <div class="vs-quick-panel">
                <div class="vs-panel-header">
                    <span class="vs-panel-title"><i class="ti ti-layout-grid"></i> <?php echo $h($t('Quick access')); ?></span>
                </div>

                <div class="vs-module-list">
                    <?php foreach ($module_links as $module): ?>
                        <a href="<?php echo $h($module['href']); ?>" class="vs-module-link">
                            <span class="vs-module-link__icon"><i class="<?php echo $h($module['icon']); ?>"></i></span>
                            <span class="vs-module-link__body">
                                <strong><?php echo $h($module['label']); ?></strong>
                                <small><?php echo $h($module['desc']); ?></small>
                            </span>
                            <i class="ti ti-chevron-right vs-module-link__arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="vs-workbench-main">
                <section class="vs-main-grid vs-main-grid--primary">
                    <div class="vs-card">
                        <div class="vs-card-header">
                            <span class="vs-card-title"><i class="ti ti-clock-check"></i> <?php echo $h($t('Pending reservations')); ?></span>
                            <a href="<?php echo $h($urls['schedule'] . '?status=1'); ?>" class="vs-card-link"><?php echo $h($t('Open reservations')); ?></a>
                        </div>

                        <?php if (empty($pending_reservations)): ?>
                            <div class="vs-empty-state"><?php echo $h($t('No reservations awaiting analysis.')); ?></div>
                        <?php else: ?>
                            <table class="vs-table">
                                <thead>
                                    <tr>
                                        <th><?php echo $h($t('Requester')); ?></th>
                                        <th><?php echo $h($t('Vehicle')); ?></th>
                                        <th><?php echo $h($t('Period')); ?></th>
                                        <th><?php echo $h($t('Destination')); ?></th>
                                        <th><?php echo $h($t('Actions')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo $h($reservation['requester_name'] ?? ''); ?></td>
                                            <td><?php echo $h($reservation['vehicle_name'] ?? ''); ?></td>
                                            <td class="vs-nowrap">
                                                <?php echo Html::convDate(substr((string) ($reservation['begin_date'] ?? ''), 0, 10)); ?>
                                                &rarr;
                                                <?php echo Html::convDate(substr((string) ($reservation['end_date'] ?? ''), 0, 10)); ?>
                                            </td>
                                            <td><?php echo $h($reservation['destination'] ?? ''); ?></td>
                                            <td>
                                                <?php if ($can_approve_reservations): ?>
                                                    <div class="vs-inline-actions">
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="approve_schedule">
                                                            <input type="hidden" name="schedule_id" value="<?php echo (int) ($reservation['id'] ?? 0); ?>">
                                                            <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
                                                            <button type="submit" class="vs-btn-sm vs-btn-sm--success"><?php echo $h($t('Approve')); ?></button>
                                                        </form>

                                                        <form method="post">
                                                            <input type="hidden" name="action" value="reject_schedule">
                                                            <input type="hidden" name="schedule_id" value="<?php echo (int) ($reservation['id'] ?? 0); ?>">
                                                            <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
                                                            <button type="submit" class="vs-btn-sm vs-btn-sm--danger"><?php echo $h($t('Reject')); ?></button>
                                                        </form>

                                                        <a href="<?php echo $h($urls['schedule_form'] . '?id=' . (int) ($reservation['id'] ?? 0)); ?>" class="vs-icon-link" title="<?php echo $h($t('View reservation')); ?>">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <a href="<?php echo $h($urls['schedule_form'] . '?id=' . (int) ($reservation['id'] ?? 0)); ?>" class="vs-btn-sm vs-btn-sm--neutral">
                                                        <?php echo $h($t('View')); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="vs-main-grid">
                    <div class="vs-card">
                        <div class="vs-card-header">
                            <span class="vs-card-title"><i class="ti ti-id-badge"></i> <?php echo $h($t('CNH alerts')); ?></span>
                            <a href="<?php echo $h($urls['driver']); ?>" class="vs-card-link"><?php echo $h($t('View all')); ?></a>
                        </div>

                        <?php if (empty($cnh_alerts)): ?>
                            <div class="vs-empty-state"><?php echo $h($t('No CNH expiring in the next 90 days.')); ?></div>
                        <?php else: ?>
                            <table class="vs-table">
                                <thead>
                                    <tr>
                                        <th><?php echo $h($t('Driver')); ?></th>
                                        <th><?php echo $h($t('Category')); ?></th>
                                        <th><?php echo $h($t('Expiry')); ?></th>
                                        <th><?php echo $h($t('Status')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cnh_alerts as $driver): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $h($urls['driver_form'] . '?id=' . (int) ($driver['id'] ?? 0)); ?>">
                                                    <?php echo $h(getUserName((int) ($driver['users_id'] ?? 0))); ?>
                                                </a>
                                            </td>
                                            <td><?php echo $h($driver['cnh_category'] ?? ''); ?></td>
                                            <td><?php echo Html::convDate((string) ($driver['cnh_expiry'] ?? '')); ?></td>
                                            <td>
                                                <span class="<?php echo $get_cnh_badge_class((int) ($driver['days_to_expiry'] ?? 0)); ?>">
                                                    <?php echo sprintf($h($t('%d days')), (int) ($driver['days_to_expiry'] ?? 0)); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="vs-card">
                        <div class="vs-card-header">
                            <span class="vs-card-title"><i class="ti ti-alert-triangle"></i> <?php echo $h($t('Recent incidents')); ?></span>
                            <a href="<?php echo $h($urls['incident']); ?>" class="vs-card-link"><?php echo $h($t('View all')); ?></a>
                        </div>

                        <?php if (empty($recent_incidents)): ?>
                            <div class="vs-empty-state"><?php echo $h($t('No incidents registered.')); ?></div>
                        <?php else: ?>
                            <table class="vs-table">
                                <thead>
                                    <tr>
                                        <th><?php echo $h($t('Vehicle')); ?></th>
                                        <th><?php echo $h($t('Date')); ?></th>
                                        <th><?php echo $h($t('Type')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_incidents as $incident): ?>
                                        <tr>
                                            <td><?php echo $h($incident['vehicle_name'] ?? ''); ?></td>
                                            <td><?php echo Html::convDateTime((string) ($incident['incident_date'] ?? '')); ?></td>
                                            <td><?php echo $h($incident['name'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </section>
    </div>
</div>
<?php
echo "<script src='" . $h($js_url) . "' defer></script>";
Html::footer();
