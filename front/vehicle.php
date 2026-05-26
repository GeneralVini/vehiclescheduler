<?php

include_once __DIR__ . '/../src/Bootstrap/common.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

/**
 * Escapes HTML output for safe rendering.
 *
 * @param string|null $value Raw value to escape.
 *
 * @return string
 */
function vs_vehicle_list_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a datetime value for UI rendering.
 *
 * @param string|null $value Raw datetime value.
 *
 * @return string
 */
function vs_vehicle_list_format_datetime(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return __('Not informed', 'vehiclescheduler');
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y H:i', $timestamp);
}

/**
 * Converts a string to uppercase using multibyte-safe functions when available.
 *
 * @param string $value Source string.
 *
 * @return string
 */
function vs_vehicle_list_upper(string $value): string
{
    if (function_exists('mb_strtoupper')) {
        return (string) mb_strtoupper($value);
    }

    return strtoupper($value);
}

/**
 * Returns the first characters of a string using multibyte-safe functions when available.
 *
 * @param string $value Source string.
 * @param int    $limit Character limit.
 *
 * @return string
 */
function vs_vehicle_list_substr(string $value, int $limit): string
{
    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, $limit);
    }

    return (string) substr($value, 0, $limit);
}

/**
 * Builds a short abbreviation for the vehicle.
 *
 * @param array<string, mixed> $vehicle Vehicle row payload.
 *
 * @return string
 */
function vs_vehicle_list_abbreviation(array $vehicle): string
{
    $name = trim((string) ($vehicle['name'] ?? ''));
    $brand = trim((string) ($vehicle['brand'] ?? ''));
    $model = trim((string) ($vehicle['model'] ?? ''));

    $source = $name !== '' ? $name : trim($brand . ' ' . $model);

    if ($source === '') {
        return 'VEI';
    }

    $parts = preg_split('/\s+/', $source) ?: [];
    $abbr = '';

    if (count($parts) >= 2) {
        foreach (array_slice($parts, 0, 3) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $abbr .= vs_vehicle_list_substr(vs_vehicle_list_upper($part), 1);
        }
    } else {
        $flat = preg_replace('/[^[:alnum:]]/u', '', $source) ?? '';
        $abbr = vs_vehicle_list_substr(vs_vehicle_list_upper($flat), 3);
    }

    $abbr = trim($abbr);

    return $abbr !== '' ? $abbr : 'VEI';
}

/**
 * Returns the required CNH label.
 *
 * @param string $category CNH category code.
 *
 * @return string
 */
function vs_vehicle_list_cnh_label(string $category): string
{
    $options = PluginVehicleschedulerVehicle::getRequiredCNHOptions();

    return (string) ($options[$category] ?? $category);
}

/**
 * Builds a small status pill.
 *
 * @param string $label Visible label.
 * @param string $modifier CSS modifier suffix.
 *
 * @return string
 */
function vs_vehicle_list_status_pill(string $label, string $modifier): string
{
    return '<span class="vs-vehicle-grid__pill vs-vehicle-grid__pill--'
        . vs_vehicle_list_escape($modifier)
        . '">'
        . vs_vehicle_list_escape($label)
        . '</span>';
}

$vehicles = PluginVehicleschedulerVehicle::getManagementGridRows();
$vehicleFormUrl = plugin_vehiclescheduler_get_front_url('vehicle.form.php');
$t = static fn(string $message): string => __($message, 'vehiclescheduler');

Html::header(
    $t('Vehicles'),
    $_SERVER['PHP_SELF'],
    'tools',
    PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css([
    'css/pages/vehicle-grid.css',
    'css/core/flash.css',
]);

plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();
?>

<div class="vs-page-header">
    <div class="vs-header-content">
        <div class="vs-header-title">
            <div class="vs-header-icon-wrapper">
                <i class="ti ti-car vs-header-icon"></i>
            </div>
            <div>
                <h2><?= vs_vehicle_list_escape($t('Vehicle Management')) ?></h2>
                <p class="vs-page-subtitle"><?= vs_vehicle_list_escape($t('Compact operational grid for lookup, quick filters, and registry maintenance.')) ?></p>
            </div>
        </div>

        <?php if (Session::haveRight('plugin_vehiclescheduler_management', CREATE)) : ?>
            <a href="<?= vs_vehicle_list_escape($vehicleFormUrl) ?>" class="vs-btn-add">
                <i class="ti ti-plus"></i>
                <span><?= vs_vehicle_list_escape($t('Add Vehicle')) ?></span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="vs-vehicle-grid" data-vs-vehicle-grid>
    <section class="vs-vehicle-grid__toolbar">
        <div class="vs-vehicle-grid__search-wrap">
            <input
                type="search"
                placeholder="<?= vs_vehicle_list_escape($t('Search vehicle...')) ?>"
                aria-label="<?= vs_vehicle_list_escape($t('Search vehicles')) ?>"
                data-vehicle-filter-search>
        </div>

        <div class="vs-vehicle-grid__results-text" data-vehicle-result-count>
            <?= vs_vehicle_list_escape(sprintf($t('Showing %d vehicles'), (int) count($vehicles))) ?>
        </div>

        <div class="vs-vehicle-grid__filters">
            <label class="vs-vehicle-grid__filter">
                <span><?= vs_vehicle_list_escape($t('Situation')) ?></span>
                <select data-vehicle-filter-active>
                    <option value="all"><?= vs_vehicle_list_escape($t('All')) ?></option>
                    <option value="1"><?= vs_vehicle_list_escape($t('Active')) ?></option>
                    <option value="0"><?= vs_vehicle_list_escape($t('Inactive')) ?></option>
                </select>
            </label>

            <label class="vs-vehicle-grid__filter">
                <span>CNH</span>
                <select data-vehicle-filter-cnh>
                    <option value="all"><?= vs_vehicle_list_escape($t('All')) ?></option>
                    <option value="A"><?= vs_vehicle_list_escape($t('A - Motorcycle')) ?></option>
                    <option value="B"><?= vs_vehicle_list_escape($t('B - Car')) ?></option>
                    <option value="D"><?= vs_vehicle_list_escape($t('D - Truck or van')) ?></option>
                </select>
            </label>

            <button type="button" class="vs-vehicle-grid__clear" data-vehicle-clear-filters>
                <i class="ti ti-eraser"></i>
                <span><?= vs_vehicle_list_escape($t('Clear')) ?></span>
            </button>
        </div>
    </section>

    <div class="vs-vehicle-grid__table-wrap">
        <table class="vs-vehicle-grid__table">
            <thead>
                <tr>
                    <th><?= vs_vehicle_list_escape($t('Vehicle')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Plate')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Brand / Model')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Year')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Passengers')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Required CNH')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Status')) ?></th>
                    <th><?= vs_vehicle_list_escape($t('Updated at')) ?></th>
                    <th class="vs-vehicle-grid__actions-col"><?= vs_vehicle_list_escape($t('Actions')) ?></th>
                </tr>
            </thead>
            <tbody data-vehicle-row-list>
                <?php foreach ($vehicles as $vehicle) : ?>
                    <?php
                    $vehicleName = (string) (($vehicle['name'] ?? '') !== '' ? $vehicle['name'] : $t('Unnamed vehicle'));
                    $vehicleUrl = $vehicleFormUrl . '?id=' . (int) ($vehicle['id'] ?? 0);
                    $requiredCnh = (string) ($vehicle['required_cnh_category'] ?? '');
                    $requiredCnhLabel = vs_vehicle_list_cnh_label($requiredCnh);

                    $searchIndex = implode(' ', [
                        (string) ($vehicle['name'] ?? ''),
                        (string) ($vehicle['plate'] ?? ''),
                        (string) ($vehicle['brand'] ?? ''),
                        (string) ($vehicle['model'] ?? ''),
                        (string) ($vehicle['year'] ?? ''),
                        (string) ($vehicle['seats'] ?? ''),
                        $requiredCnh,
                        $requiredCnhLabel,
                    ]);
                    ?>
                    <tr
                        data-vehicle-row
                        data-search="<?= vs_vehicle_list_escape(strtolower($searchIndex)) ?>"
                        data-active="<?= (int) ($vehicle['is_active'] ?? 0) ?>"
                        data-required-cnh="<?= vs_vehicle_list_escape($requiredCnh) ?>">
                        <td>
                            <div class="vs-vehicle-grid__identity">
                                <div class="vs-vehicle-grid__identity-body">
                                    <div class="vs-vehicle-grid__name">
                                        <?= vs_vehicle_list_escape($vehicleName) ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td>
                            <?= vs_vehicle_list_escape((string) ((($vehicle['plate'] ?? '') !== '') ? $vehicle['plate'] : $t('Not informed'))) ?>
                        </td>

                        <td>
                            <div class="vs-vehicle-grid__brand-model">
                                <span class="vs-vehicle-grid__brand">
                                    <?= vs_vehicle_list_escape((string) ((($vehicle['brand'] ?? '') !== '') ? $vehicle['brand'] : $t('Brand not informed'))) ?>
                                </span>
                                <span class="vs-vehicle-grid__model">
                                    <?= vs_vehicle_list_escape((string) ((($vehicle['model'] ?? '') !== '') ? $vehicle['model'] : $t('Model not informed'))) ?>
                                </span>
                            </div>
                        </td>

                        <td>
                            <?= vs_vehicle_list_escape((string) ((($vehicle['year'] ?? '') !== '') ? (string) $vehicle['year'] : $t('Not informed'))) ?>
                        </td>

                        <td>
                            <?= vs_vehicle_list_escape((string) ((($vehicle['seats'] ?? '') !== '') ? (string) $vehicle['seats'] : $t('Not informed'))) ?>
                        </td>

                        <td>
                            <?= vs_vehicle_list_escape($requiredCnhLabel) ?>
                        </td>

                        <td>
                            <div class="vs-vehicle-grid__pill-stack">
                                <?= vs_vehicle_list_status_pill(
                                    ((int) ($vehicle['is_active'] ?? 0) === 1 ? $t('Active') : $t('Inactive')),
                                    ((int) ($vehicle['is_active'] ?? 0) === 1 ? 'active' : 'inactive')
                                ) ?>
                            </div>
                        </td>

                        <td>
                            <?= vs_vehicle_list_escape(vs_vehicle_list_format_datetime((string) ($vehicle['date_mod'] ?? ''))) ?>
                        </td>

                        <td class="vs-vehicle-grid__actions-col">
                            <a href="<?= vs_vehicle_list_escape($vehicleUrl) ?>" class="vs-vehicle-grid__action">
                                <i class="ti ti-pencil"></i>
                                <span><?= vs_vehicle_list_escape($t('Open')) ?></span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="vs-vehicle-grid__empty" data-vehicle-empty hidden>
        <div class="vs-vehicle-grid__empty-icon"><i class="ti ti-car"></i></div>
        <h3><?= vs_vehicle_list_escape($t('No vehicle found')) ?></h3>
        <p><?= vs_vehicle_list_escape($t('Review the applied filters.')) ?></p>
    </div>
</div>

<?php
plugin_vehiclescheduler_load_script('js/vehicle-grid.js');
plugin_vehiclescheduler_load_script('js/flash.js');
Html::footer();
?>
