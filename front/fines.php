<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('MULTAS', 'EM OBRAS !!!');
exit;

global $DB;

Session::checkRight('plugin_vehiclescheduler', READ);

function vs_fines_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['quick_fine_action'])) {
    $fineId      = PluginVehicleschedulerInput::int($_POST, 'fine_id', 0, 1);
    $action      = PluginVehicleschedulerInput::enum($_POST, 'quick_fine_action', ['paid', 'cancel'], '');
    $statusMap   = [
        'paid'   => PluginVehicleschedulerDriverfine::STATUS_PAID,
        'cancel' => PluginVehicleschedulerDriverfine::STATUS_CANCELLED,
    ];

    if ($fineId > 0 && isset($statusMap[$action])) {
        $fine = new PluginVehicleschedulerDriverfine();
        if ($fine->getFromDB($fineId)) {
            $fine->update([
                'id'     => $fineId,
                'status' => $statusMap[$action],
            ]);
            Session::addMessageAfterRedirect('Multa atualizada com sucesso.', false, INFO);
        }
    }

    Html::redirect($_SERVER['PHP_SELF']);
}

Html::header('Multas de Trânsito', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'fines');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$openFines = iterator_to_array($DB->request([
    'SELECT'    => [
        'glpi_plugin_vehiclescheduler_driverfines.*',
        'glpi_plugin_vehiclescheduler_drivers.name AS driver_name',
        'glpi_plugin_vehiclescheduler_vehicles.name AS vehicle_name',
        'glpi_plugin_vehiclescheduler_vehicles.plate AS vehicle_plate',
    ],
    'FROM'      => 'glpi_plugin_vehiclescheduler_driverfines',
    'LEFT JOIN' => [
        'glpi_plugin_vehiclescheduler_drivers'  => [
            'FKEY' => [
                'glpi_plugin_vehiclescheduler_driverfines' => 'plugin_vehiclescheduler_drivers_id',
                'glpi_plugin_vehiclescheduler_drivers'     => 'id',
            ],
        ],
        'glpi_plugin_vehiclescheduler_vehicles' => [
            'FKEY' => [
                'glpi_plugin_vehiclescheduler_driverfines' => 'plugin_vehiclescheduler_vehicles_id',
                'glpi_plugin_vehiclescheduler_vehicles'    => 'id',
            ],
        ],
    ],
    'WHERE'     => [
        'glpi_plugin_vehiclescheduler_driverfines.status' => PluginVehicleschedulerDriverfine::STATUS_OPEN,
    ],
    'ORDER'     => ['glpi_plugin_vehiclescheduler_driverfines.fine_date DESC'],
]));

$pointsMap  = PluginVehicleschedulerDriverfine::getSeverityPoints();
$severities = PluginVehicleschedulerDriverfine::getAllSeverities();
$totalPoints = 0;

foreach ($openFines as $fine) {
    $totalPoints += $pointsMap[(int) $fine['severity']] ?? 0;
}

$riskTone = 'normal';
if ($totalPoints >= 20) {
    $riskTone = 'critical';
} elseif ($totalPoints >= 10) {
    $riskTone = 'attention';
}

$totalValue = count($openFines) * 195.23;
?>
<div class="vs-fines-page">
    <section class="vs-fines-hero">
        <div>
            <p class="vs-fines-hero__eyebrow">Operação diária</p>
            <h1>Gestão de multas de trânsito</h1>
            <p class="vs-fines-hero__subtitle">
                Veja o backlog de infrações em aberto, priorize o que precisa de ação e acompanhe o impacto na CNH.
            </p>
        </div>
        <div class="vs-fines-hero__actions">
            <a href="driver.php" class="vs-fines-hero__link">Ver motoristas</a>
        </div>
    </section>

    <section class="vs-fines-kpis">
        <article class="vs-fines-kpi">
            <span class="vs-fines-kpi__label">Multas abertas</span>
            <strong class="vs-fines-kpi__value"><?= count($openFines) ?></strong>
            <p class="vs-fines-kpi__hint">Itens que ainda exigem pagamento, recurso ou cancelamento.</p>
        </article>
        <article class="vs-fines-kpi vs-fines-kpi--<?= $riskTone ?>">
            <span class="vs-fines-kpi__label">Pontos em aberto</span>
            <strong class="vs-fines-kpi__value"><?= $totalPoints ?></strong>
            <p class="vs-fines-kpi__hint">Atenção maior quando a pontuação acumulada começa a crescer.</p>
        </article>
        <article class="vs-fines-kpi">
            <span class="vs-fines-kpi__label">Valor estimado</span>
            <strong class="vs-fines-kpi__value">R$ <?= number_format($totalValue, 2, ',', '.') ?></strong>
            <p class="vs-fines-kpi__hint">Estimativa rápida para leitura gerencial do passivo atual.</p>
        </article>
    </section>

    <section class="vs-fines-table-card">
        <div class="vs-fines-table-card__header">
            <div>
                <h2>Fila de multas em aberto</h2>
                <p>As ações rápidas ficam no fim da linha para reduzir navegação desnecessária.</p>
            </div>
        </div>

        <?php if (empty($openFines)): ?>
            <div class="vs-fines-empty">
                <div class="vs-fines-empty__icon">✅</div>
                <h3>Nenhuma multa em aberto</h3>
                <p>A operação está limpa neste momento.</p>
            </div>
        <?php else: ?>
            <div class="vs-fines-table-wrap">
                <table class="vs-fines-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Motorista</th>
                            <th>Veículo</th>
                            <th>Gravidade</th>
                            <th>Pontos</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($openFines as $fine): ?>
                            <?php
                            $points = $pointsMap[(int) $fine['severity']] ?? 0;
                            $vehicleDisplay = '—';
                            if (!empty($fine['vehicle_name'])) {
                                $vehicleDisplay = vs_fines_escape(
                                    (string) $fine['vehicle_name'] . ' (' . (string) $fine['vehicle_plate'] . ')'
                                );
                            }

                            $description = PluginVehicleschedulerInput::text(
                                ['description' => $fine['description'] ?? ''],
                                'description',
                                120,
                                ''
                            );
                            ?>
                            <tr>
                                <td><?= Html::convDate($fine['fine_date']) ?></td>
                                <td>
                                    <a href="driver.form.php?id=<?= (int) $fine['plugin_vehiclescheduler_drivers_id'] ?>" class="vs-fines-driver-link">
                                        <?= vs_fines_escape((string) $fine['driver_name']) ?>
                                    </a>
                                </td>
                                <td><?= $vehicleDisplay ?></td>
                                <td><?= vs_fines_escape((string) ($severities[(int) $fine['severity']] ?? '?')) ?></td>
                                <td><span class="vs-fines-points"><?= $points ?></span></td>
                                <td><?= vs_fines_escape($description) ?></td>
                                <td><span class="vs-fines-badge">Em aberto</span></td>
                                <td>
                                    <form method="post" class="vs-fines-actions">
                                        <input type="hidden" name="fine_id" value="<?= (int) $fine['id'] ?>">
                                        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                                        <button type="submit" name="quick_fine_action" value="paid" class="vs-fines-action vs-fines-action--success">
                                            Pagar
                                        </button>
                                        <button
                                            type="submit"
                                            name="quick_fine_action"
                                            value="cancel"
                                            class="vs-fines-action vs-fines-action--neutral"
                                            data-confirm-message="Cancelar esta multa?">
                                            Cancelar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php Html::footer(); ?>