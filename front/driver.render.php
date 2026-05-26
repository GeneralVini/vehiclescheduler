<?php
// front/driver.render.php

/**
 * Driver form renderer.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Escapes HTML output for safe rendering.
 *
 * @param string|null $value Raw value.
 *
 * @return string
 */
function vs_driver_render_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Renders the CNH expiry badge for the current form.
 *
 * @param array<string, int|string|null> $status Expiry status payload.
 *
 * @return string
 */
function vs_driver_render_expiry_badge(array $status): string
{
    $badge = PluginVehicleschedulerDriver::getCNHExpiryBadgeData($status);

    return '<span class="vs-driver-expiry-badge ' . vs_driver_render_escape((string) ($badge['class'] ?? '')) . '">'
        . vs_driver_render_escape((string) ($badge['label'] ?? __('No date', 'vehiclescheduler')))
        . '</span>';
}

/**
 * Returns the icon class associated with a CNH category.
 *
 * @param string $category CNH category code.
 *
 * @return string
 */
function vs_driver_render_category_icon(string $category): string
{
    $icons = [
        'A' => 'ti ti-motorbike',
        'B' => 'ti ti-car',
        'D' => 'ti ti-truck',
    ];

    return $icons[$category] ?? 'ti ti-license';
}

/**
 * Renders the driver form body inside the GLPI form wrapper.
 *
 * @param PluginVehicleschedulerDriver $driver Driver instance.
 * @param int                          $id     Current driver identifier.
 *
 * @return void
 */
function vs_render_driver_form(PluginVehicleschedulerDriver $driver, int $id): void
{
    $t = static fn(string $message): string => __($message, 'vehiclescheduler');
    $selectedCategories = PluginVehicleschedulerDriver::getDriverCNHCategoryList(
        $driver->fields['cnh_category'] ?? ''
    );

    $badgeHtml = '';

    if ($id > 0 && !empty($driver->fields['cnh_expiry'])) {
        $badgeHtml = vs_driver_render_expiry_badge(
            PluginVehicleschedulerDriver::getCNHExpiryStatus((string) ($driver->fields['cnh_expiry'] ?? ''))
        );
    }
    ?>
    <div class="vs-driver-wrap" data-vs-driver-form>
        <div class="vs-driver-surface">
            <div class="vs-driver-card">
                <div class="vs-driver-head">
                    <div>
                        <h3 class="vs-driver-title">
                            <i class="ti ti-steering-wheel"></i>
                            <?= vs_driver_render_escape($t('Driver registration')) ?>
                        </h3>
                        <div class="vs-driver-sub">
                            <?= vs_driver_render_escape($t('Essential fields for fleet management with privacy by default.')) ?>
                        </div>
                    </div>
                    <div class="vs-driver-pill">
                        <span class="dot"></span>
                        <?= vs_driver_render_escape($t('Drivers')) ?>
                    </div>
                </div>

                <div class="vs-driver-privacy">
                    <strong><?= vs_driver_render_escape($t('LGPD notice:')) ?></strong>
                    <?= vs_driver_render_escape($t('We collect only the minimum required data. We do not store CPF, RG, CNH number, or biometrics. Legal basis: contract execution and legitimate operational interest.')) ?>
                </div>

                <div class="vs-form-feedback" data-driver-validation hidden></div>

                <div class="vs-driver-form-grid">
                    <div class="vs-driver-field vs-driver-field--user">
                        <div class="vs-driver-label">
                            <?= vs_driver_render_escape($t('User (GLPI)')) ?> <span class="red">*</span>
                        </div>
                        <?php
                        User::dropdown([
                            'name'   => 'users_id',
                            'value'  => (int) ($driver->fields['users_id'] ?? 0),
                            'entity' => (int) ($driver->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
                            'right'  => 'all',
                        ]);
                        ?>
                        <div class="vs-driver-hint">
                            <?= vs_driver_render_escape($t('The driver name will be filled automatically from the selected user.')) ?>
                        </div>
                    </div>

                    <div class="vs-driver-field vs-driver-field--categories">
                        <div class="vs-driver-label">
                            <?= vs_driver_render_escape($t('CNH categories')) ?> <span class="red">*</span>
                        </div>
                        <div class="vs-driver-category-grid" data-driver-category-group>
                            <?php foreach (PluginVehicleschedulerDriver::getDriverSelectableCNHCategories() as $category => $label) : ?>
                                <label class="vs-driver-category-option">
                                    <input
                                        type="checkbox"
                                        name="cnh_category[]"
                                        value="<?= vs_driver_render_escape($category) ?>"
                                        <?= in_array($category, $selectedCategories, true) ? 'checked' : '' ?>
                                    >
                                    <span class="vs-driver-category-option__icon">
                                        <i class="<?= vs_driver_render_escape(vs_driver_render_category_icon($category)) ?>"></i>
                                    </span>
                                    <span class="vs-driver-category-option__meta">
                                        <span class="vs-driver-category-option__code">
                                            <?= vs_driver_render_escape($category) ?>
                                        </span>
                                        <span class="vs-driver-category-option__text">
                                            <?= vs_driver_render_escape($label) ?>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="vs-driver-hint">
                            <?= vs_driver_render_escape($t('MVP rule: motorcycles require A, cars accept B or D, and trucks/vans require D.')) ?>
                        </div>
                    </div>

                    <div class="vs-driver-field vs-driver-field--registration">
                        <div class="vs-driver-label">
                            <?= vs_driver_render_escape($t('Internal registration')) ?> <span class="vs-driver-hint-inline"><?= vs_driver_render_escape($t('optional')) ?></span>
                        </div>
                        <?= Html::input('registration', [
                            'value'       => $driver->fields['registration'] ?? '',
                            'size'        => 18,
                            'placeholder' => 'ex: EMP-0042',
                        ]) ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--group">
                        <div class="vs-driver-label">
                            <?= vs_driver_render_escape($t('Department / Sector')) ?> <span class="vs-driver-hint-inline"><?= vs_driver_render_escape($t('optional')) ?></span>
                        </div>
                        <?php
                        Group::dropdown([
                            'name'   => 'groups_id',
                            'value'  => (int) ($driver->fields['groups_id'] ?? 0),
                            'entity' => (int) ($driver->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
                        ]);
                        ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--phone">
                        <div class="vs-driver-label">
                            <?= vs_driver_render_escape($t('Contact phone')) ?> <span class="vs-driver-hint-inline"><?= vs_driver_render_escape($t('optional')) ?></span>
                        </div>
                        <?= Html::input('contact_phone', [
                            'value'       => $driver->fields['contact_phone'] ?? '',
                            'size'        => 18,
                            'placeholder' => '(00) 00000-0000',
                        ]) ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--expiry">
                        <div class="vs-driver-label">
                            <span><?= vs_driver_render_escape($t('CNH expiry')) ?> <span class="red">*</span></span>
                            <span class="vs-driver-badge-slot"><?= $badgeHtml ?></span>
                        </div>
                        <?php
                        Html::showDateField('cnh_expiry', [
                            'value' => $driver->fields['cnh_expiry'] ?? '',
                        ]);
                        ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--active">
                        <div class="vs-driver-label"><?= vs_driver_render_escape($t('Active')) ?></div>
                        <?php Dropdown::showYesNo('is_active', (int) ($driver->fields['is_active'] ?? 1)); ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--comment">
                        <div class="vs-driver-label">
                            <?= vs_driver_render_escape($t('Notes')) ?> <span class="vs-driver-hint-inline"><?= vs_driver_render_escape($t('optional')) ?></span>
                        </div>
                        <?= Html::textarea([
                            'name'  => 'comment',
                            'value' => $driver->fields['comment'] ?? '',
                            'rows'  => 3,
                        ]) ?>
                        <div class="vs-driver-hint">
                            <?= vs_driver_render_escape($t('Use this field for useful operational guidance, avoiding sensitive personal data.')) ?>
                        </div>
                    </div>
                </div>

                <div class="vs-driver-foot">
                    <?= vs_driver_render_escape($t('Keep only operational information required for fleet allocation and compliance.')) ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
