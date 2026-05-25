<?php

include_once __DIR__ . '/../src/Bootstrap/common.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

/**
 * Management reports landing page.
 */

include_once(__DIR__ . '/../src/Bootstrap/common.php');

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

$t = static fn(string $text): string => __($text, 'vehiclescheduler');

$report = PluginVehicleschedulerInput::enum(
    $_GET,
    'report',
    ['reservas', 'manutencoes', 'incidentes', 'utilizacao', 'motoristas', 'financeiro'],
    ''
);
$export = PluginVehicleschedulerInput::enum($_GET, 'export', ['pdf', 'xlsx'], '');

if ($report !== '' && $export !== '') {
    Html::redirect('reports_' . $export . '.php?report=' . urlencode($report));
}

Html::header($t('Management reports'), $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'reports');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$reports = [
    [
        'code'        => 'reservas',
        'icon'        => 'ti ti-calendar-stats',
        'title'       => $t('Reservations by period'),
        'description' => $t('Complete reservation analysis with requester, vehicle, driver, usage period, and approval status.'),
        'meta'        => [$t('Reservations + Vehicles + Drivers'), $t('Approval chart')],
    ],
    [
        'code'        => 'manutencoes',
        'icon'        => 'ti ti-tool',
        'title'       => $t('Maintenances and costs'),
        'description' => $t('Preventive and corrective maintenance history with costs, suppliers, and vehicle expense reading.'),
        'meta'        => [$t('Maintenances + Costs'), $t('Preventive vs corrective')],
    ],
    [
        'code'        => 'incidentes',
        'icon'        => 'ti ti-alert-triangle',
        'title'       => $t('Incidents and claims'),
        'description' => $t('Record of incidents, accidents, and claims with insurer, including approved amounts and coverage.'),
        'meta'        => [$t('Incidents + Claims'), $t('Insurance analysis')],
    ],
    [
        'code'        => 'utilizacao',
        'icon'        => 'ti ti-car',
        'title'       => $t('Fleet utilization'),
        'description' => $t('Vehicle usage rate, mileage, service time, and idle capacity identification.'),
        'meta'        => [$t('Vehicles + Reservations'), $t('Utilization rate')],
    ],
    [
        'code'        => 'motoristas',
        'icon'        => 'ti ti-id-badge',
        'title'       => $t('Drivers and licenses'),
        'description' => $t('Driver license status, upcoming expirations, associated fines, and driver usage history.'),
        'meta'        => [$t('Drivers + Fines'), $t('Expiration alerts')],
    ],
    [
        'code'        => 'financeiro',
        'icon'        => 'ti ti-report-money',
        'title'       => $t('Financial consolidated'),
        'description' => $t('Financial summary with maintenance, fines, claims, and monthly expense analysis by category.'),
        'meta'        => [$t('All sources'), $t('Monthly analysis')],
    ],
];
?>
<div class="vs-reports-page">
    <a href="management.php" class="vs-reports-back"><?= htmlspecialchars($t('Back to dashboard'), ENT_QUOTES, 'UTF-8') ?></a>

    <section class="vs-reports-hero">
        <div>
            <p class="vs-reports-hero__eyebrow"><?= htmlspecialchars($t('Management view'), ENT_QUOTES, 'UTF-8') ?></p>
            <h1><?= htmlspecialchars($t('Management reports'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p>
                <?= htmlspecialchars($t('Choose the desired view to see it online or export it as PDF and Excel without leaving the management flow.'), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <div class="vs-reports-hero__note">
            <strong><?= htmlspecialchars($t('6 reports ready'), ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars($t('Focused on quick reading and operational export.'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </section>

    <section class="vs-reports-grid">
        <?php foreach ($reports as $reportItem): ?>
            <article class="vs-report-card">
                <header class="vs-report-card__header">
                    <div class="vs-report-card__icon">
                        <i class="<?= htmlspecialchars($reportItem['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div>
                        <h2><?= htmlspecialchars($reportItem['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars($reportItem['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </header>

                <div class="vs-report-card__meta">
                    <?php foreach ($reportItem['meta'] as $meta): ?>
                        <span><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>

                <footer class="vs-report-card__actions">
                    <a href="reports_view.php?report=<?= urlencode($reportItem['code']) ?>" class="vs-report-btn vs-report-btn--primary">
                        <?= htmlspecialchars($t('View'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="?report=<?= urlencode($reportItem['code']) ?>&export=pdf" class="vs-report-btn">
                        PDF
                    </a>
                    <a href="?report=<?= urlencode($reportItem['code']) ?>&export=xlsx" class="vs-report-btn">
                        Excel
                    </a>
                </footer>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<?php Html::footer(); ?>
