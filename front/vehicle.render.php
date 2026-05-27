<?php

/**
 * Vehicle form renderer.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function vs_vehicle_render_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_vehicle_render_cnh_icon(string $category): string
{
    $icons = [
        'A' => 'ti ti-motorbike',
        'B' => 'ti ti-car',
        'D' => 'ti ti-truck',
    ];

    return $icons[$category] ?? 'ti ti-license';
}

function vs_render_vehicle_form(PluginVehicleschedulerVehicle $vehicle): void
{
    $t = static fn(string $message): string => __($message, 'vehiclescheduler');
    $selectedRequiredCategory = (string) ($vehicle->fields['required_cnh_category'] ?? PluginVehicleschedulerVehicle::REQUIRED_CNH_B);
    ?>
    <div
        class="vs-vehicle-wrap"
        data-vs-vehicle-form
        data-vehicle-name-required="<?= vs_vehicle_render_escape($t('Vehicle name is required.')) ?>"
        data-vehicle-plate-required="<?= vs_vehicle_render_escape($t('Plate is required.')) ?>"
        data-vehicle-plate-invalid="<?= vs_vehicle_render_escape($t('Enter a valid Brazilian registration number.')) ?>"
        data-vehicle-year-invalid="<?= vs_vehicle_render_escape($t('Enter a valid year for the vehicle.')) ?>"
        data-vehicle-seats-invalid="<?= vs_vehicle_render_escape($t('Passenger capacity must be between 1 and 100.')) ?>"
        data-vehicle-required-licence="<?= vs_vehicle_render_escape($t('Select the required CNH category for the vehicle.')) ?>"
    >
        <div class="vs-vehicle-surface">
            <div class="vs-vehicle-card">
                <div class="vs-vehicle-head">
                    <div>
                        <h3 class="vs-vehicle-title"><i class="ti ti-car"></i> <?= vs_vehicle_render_escape($t('Vehicle registration')) ?></h3>
                        <div class="vs-vehicle-sub"><?= vs_vehicle_render_escape($t('Essential operational data for fleet availability, allocation, and traceability.')) ?></div>
                    </div>
                    <div class="vs-vehicle-pill"><span class="dot"></span> <?= vs_vehicle_render_escape($t('Vehicles')) ?></div>
                </div>

                <div class="vs-form-feedback" data-vehicle-validation hidden></div>

                <div class="vs-vehicle-grid">
                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Vehicle name')) ?> <span class="red">*</span></div>
                        <?= Html::input('name', [
                            'value'       => $vehicle->fields['name'] ?? '',
                            'size'        => 40,
                            'placeholder' => $t('Example: Administrative Vehicle 01'),
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Plate')) ?> <span class="red">*</span></div>
                        <?= Html::input('plate', [
                            'value'       => $vehicle->fields['plate'] ?? '',
                            'size'        => 20,
                            'placeholder' => $t('Example: ABC1D23'),
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Brand')) ?> <span class="vs-vehicle-hint-inline"><?= vs_vehicle_render_escape($t('optional')) ?></span></div>
                        <?= Html::input('brand', [
                            'value'       => $vehicle->fields['brand'] ?? '',
                            'size'        => 30,
                            'placeholder' => $t('Example: Toyota'),
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Model')) ?> <span class="vs-vehicle-hint-inline"><?= vs_vehicle_render_escape($t('optional')) ?></span></div>
                        <?= Html::input('model', [
                            'value'       => $vehicle->fields['model'] ?? '',
                            'size'        => 30,
                            'placeholder' => $t('Example: Hilux'),
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Year')) ?> <span class="red">*</span></div>
                        <?= Html::input('year', [
                            'value'       => (int) ($vehicle->fields['year'] ?? (int) date('Y')),
                            'type'        => 'number',
                            'min'         => PluginVehicleschedulerVehicle::MIN_YEAR,
                            'max'         => PluginVehicleschedulerVehicle::MAX_YEAR,
                            'placeholder' => $t('Example: 2025'),
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Passenger capacity')) ?> <span class="red">*</span></div>
                        <?= Html::input('seats', [
                            'value'       => (int) ($vehicle->fields['seats'] ?? 5),
                            'type'        => 'number',
                            'min'         => PluginVehicleschedulerVehicle::MIN_SEATS,
                            'max'         => PluginVehicleschedulerVehicle::MAX_SEATS,
                            'placeholder' => $t('Example: 5'),
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Active')) ?></div>
                        <?php Dropdown::showYesNo('is_active', (int) ($vehicle->fields['is_active'] ?? 1)); ?>
                        <div class="vs-vehicle-hint"><?= vs_vehicle_render_escape($t('Inactive vehicles are hidden from operational allocation.')) ?></div>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Required CNH')) ?> <span class="red">*</span></div>
                        <div class="vs-vehicle-cnh-grid" data-vehicle-cnh-group>
                            <?php foreach (PluginVehicleschedulerVehicle::getRequiredCNHOptions() as $category => $label) : ?>
                                <label class="vs-vehicle-cnh-option">
                                    <input
                                        type="radio"
                                        name="required_cnh_category"
                                        value="<?= vs_vehicle_render_escape($category) ?>"
                                        <?= $selectedRequiredCategory === $category ? 'checked' : '' ?>
                                    >
                                    <span class="vs-vehicle-cnh-option__icon">
                                        <i class="<?= vs_vehicle_render_escape(vs_vehicle_render_cnh_icon($category)) ?>"></i>
                                    </span>
                                    <span class="vs-vehicle-cnh-option__meta">
                                        <span class="vs-vehicle-cnh-option__code"><?= vs_vehicle_render_escape($category) ?></span>
                                        <span class="vs-vehicle-cnh-option__text"><?= vs_vehicle_render_escape($label) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="vs-vehicle-hint"><?= vs_vehicle_render_escape($t('MVP rule: motorcycles require A, cars accept B or D, and trucks/vans require D.')) ?></div>
                    </div>

                    <div class="vs-vehicle-field vs-vehicle-field--full">
                        <div class="vs-vehicle-label"><?= vs_vehicle_render_escape($t('Notes')) ?> <span class="vs-vehicle-hint-inline"><?= vs_vehicle_render_escape($t('optional')) ?></span></div>
                        <textarea name="comment" rows="3"><?= vs_vehicle_render_escape($vehicle->fields['comment'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
