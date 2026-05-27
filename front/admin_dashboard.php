<?php

/**
 * Fleet executive dashboard controller.
 *
 * Scope:
 * - executive monitoring;
 * - compact large-screen overview;
 * - standalone wallboard mode;
 * - stable rendering without optional frontend dependencies.
 */

include_once(__DIR__ . '/../src/Bootstrap/common.php');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

Session::checkRight('plugin_vehiclescheduler_management', READ);

$root_doc = plugin_vehiclescheduler_get_root_doc();

$standalone = PluginVehicleschedulerInput::int($_GET, 'standalone', 0, 0, 1) === 1;

$exec = PluginVehicleschedulerDashboard::getExecutiveBoardData();

$summary            = $exec['summary'];
$reservations_chart = $exec['charts']['reservations_by_status'];
$recent_requests    = array_slice($exec['lists']['recent_requests'], 0, 6);
$top_vehicles       = array_slice($exec['lists']['top_vehicles'], 0, 6);
$realtime           = $exec['realtime'];

$management_url = plugin_vehiclescheduler_get_front_url('management.php');
$normal_url     = plugin_vehiclescheduler_get_front_url('admin_dashboard.php');
$standalone_url = plugin_vehiclescheduler_get_front_url('admin_dashboard.php') . '?standalone=1';
$pageTitle = __('Executive Fleet View', 'vehiclescheduler');
$t = static fn(string $message): string => __($message, 'vehiclescheduler');
$h = static fn(?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

plugin_vehiclescheduler_apply_configured_locale();

if ($standalone) {
    Html::nullHeader($pageTitle, $management_url);
    echo "<script>document.body.classList.add('vs-wallboard-standalone-body');</script>";
} else {
    Html::header($pageTitle, $_SERVER['PHP_SELF'], 'tools');
}

plugin_vehiclescheduler_apply_configured_locale();

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$js_file = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/admin-dashboard.js';
$js_ver  = is_file($js_file) ? filemtime($js_file) : PLUGIN_VEHICLESCHEDULER_VERSION;
$js_url  = plugin_vehiclescheduler_get_public_url('js/admin-dashboard.js') . '?v=' . $js_ver;

$page_classes = 'vs-page vs-page-admin-dashboard';
if ($standalone) {
    $page_classes .= ' vs-standalone';
}

?>
<div
    class="<?php echo $h($page_classes); ?>"
    data-next-refresh-at="<?php echo $h($t('Next refresh at %s')); ?>"
    data-time-locale="<?php echo $h(str_replace('_', '-', (string) ($_SESSION['glpilanguage'] ?? 'en_GB'))); ?>">
    <div class="vs-wallboard-shell">

        <section class="vs-wallboard-hero">
            <div class="vs-wallboard-hero__top">
                <div class="vs-wallboard-pill">
                    <i class="ti ti-broadcast"></i>
                    <span><?php echo $h($t('Fleet Monitoring Center')); ?></span>
                </div>

                <div class="vs-wallboard-controls">
                    <div class="vs-theme-toggle" title="<?php echo $h($t('Toggle theme')); ?>">
                        <input type="checkbox" id="vsExecThemeToggle" aria-label="<?php echo $h($t('Toggle theme')); ?>">
                        <label for="vsExecThemeToggle">
                            <svg class="sun" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.8 1.42-1.42zM1 11h3v2H1v-2zm10-10h2v3h-2V1zm9.66 3.46l-1.41-1.41-1.8 1.79 1.42 1.42 1.79-1.8zM20 11h3v2h-3v-2zM11 20h2v3h-2v-3zm7.24-1.84l1.8 1.79 1.41-1.41-1.79-1.8-1.42 1.42zM4.34 19.54l1.41 1.41 1.8-1.79-1.42-1.42-1.79 1.8zM12 6a6 6 0 100 12 6 6 0 000-12z" />
                            </svg>
                            <svg class="moon" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M21 14.53A8.5 8.5 0 0110.47 3 6.5 6.5 0 1019 16.5c.71 0 1.4-.12 2.06-.33-.03-.55-.04-1.09-.06-1.64z" />
                            </svg>
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

                    <div class="vs-control-group" aria-label="<?php echo $h($t('Visual controls')); ?>">
                        <button type="button" class="vs-control-btn vs-control-btn--label" id="vsVisualReset" title="<?php echo $h($t('Reset visual')); ?>" aria-label="<?php echo $h($t('Reset visual')); ?>">
                            <?php echo $h($t('Reset visual')); ?>
                        </button>
                    </div>

                    <div class="vs-wallboard-actions">
                        <?php if (!$standalone): ?>
                            <a href="<?php echo $h($standalone_url); ?>" target="_blank" rel="noopener" class="vs-action-btn">
                                <i class="ti ti-screen-share"></i>
                                <span><?php echo $h($t('Open wallboard')); ?></span>
                            </a>

                            <a href="<?php echo $h($management_url); ?>" class="vs-action-btn">
                                <i class="ti ti-arrow-left"></i>
                                <span><?php echo $h($t('Back to operations')); ?></span>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $h($normal_url); ?>" class="vs-action-btn">
                                <i class="ti ti-layout-sidebar"></i>
                                <span><?php echo $h($t('Normal mode')); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="vs-wallboard-hero__content">
                <div class="vs-wallboard-hero__main">
                    <h1 class="vs-dashboard-title">
                        <i class="ti ti-layout-dashboard"></i>
                        <span><?php echo $h($t('Executive Fleet Wallboard')); ?></span>
                    </h1>
                    <p class="vs-dashboard-subtitle">
                        <?php echo $h($t('Continuous view of availability, requests, trips, incidents, and fleet operations.')); ?>
                    </p>
                </div>

                <div class="vs-wallboard-status">
                    <div class="vs-wallboard-clock">
                        <div class="vs-wallboard-clock__label"><?php echo $h($t('Clock')); ?></div>
                        <div id="vsWallClock" class="vs-wallboard-clock__value">--:--:--</div>
                    </div>

                    <div class="vs-wallboard-status-pill">
                        <strong><?php echo $h($t('Refreshes in')); ?></strong>
                        <span id="vsRefreshCountdown">15s</span>
                    </div>

                    <div class="vs-wallboard-status-pill">
                        <strong><?php echo $h($t('Last update')); ?></strong>
                        <span><?php echo $h((string) $realtime['generated_at']); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="vs-wallboard-kpi-grid">
            <div class="vs-wallboard-kpi vs-wallboard-kpi--primary">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Availability')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-car-suv"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo number_format((float) $summary['availability_pct'], 1, ',', '.'); ?>%</div>
            </div>

            <div class="vs-wallboard-kpi vs-wallboard-kpi--warning">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Pending requests')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-calendar-plus"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo (int) $summary['pending_requests']; ?></div>
            </div>

            <div class="vs-wallboard-kpi vs-wallboard-kpi--info">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Trips in progress')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-route-2"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo (int) $summary['trips_in_progress']; ?></div>
            </div>

            <div class="vs-wallboard-kpi vs-wallboard-kpi--neutral">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Unavailable vehicles')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-car-off"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo (int) $summary['vehicles_unavailable']; ?></div>
            </div>

            <div class="vs-wallboard-kpi vs-wallboard-kpi--warning">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Critical CNHs')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-id-badge"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo (int) $summary['cnh_critical']; ?></div>
            </div>

            <div class="vs-wallboard-kpi vs-wallboard-kpi--danger">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Open incidents')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-alert-triangle"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo (int) $summary['incidents_open']; ?></div>
            </div>

            <div class="vs-wallboard-kpi vs-wallboard-kpi--warning">
                <div class="vs-wallboard-kpi__head">
                    <div class="vs-wallboard-kpi__label"><?php echo $h($t('Pending checklist')); ?></div>
                    <div class="vs-wallboard-kpi__icon"><i class="ti ti-checklist"></i></div>
                </div>
                <div class="vs-wallboard-kpi__value"><?php echo (int) $summary['checklist_pending']; ?></div>
            </div>
        </section>

        <section class="vs-wallboard-main">
            <div class="vs-card">
                <div class="vs-card-header">
                    <span class="vs-card-title"><i class="ti ti-chart-pie"></i> <?php echo $h($t('Reservations by status')); ?></span>
                </div>

                <div class="vs-wallboard-status-kpi-grid">
                    <div class="vs-wallboard-kpi vs-wallboard-kpi--primary">
                        <div class="vs-wallboard-kpi__head">
                            <div class="vs-wallboard-kpi__label"><?php echo $h($t('New')); ?></div>
                            <div class="vs-wallboard-kpi__icon"><i class="ti ti-file-plus"></i></div>
                        </div>
                        <div class="vs-wallboard-kpi__value"><?php echo (int) $reservations_chart['new']; ?></div>
                    </div>

                    <div class="vs-wallboard-kpi vs-wallboard-kpi--success">
                        <div class="vs-wallboard-kpi__head">
                            <div class="vs-wallboard-kpi__label"><?php echo $h($t('Approved')); ?></div>
                            <div class="vs-wallboard-kpi__icon"><i class="ti ti-circle-check"></i></div>
                        </div>
                        <div class="vs-wallboard-kpi__value"><?php echo (int) $reservations_chart['approved']; ?></div>
                    </div>

                    <div class="vs-wallboard-kpi vs-wallboard-kpi--danger">
                        <div class="vs-wallboard-kpi__head">
                            <div class="vs-wallboard-kpi__label"><?php echo $h($t('Rejected')); ?></div>
                            <div class="vs-wallboard-kpi__icon"><i class="ti ti-circle-x"></i></div>
                        </div>
                        <div class="vs-wallboard-kpi__value"><?php echo (int) $reservations_chart['rejected']; ?></div>
                    </div>

                    <div class="vs-wallboard-kpi vs-wallboard-kpi--neutral">
                        <div class="vs-wallboard-kpi__head">
                            <div class="vs-wallboard-kpi__label"><?php echo $h($t('Cancelled')); ?></div>
                            <div class="vs-wallboard-kpi__icon"><i class="ti ti-ban"></i></div>
                        </div>
                        <div class="vs-wallboard-kpi__value"><?php echo (int) $reservations_chart['cancelled']; ?></div>
                    </div>
                </div>
            </div>

            <div class="vs-wallboard-side">
                <div class="vs-card">
                    <div class="vs-card-header">
                        <span class="vs-card-title"><i class="ti ti-timeline-event"></i> <?php echo $h($t('Latest requests')); ?></span>
                    </div>

                    <?php if (empty($recent_requests)): ?>
                        <div class="vs-empty-state"><?php echo $h($t('No requests registered.')); ?></div>
                    <?php else: ?>
                        <table class="vs-table">
                            <thead>
                                <tr>
                                    <th><?php echo $h($t('Requester')); ?></th>
                                    <th><?php echo $h($t('Vehicle')); ?></th>
                                    <th><?php echo $h($t('Destination')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $request['requester_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $request['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $request['destination'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="vs-card">
                    <div class="vs-card-header">
                        <span class="vs-card-title"><i class="ti ti-trophy"></i> <?php echo $h($t('Top vehicles')); ?></span>
                    </div>

                    <?php if (empty($top_vehicles)): ?>
                        <div class="vs-empty-state"><?php echo $h($t('No vehicle usage data.')); ?></div>
                    <?php else: ?>
                        <table class="vs-table">
                            <thead>
                                <tr>
                                    <th><?php echo $h($t('Vehicle')); ?></th>
                                    <th><?php echo $h($t('Reservations')); ?></th>
                                    <th><?php echo $h($t('Last use')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_vehicles as $vehicle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $vehicle['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int) $vehicle['total_reservations']; ?></td>
                                        <td><?php echo !empty($vehicle['last_use']) ? Html::convDateTime((string) $vehicle['last_use']) : '—'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<?php
echo "<script src='" . htmlspecialchars($js_url, ENT_QUOTES, 'UTF-8') . "' defer></script>";

if ($standalone) {
    Html::nullFooter();
} else {
    Html::footer();
}
?>
