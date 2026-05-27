<?php

/**
 * Driver fine form controller.
 */

include_once __DIR__ . '/../src/Bootstrap/common.php';

PluginVehicleschedulerDriverfine::requireAdminFines();

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$t = static fn(string $text): string => __($text, 'vehiclescheduler');

$formAction = plugin_vehiclescheduler_get_front_url('driverfine.form.php');
$listUrl = plugin_vehiclescheduler_get_front_url('fines.php');

$fine = new PluginVehicleschedulerDriverfine();

$redirectAfterSave = static function (int $driverId = 0, int $fineId = 0) use ($listUrl): void {
    if ($driverId > 0) {
        Html::redirect(plugin_vehiclescheduler_get_front_url('driver.form.php') . '?id=' . $driverId . '&forcetab=PluginVehicleschedulerDriverfine$1');
    }

    if ($fineId > 0) {
        Html::redirect(plugin_vehiclescheduler_get_front_url('driverfine.form.php') . '?id=' . $fineId);
    }

    Html::redirect($listUrl);
};

if (isset($_POST['add']) || isset($_POST['update'])) {
    PluginVehicleschedulerDriverfine::requireAdminFines();

    $input = [
        'id'                                  => PluginVehicleschedulerInput::int($_POST, 'id', 0, 0),
        'plugin_vehiclescheduler_drivers_id'  => PluginVehicleschedulerInput::int($_POST, 'plugin_vehiclescheduler_drivers_id', 0, 1),
        'plugin_vehiclescheduler_vehicles_id' => PluginVehicleschedulerInput::int($_POST, 'plugin_vehiclescheduler_vehicles_id', 0, 0),
        'fine_date'                           => PluginVehicleschedulerInput::date($_POST, 'fine_date'),
        'violation_code'                      => PluginVehicleschedulerInput::text($_POST, 'violation_code', 20, ''),
        'violation_split'                     => PluginVehicleschedulerInput::text($_POST, 'violation_split', 20, ''),
        'legal_basis'                         => PluginVehicleschedulerInput::text($_POST, 'legal_basis', 255, ''),
        'offender'                            => PluginVehicleschedulerInput::text($_POST, 'offender', 100, ''),
        'authority'                           => PluginVehicleschedulerInput::text($_POST, 'authority', 100, ''),
        'severity'                            => PluginVehicleschedulerInput::int(
            $_POST,
            'severity',
            PluginVehicleschedulerDriverfine::SEVERITY_SEVERE,
            PluginVehicleschedulerDriverfine::SEVERITY_NONE,
            PluginVehicleschedulerDriverfine::SEVERITY_VERYSEVERE
        ),
        'status'                              => PluginVehicleschedulerInput::int(
            $_POST,
            'status',
            PluginVehicleschedulerDriverfine::STATUS_OPEN,
            PluginVehicleschedulerDriverfine::STATUS_OPEN,
            PluginVehicleschedulerDriverfine::STATUS_CANCELLED
        ),
        'description'                         => PluginVehicleschedulerInput::text($_POST, 'description', 65535, ''),
    ];

    if (isset($_POST['add'])) {
        $newId = $fine->add($input);
        if ($newId) {
            Session::addMessageAfterRedirect($t('Fine registered successfully.'), false, INFO);
            $redirectAfterSave($input['plugin_vehiclescheduler_drivers_id'], (int) $newId);
        }
    } else {
        $fine->update($input);
        Session::addMessageAfterRedirect($t('Fine updated successfully.'), false, INFO);
        $redirectAfterSave($input['plugin_vehiclescheduler_drivers_id'], $input['id']);
    }

    Html::back();
}

if (isset($_POST['delete'])) {
    PluginVehicleschedulerDriverfine::requireAdminFines();

    $fineId = PluginVehicleschedulerInput::int($_POST, 'id', 0, 1);
    $driverId = PluginVehicleschedulerInput::int($_POST, 'plugin_vehiclescheduler_drivers_id', 0, 0);

    if ($fineId > 0 && $fine->getFromDB($fineId)) {
        $driverId = (int) ($fine->fields['plugin_vehiclescheduler_drivers_id'] ?? $driverId);
        $fine->delete(['id' => $fineId], true);
        Session::addMessageAfterRedirect($t('Fine deleted successfully.'), false, INFO);
    }

    $redirectAfterSave($driverId, 0);
}

$fineId = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$driverId = PluginVehicleschedulerInput::int($_GET, 'plugin_vehiclescheduler_drivers_id', 0, 0);

if ($fineId > 0) {
    if (!$fine->getFromDB($fineId)) {
        Session::addMessageAfterRedirect($t('Fine not found.'), false, ERROR);
        Html::redirect($listUrl);
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

$values = [
    'id'                                  => $fineId,
    'plugin_vehiclescheduler_drivers_id'  => $driverId,
    'plugin_vehiclescheduler_vehicles_id' => (int) ($fine->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
    'fine_date'                           => (string) ($fine->fields['fine_date'] ?? date('Y-m-d')),
    'violation_code'                      => (string) ($fine->fields['violation_code'] ?? ''),
    'violation_split'                     => (string) ($fine->fields['violation_split'] ?? ''),
    'legal_basis'                         => (string) ($fine->fields['legal_basis'] ?? ''),
    'offender'                            => (string) ($fine->fields['offender'] ?? ''),
    'authority'                           => (string) ($fine->fields['authority'] ?? ''),
    'severity'                            => (int) ($fine->fields['severity'] ?? PluginVehicleschedulerDriverfine::SEVERITY_SEVERE),
    'status'                              => (int) ($fine->fields['status'] ?? PluginVehicleschedulerDriverfine::STATUS_OPEN),
    'description'                         => (string) ($fine->fields['description'] ?? ''),
];

$renainfCatalogUrl = plugin_vehiclescheduler_get_public_asset_url('data/renainf-infractions.json');

plugin_vehiclescheduler_apply_configured_locale();

Html::header($t('Traffic fine'), $formAction, 'tools', PluginVehicleschedulerMenu::class, 'management');

plugin_vehiclescheduler_apply_configured_locale();

plugin_vehiclescheduler_load_css([
    'css/pages/driverfine-form.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_load_script('js/driverfine-form.js');
plugin_vehiclescheduler_render_back_to_management();

?>
<div class="vs-driverfine-page">
    <div class="vs-page-header">
        <div class="vs-header-content">
            <div class="vs-header-title">
                <div class="vs-header-icon-wrapper">
                    <i class="ti ti-file-alert vs-header-icon"></i>
                </div>
                <div>
                    <h2><?= $h($fineId > 0 ? $t('Edit fine') : $t('New fine')) ?></h2>
                    <p class="vs-page-subtitle">
                        <?= $driverName !== '' ? $h(sprintf($t('Driver: %s'), $driverName)) : $h($t('Inform driver, vehicle, date, and RENAINF infraction.')) ?>
                    </p>
                </div>
            </div>

            <a href="<?= $h($listUrl) ?>" class="vs-btn-add vs-btn-secondary">
                <i class="ti ti-list-details"></i>
                <span><?= $h($t('List')) ?></span>
            </a>
        </div>
    </div>

    <section class="vs-driverfine-card">
        <form
            method="post"
            action="<?= $h($formAction) ?>"
            class="vs-driverfine-form"
            data-vs-driverfine-form
            data-renainf-catalog-url="<?= $h($renainfCatalogUrl) ?>"
            data-no-infraction-selected="<?= $h($t('No infraction selected.')) ?>"
            data-no-legal-basis="<?= $h($t('No legal basis informed')) ?>"
            data-no-legal-basis-short="<?= $h($t('No legal basis')) ?>"
            data-no-severity="<?= $h($t('No severity')) ?>"
            data-no-offender="<?= $h($t('Offender not informed')) ?>"
            data-no-infraction-found="<?= $h($t('No infraction found.')) ?>"
            data-select-renainf-infraction="<?= $h($t('Select a RENAINF infraction.')) ?>"
            data-renainf-load-error="<?= $h($t('Unable to load RENAINF table.')) ?>"
            data-defined-by-infraction="<?= $h($t('Defined by infraction')) ?>"
            data-severity-labels="<?= $h(json_encode(PluginVehicleschedulerDriverfine::getAllSeverities(), JSON_UNESCAPED_UNICODE)) ?>">
            <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
            <input type="hidden" name="id" value="<?= (int) $values['id'] ?>">
            <input type="hidden" name="violation_code" value="<?= $h($values['violation_code']) ?>" data-renainf-code>
            <input type="hidden" name="violation_split" value="<?= $h($values['violation_split']) ?>" data-renainf-split>
            <input type="hidden" name="legal_basis" value="<?= $h($values['legal_basis']) ?>" data-renainf-legal>
            <input type="hidden" name="offender" value="<?= $h($values['offender']) ?>" data-renainf-offender>
            <input type="hidden" name="authority" value="<?= $h($values['authority']) ?>" data-renainf-authority>
            <input type="hidden" name="severity" value="<?= (int) $values['severity'] ?>" data-renainf-severity>

            <div class="vs-driverfine-grid">
                <div class="vs-driverfine-field">
                    <label class="vs-driverfine-label"><?= $h($t('Driver')) ?> *</label>
                    <?php
                    PluginVehicleschedulerDriver::dropdown([
                        'name'                => 'plugin_vehiclescheduler_drivers_id',
                        'value'               => (int) $values['plugin_vehiclescheduler_drivers_id'],
                        'display_emptychoice' => true,
                        'entity'              => $_SESSION['glpiactive_entity'] ?? 0,
                        'width'               => '100%',
                    ]);
                    ?>
                </div>

                <div class="vs-driverfine-field">
                    <label class="vs-driverfine-label"><?= $h($t('Vehicle')) ?></label>
                    <?php
                    PluginVehicleschedulerVehicle::dropdown([
                        'name'   => 'plugin_vehiclescheduler_vehicles_id',
                        'value'  => (int) $values['plugin_vehiclescheduler_vehicles_id'],
                        'entity' => $_SESSION['glpiactive_entity'] ?? 0,
                        'width'  => '100%',
                    ]);
                    ?>
                </div>

                <div class="vs-driverfine-field">
                    <label class="vs-driverfine-label"><?= $h($t('Date')) ?> *</label>
                    <?php Html::showDateField('fine_date', ['value' => $values['fine_date']]); ?>
                </div>

                <div class="vs-driverfine-field vs-driverfine-grid__full">
                    <label class="vs-driverfine-label" for="vs-renainf-search"><?= $h($t('RENAINF infraction')) ?></label>
                    <div class="vs-renainf-picker" data-renainf-picker>
                        <div class="vs-renainf-search-row">
                            <i class="ti ti-search"></i>
                            <input
                                id="vs-renainf-search"
                                type="search"
                                class="vs-renainf-search"
                                placeholder="<?= $h($t('Search by code, description, article, offender, or authority...')) ?>"
                                autocomplete="off"
                                data-renainf-search>
                            <button type="button" class="vs-renainf-clear" data-renainf-clear>
                                <i class="ti ti-eraser"></i>
                                <span><?= $h($t('Clear')) ?></span>
                            </button>
                        </div>
                        <button
                            type="button"
                            class="vs-renainf-trigger"
                            data-renainf-trigger
                            aria-expanded="false">
                            <span data-renainf-trigger-label><?= $h($t('Loading RENAINF table...')) ?></span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="vs-renainf-selected" data-renainf-selected>
                            <?php if ($values['violation_code'] !== ''): ?>
                                <strong><?= $h($values['violation_code'] . '-' . $values['violation_split']) ?></strong>
                                <span><?= $h($values['description']) ?></span>
                            <?php else: ?>
                                <span><?= $h($t('No infraction selected.')) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="vs-renainf-results" data-renainf-results aria-label="<?= $h($t('Filtered RENAINF infractions')) ?>"></div>
                    </div>
                </div>

                <div class="vs-driverfine-field">
                    <label class="vs-driverfine-label"><?= $h($t('Severity and points')) ?></label>
                    <div class="vs-renainf-readonly" data-renainf-severity-display>
                        <?= $h(PluginVehicleschedulerDriverfine::getAllSeverities()[$values['severity']] ?? $t('Defined by infraction')) ?>
                    </div>
                </div>

                <div class="vs-driverfine-field">
                    <label class="vs-driverfine-label"><?= $h($t('Status')) ?></label>
                    <?php Dropdown::showFromArray('status', PluginVehicleschedulerDriverfine::getAllStatus(), ['value' => $values['status']]); ?>
                </div>

                <div class="vs-driverfine-field vs-driverfine-grid__full">
                    <label class="vs-driverfine-label"><?= $h($t('Description')) ?> *</label>
                    <textarea
                        name="description"
                        rows="5"
                        class="vs-driverfine-textarea"
                        placeholder="<?= $h($t('Describe the infraction, location, notice, or relevant administrative notes.')) ?>"
                        data-renainf-description
                        required><?= $h($values['description']) ?></textarea>
                </div>
            </div>

            <footer class="vs-driverfine-actions">
                <?php if ($fineId > 0): ?>
                    <button
                        type="submit"
                        name="delete"
                        class="vs-driverfine-btn vs-driverfine-btn--danger"
                        data-confirm-message="<?= $h($t('Delete this fine?')) ?>">
                        <i class="ti ti-trash"></i>
                        <span><?= $h($t('Delete')) ?></span>
                    </button>
                <?php endif; ?>

                <div class="vs-driverfine-actions__primary">
                    <button type="submit" name="<?= $fineId > 0 ? 'update' : 'add' ?>" class="vs-driverfine-btn">
                        <i class="ti ti-device-floppy"></i>
                        <span><?= $h($fineId > 0 ? $t('Save fine') : $t('Register fine')) ?></span>
                    </button>
                </div>
            </footer>
        </form>
    </section>
</div>
<?php
Html::footer();
