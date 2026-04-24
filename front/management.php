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

include_once __DIR__ . '/../inc/common.inc.php';
include_once __DIR__ . '/../inc/dashboard.class.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

$root_doc = plugin_vehiclescheduler_get_root_doc();

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
];

$request_method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($request_method === 'POST') {
    $action = trim((string) (filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?? ''));
    $schedule_id = filter_input(
        INPUT_POST,
        'schedule_id',
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
            ],
        ]
    );

    try {
        switch ($action) {
            case 'approve_schedule':
                Session::checkRight('plugin_vehiclescheduler_approve', READ);

                if ($schedule_id === false || $schedule_id === null) {
                    throw new RuntimeException('Reserva inválida para aprovação.');
                }

                PluginVehicleschedulerDashboard::approveSchedule((int) $schedule_id);
                Session::addMessageAfterRedirect('Reserva aprovada com sucesso.', false, INFO);
                break;

            case 'reject_schedule':
                Session::checkRight('plugin_vehiclescheduler_approve', READ);

                if ($schedule_id === false || $schedule_id === null) {
                    throw new RuntimeException('Reserva inválida para recusa.');
                }

                PluginVehicleschedulerDashboard::rejectSchedule((int) $schedule_id);
                Session::addMessageAfterRedirect('Reserva recusada com sucesso.', false, INFO);
                break;

            default:
                Session::addMessageAfterRedirect('Ação inválida.', true, ERROR);
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
            'Não foi possível executar a ação solicitada.',
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

Html::header(
    'Gestão de Frota',
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

        <section class="vs-dashboard-hero">
            <fieldset class="vs-mgmt-toolbar">
                <legend>
                    <span class="vs-mgmt-toolbar__legend">
                        <i class="ti ti-steering-wheel"></i>
                        <span>Central Operacional da Frota</span>
                    </span>
                </legend>

                <div class="vs-mgmt-toolbar__row">
                    <div class="vs-dashboard-actions">
                        <a href="<?php echo $h($urls['wallboard']); ?>" target="_blank" rel="noopener" class="vs-action-btn">
                            <i class="ti ti-screen-share"></i>
                            <span>Abrir telão</span>
                        </a>

                        <a href="<?php echo $h($urls['schedule']); ?>" class="vs-action-btn">
                            <i class="ti ti-calendar-event"></i>
                            <span>Reservas</span>
                        </a>
                    </div>

                    <div class="vs-visual-controls">
                        <div class="vs-theme-toggle" title="Alternar tema">
                            <input type="checkbox" id="vsMgmtThemeToggle" aria-label="Alternar tema">
                            <label for="vsMgmtThemeToggle">
                                <svg class="sun" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M6.76 4.84l-1.8-1.79-1.41 1.41 1.79 1.8 1.42-1.42zM1 11h3v2H1v-2zm10-10h2v3h-2V1zm9.66 3.46l-1.41-1.41-1.8 1.79 1.42 1.42 1.79-1.8zM20 11h3v2h-3v-2zM11 20h2v3h-2v-3zm7.24-1.84l1.8 1.79 1.41-1.41-1.79-1.8-1.42 1.42zM4.34 19.54l1.41 1.41 1.8-1.79-1.42-1.42-1.79 1.8zM12 6a6 6 0 100 12 6 6 0 000-12z" />
                                </svg>
                                <svg class="moon" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M21 14.53A8.5 8.5 0 0110.47 3 6.5 6.5 0 1019 16.5c.71 0 1.4-.12 2.06-.33-.03-.55-.04-1.09-.06-1.64z" />
                                </svg>
                            </label>
                        </div>

                        <div class="vs-control-group" aria-label="Controles de fonte">
                            <button type="button" class="vs-control-btn" id="vsFontDecrease" title="Diminuir fonte" aria-label="Diminuir fonte">
                                <i class="ti ti-minus"></i>
                            </button>
                            <button type="button" class="vs-control-btn" id="vsFontReset" title="Resetar fonte" aria-label="Resetar fonte">
                                <i class="ti ti-refresh"></i>
                            </button>
                            <button type="button" class="vs-control-btn" id="vsFontIncrease" title="Aumentar fonte" aria-label="Aumentar fonte">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>

                        <button type="button" class="vs-control-btn vs-control-btn--label" id="vsVisualReset" title="Resetar visual" aria-label="Resetar visual">
                            Reset visual
                        </button>
                    </div>
                </div>
            </fieldset>
        </section>

        <section class="vs-ops-grid">
            <a href="<?php echo $h($urls['schedule'] . '?status=1'); ?>" class="vs-kpi-card vs-ops-card">
                <div class="vs-kpi-value"><?php echo (int) ($kpi['reservations_new'] ?? 0); ?></div>
                <div class="vs-kpi-label">Reservas pendentes</div>
            </a>

            <div class="vs-kpi-card vs-kpi-card--static vs-ops-card">
                <div class="vs-kpi-value"><?php echo count($cnh_alerts); ?></div>
                <div class="vs-kpi-label">Alertas CNH</div>
            </div>

            <a href="<?php echo $h($urls['incident']); ?>" class="vs-kpi-card vs-ops-card">
                <div class="vs-kpi-value"><?php echo (int) ($kpi['incidents_open'] ?? 0); ?></div>
                <div class="vs-kpi-label">Incidentes abertos</div>
            </a>

            <a href="<?php echo $h($urls['maintenance']); ?>" class="vs-kpi-card vs-ops-card">
                <div class="vs-kpi-value"><?php echo (int) ($kpi['maintenances_active'] ?? 0); ?></div>
                <div class="vs-kpi-label">Manutenções ativas</div>
            </a>

            <?php if ($checklists_enabled): ?>
                <a href="<?php echo $h($urls['schedule'] . '?checklist_pending=1'); ?>" class="vs-kpi-card vs-ops-card">
                    <div class="vs-kpi-value"><?php echo (int) ($kpi['checklist_pending'] ?? 0); ?></div>
                    <div class="vs-kpi-label">Checklists pendentes</div>
                </a>
            <?php else: ?>
                <div class="vs-kpi-card vs-kpi-card--static vs-ops-card">
                    <div class="vs-kpi-value">—</div>
                    <div class="vs-kpi-label">Checklists</div>
                </div>
            <?php endif; ?>

            <a href="<?php echo $h($urls['vehicle']); ?>" class="vs-kpi-card vs-ops-card">
                <div class="vs-kpi-value">
                    <?php echo (int) ($kpi['vehicles_active'] ?? 0); ?>/<?php echo (int) ($kpi['vehicles_total'] ?? 0); ?>
                </div>
                <div class="vs-kpi-label">Viaturas ativas</div>
            </a>
        </section>

        <section class="vs-link-grid">
            <a href="<?php echo $h($urls['vehicle']); ?>" class="vs-link-card">
                <div class="vs-link-card__head">
                    <div class="vs-link-card__icon"><i class="ti ti-car"></i></div>
                    <div class="vs-link-card__title">Veículos</div>
                </div>
                <p class="vs-link-card__desc">Cadastro, consulta e controle operacional das viaturas.</p>
            </a>

            <a href="<?php echo $h($urls['driver']); ?>" class="vs-link-card">
                <div class="vs-link-card__head">
                    <div class="vs-link-card__icon"><i class="ti ti-steering-wheel"></i></div>
                    <div class="vs-link-card__title">Motoristas</div>
                </div>
                <p class="vs-link-card__desc">Cadastro e acompanhamento de CNH.</p>
            </a>

            <a href="<?php echo $h($urls['schedule']); ?>" class="vs-link-card">
                <div class="vs-link-card__head">
                    <div class="vs-link-card__icon"><i class="ti ti-calendar-event"></i></div>
                    <div class="vs-link-card__title">Reservas</div>
                </div>
                <p class="vs-link-card__desc">Gestão das solicitações, períodos e aprovações.</p>
            </a>

            <a href="<?php echo $h($urls['incident']); ?>" class="vs-link-card">
                <div class="vs-link-card__head">
                    <div class="vs-link-card__icon"><i class="ti ti-alert-triangle"></i></div>
                    <div class="vs-link-card__title">Incidentes</div>
                </div>
                <p class="vs-link-card__desc">Registro e acompanhamento de ocorrências.</p>
            </a>

            <a href="<?php echo $h($urls['maintenance']); ?>" class="vs-link-card">
                <div class="vs-link-card__head">
                    <div class="vs-link-card__icon"><i class="ti ti-tool"></i></div>
                    <div class="vs-link-card__title">Manutenções</div>
                </div>
                <p class="vs-link-card__desc">Preventivas, corretivas e custos associados.</p>
            </a>

            <a href="<?php echo $h($urls['checklist']); ?>" class="vs-link-card">
                <div class="vs-link-card__head">
                    <div class="vs-link-card__icon"><i class="ti ti-checklist"></i></div>
                    <div class="vs-link-card__title">Checklist</div>
                </div>
                <p class="vs-link-card__desc">Templates e acompanhamento dos checklists operacionais.</p>
            </a>
        </section>

        <section class="vs-main-grid">
            <div class="vs-card">
                <div class="vs-card-header">
                    <span class="vs-card-title"><i class="ti ti-clock-check"></i> Reservas pendentes</span>
                    <a href="<?php echo $h($urls['schedule'] . '?status=1'); ?>" class="vs-card-link">Abrir reservas</a>
                </div>

                <?php if (empty($pending_reservations)): ?>
                    <div class="vs-empty-state">Nenhuma reserva aguardando análise.</div>
                <?php else: ?>
                    <table class="vs-table">
                        <thead>
                            <tr>
                                <th>Solicitante</th>
                                <th>Veículo</th>
                                <th>Período</th>
                                <th>Destino</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo $h($reservation['requester_name'] ?? ''); ?></td>
                                    <td><?php echo $h($reservation['vehicle_name'] ?? ''); ?></td>
                                    <td class="vs-nowrap">
                                        <?php echo Html::convDate(substr((string) ($reservation['begin_date'] ?? ''), 0, 10)); ?>
                                        →
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
                                                    <button type="submit" class="vs-btn-sm vs-btn-sm--success">Aprovar</button>
                                                </form>

                                                <form method="post">
                                                    <input type="hidden" name="action" value="reject_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo (int) ($reservation['id'] ?? 0); ?>">
                                                    <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
                                                    <button type="submit" class="vs-btn-sm vs-btn-sm--danger">Recusar</button>
                                                </form>

                                                <a href="<?php echo $h($urls['schedule_form'] . '?id=' . (int) ($reservation['id'] ?? 0)); ?>" class="vs-icon-link" title="Ver reserva">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <a href="<?php echo $h($urls['schedule_form'] . '?id=' . (int) ($reservation['id'] ?? 0)); ?>" class="vs-btn-sm vs-btn-sm--neutral">
                                                Ver
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
                    <span class="vs-card-title"><i class="ti ti-id-badge"></i> Alertas de CNH</span>
                    <a href="<?php echo $h($urls['driver']); ?>" class="vs-card-link">Ver todos</a>
                </div>

                <?php if (empty($cnh_alerts)): ?>
                    <div class="vs-empty-state">Nenhuma CNH vencendo nos próximos 90 dias.</div>
                <?php else: ?>
                    <table class="vs-table">
                        <thead>
                            <tr>
                                <th>Motorista</th>
                                <th>Categoria</th>
                                <th>Vencimento</th>
                                <th>Status</th>
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
                                            <?php echo (int) ($driver['days_to_expiry'] ?? 0); ?> dias
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
                    <span class="vs-card-title"><i class="ti ti-alert-triangle"></i> Incidentes recentes</span>
                    <a href="<?php echo $h($urls['incident']); ?>" class="vs-card-link">Ver todos</a>
                </div>

                <?php if (empty($recent_incidents)): ?>
                    <div class="vs-empty-state">Nenhum incidente registrado.</div>
                <?php else: ?>
                    <table class="vs-table">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Data</th>
                                <th>Tipo</th>
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
</div>
<?php
echo "<script src='" . $h($js_url) . "' defer></script>";
Html::footer();
