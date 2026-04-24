<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('MULTAS', 'EM OBRAS !!!');
exit;

/**
 * Driver fine form controller.
 */

include_once(__DIR__ . '/../inc/common.inc.php');

function vs_driverfine_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_driverfine_redirect(int $driverId, int $fineId = 0): void
{
    if ($driverId > 0) {
        $rootDoc = plugin_vehiclescheduler_get_root_doc();
        Html::redirect(plugin_vehiclescheduler_get_front_url('driver.form.php') . '?id=' . $driverId . '&forcetab=PluginVehicleschedulerDriverfine$1');
    }

    if ($fineId > 0) {
        Html::redirect($_SERVER['PHP_SELF'] . '?id=' . $fineId);
    }

    $rootDoc = plugin_vehiclescheduler_get_root_doc();
    Html::redirect($rootDoc . '/front/central.php');
}

Session::checkRight('plugin_vehiclescheduler', READ);

$fine = new PluginVehicleschedulerDriverfine();

if (isset($_POST['add']) || isset($_POST['update'])) {
    Session::checkRight('plugin_vehiclescheduler', UPDATE);

    $input = [
        'id'                                => PluginVehicleschedulerInput::int($_POST, 'id', 0, 0),
        'plugin_vehiclescheduler_drivers_id' => PluginVehicleschedulerInput::int($_POST, 'plugin_vehiclescheduler_drivers_id', 0, 1),
        'plugin_vehiclescheduler_vehicles_id' => PluginVehicleschedulerInput::int($_POST, 'plugin_vehiclescheduler_vehicles_id', 0, 0),
        'fine_date'                         => PluginVehicleschedulerInput::date($_POST, 'fine_date'),
        'severity'                          => PluginVehicleschedulerInput::int(
            $_POST,
            'severity',
            PluginVehicleschedulerDriverfine::SEVERITY_SEVERE,
            PluginVehicleschedulerDriverfine::SEVERITY_MILD,
            PluginVehicleschedulerDriverfine::SEVERITY_VERYSEVERE
        ),
        'status'                            => PluginVehicleschedulerInput::int(
            $_POST,
            'status',
            PluginVehicleschedulerDriverfine::STATUS_OPEN,
            PluginVehicleschedulerDriverfine::STATUS_OPEN,
            PluginVehicleschedulerDriverfine::STATUS_CANCELLED
        ),
        'description'                       => PluginVehicleschedulerInput::text($_POST, 'description', 65535, ''),
    ];

    if (isset($_POST['add'])) {
        $newId = $fine->add($input);
        if ($newId) {
            Session::addMessageAfterRedirect('Infração registrada com sucesso.', false, INFO);
            vs_driverfine_redirect($input['plugin_vehiclescheduler_drivers_id'], (int) $newId);
        }
    } else {
        $fine->update($input);
        Session::addMessageAfterRedirect('Infração atualizada com sucesso.', false, INFO);
        vs_driverfine_redirect($input['plugin_vehiclescheduler_drivers_id'], $input['id']);
    }

    Html::back();
}

if (isset($_POST['delete'])) {
    Session::checkRight('plugin_vehiclescheduler', UPDATE);

    $fineId   = PluginVehicleschedulerInput::int($_POST, 'id', 0, 1);
    $driverId = PluginVehicleschedulerInput::int($_POST, 'plugin_vehiclescheduler_drivers_id', 0, 0);

    if ($fineId > 0 && $fine->getFromDB($fineId)) {
        $driverId = (int) ($fine->fields['plugin_vehiclescheduler_drivers_id'] ?? $driverId);
        $fine->delete(['id' => $fineId], true);
        Session::addMessageAfterRedirect('Infração excluída com sucesso.', false, INFO);
    }

    vs_driverfine_redirect($driverId, $fineId);
}

$fineId   = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$driverId = PluginVehicleschedulerInput::int($_GET, 'plugin_vehiclescheduler_drivers_id', 0, 0);

if ($fineId > 0) {
    if (!$fine->getFromDB($fineId)) {
        Session::addMessageAfterRedirect('Infração não encontrada.', false, ERROR);
        vs_driverfine_redirect($driverId, 0);
    }
    $driverId = (int) $fine->fields['plugin_vehiclescheduler_drivers_id'];
}

$driverName = '';
if ($driverId > 0) {
    $driver = new PluginVehicleschedulerDriver();
    if ($driver->getFromDB($driverId)) {
        $driverName = (string) ($driver->fields['name'] ?? '');
    }
}

Html::header('Infração de Trânsito', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'fines');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$values = [
    'id'                                 => $fineId,
    'plugin_vehiclescheduler_drivers_id' => $driverId,
    'plugin_vehiclescheduler_vehicles_id' => (int) ($fine->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
    'fine_date'                          => (string) ($fine->fields['fine_date'] ?? date('Y-m-d')),
    'severity'                           => (int) ($fine->fields['severity'] ?? PluginVehicleschedulerDriverfine::SEVERITY_SEVERE),
    'status'                             => (int) ($fine->fields['status'] ?? PluginVehicleschedulerDriverfine::STATUS_OPEN),
    'description'                        => (string) ($fine->fields['description'] ?? ''),
];
?>
<div class="vs-driverfine-page">
    <section class="vs-driverfine-card">
        <header class="vs-driverfine-card__header">
            <div>
                <p class="vs-driverfine-card__eyebrow">Registro operacional</p>
                <h1><?= $fineId > 0 ? 'Editar infração' : 'Nova infração' ?></h1>
                <p>
                    <?= $driverName !== '' ? 'Motorista: ' . vs_driverfine_escape($driverName) : 'Preencha os dados da infração.' ?>
                </p>
            </div>
            <?php if ($driverId > 0): ?>
                <a href="driver.form.php?id=<?= $driverId ?>&forcetab=PluginVehicleschedulerDriverfine$1" class="vs-driverfine-back">
                    Voltar ao motorista
                </a>
            <?php endif; ?>
        </header>

        <form method="post" class="vs-driverfine-form">
            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= $values['id'] ?>">
            <input type="hidden" name="plugin_vehiclescheduler_drivers_id" value="<?= $values['plugin_vehiclescheduler_drivers_id'] ?>">

            <div class="vs-driverfine-grid">
                <div>
                    <label class="vs-driverfine-label">Data</label>
                    <?php Html::showDateField('fine_date', ['value' => $values['fine_date']]); ?>
                </div>
                <div>
                    <label class="vs-driverfine-label">Gravidade</label>
                    <?php Dropdown::showFromArray('severity', PluginVehicleschedulerDriverfine::getAllSeverities(), ['value' => $values['severity']]); ?>
                </div>
                <div>
                    <label class="vs-driverfine-label">Veículo</label>
                    <?php PluginVehicleschedulerVehicle::dropdown([
                        'name'   => 'plugin_vehiclescheduler_vehicles_id',
                        'value'  => $values['plugin_vehiclescheduler_vehicles_id'],
                        'entity' => $_SESSION['glpiactive_entity'] ?? 0,
                    ]); ?>
                </div>
                <div>
                    <label class="vs-driverfine-label">Status</label>
                    <?php Dropdown::showFromArray('status', PluginVehicleschedulerDriverfine::getAllStatus(), ['value' => $values['status']]); ?>
                </div>
                <div class="vs-driverfine-grid__full">
                    <label class="vs-driverfine-label">Descrição</label>
                    <textarea
                        name="description"
                        rows="4"
                        class="vs-driverfine-textarea"
                        placeholder="Descreva a infração sem incluir dados pessoais desnecessários."><?= vs_driverfine_escape($values['description']) ?></textarea>
                </div>
            </div>

            <footer class="vs-driverfine-actions">
                <?php if ($fineId > 0): ?>
                    <button
                        type="submit"
                        name="delete"
                        class="vs-driverfine-btn vs-driverfine-btn--danger"
                        data-confirm-message="Excluir esta infração?">
                        Excluir
                    </button>
                <?php endif; ?>
                <div class="vs-driverfine-actions__primary">
                    <button type="submit" name="<?= $fineId > 0 ? 'update' : 'add' ?>" class="vs-driverfine-btn">
                        <?= $fineId > 0 ? 'Salvar alterações' : 'Registrar infração' ?>
                    </button>
                </div>
            </footer>
        </form>
    </section>
</div>
<?php Html::footer(); ?>