<?php

/**
 * Driver fine domain class.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerDriverfine extends CommonDBChild
{
    public static $itemtype  = 'PluginVehicleschedulerDriver';
    public static $items_id  = 'plugin_vehiclescheduler_drivers_id';
    public static $rightname = 'plugin_vehiclescheduler';
    public $dohistory = true;

    public const SEVERITY_MILD       = 1;
    public const SEVERITY_MEDIUM     = 2;
    public const SEVERITY_SEVERE     = 3;
    public const SEVERITY_VERYSEVERE = 4;

    public const STATUS_OPEN      = 1;
    public const STATUS_PAID      = 2;
    public const STATUS_APPEALED  = 3;
    public const STATUS_CANCELLED = 4;

    /**
     * Returns the translated item type name.
     *
     * @param int $nb Number of items.
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return ($nb === 1) ? 'Infração de Trânsito' : 'Infrações de Trânsito';
    }

    /**
     * Returns the tab icon.
     *
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-ticket';
    }

    /**
     * Returns all available severities.
     *
     * @return array<int, string>
     */
    public static function getAllSeverities(): array
    {
        return [
            self::SEVERITY_MILD       => '🟢 Leve — 3 pts',
            self::SEVERITY_MEDIUM     => '🟡 Média — 4 pts',
            self::SEVERITY_SEVERE     => '🟠 Grave — 5 pts',
            self::SEVERITY_VERYSEVERE => '🔴 Gravíssima — 7 pts',
        ];
    }

    /**
     * Returns severity to points mapping.
     *
     * @return array<int, int>
     */
    public static function getSeverityPoints(): array
    {
        return [
            self::SEVERITY_MILD       => 3,
            self::SEVERITY_MEDIUM     => 4,
            self::SEVERITY_SEVERE     => 5,
            self::SEVERITY_VERYSEVERE => 7,
        ];
    }

    /**
     * Returns all available statuses.
     *
     * @return array<int, string>
     */
    public static function getAllStatus(): array
    {
        return [
            self::STATUS_OPEN      => '⏳ Em aberto',
            self::STATUS_PAID      => '✅ Paga',
            self::STATUS_APPEALED  => '📝 Recurso',
            self::STATUS_CANCELLED => '❌ Cancelada',
        ];
    }

    /**
     * Returns the tab label for the parent driver item.
     *
     * @param CommonGLPI $item         Parent item.
     * @param int        $withtemplate Template flag.
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof PluginVehicleschedulerDriver) {
            $count = countElementsInTable(
                (new self())->getTable(),
                ['plugin_vehiclescheduler_drivers_id' => $item->getID()]
            );

            return self::createTabEntry('Infrações de Trânsito', $count);
        }

        return '';
    }

    /**
     * Displays the tab content for the parent driver item.
     *
     * @param CommonGLPI $item         Parent item.
     * @param int        $tabnum       Tab number.
     * @param int        $withtemplate Template flag.
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof PluginVehicleschedulerDriver) {
            return false;
        }

        require_once GLPI_ROOT . '/plugins/vehiclescheduler/front/driverfine.render.php';

        vs_render_driverfine_tab($item);

        return true;
    }

    /**
     * Returns all fines linked to a driver.
     *
     * @param int $driverId Driver identifier.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getFinesForDriver(int $driverId): array
    {
        global $DB;

        if ($driverId <= 0) {
            return [];
        }

        return iterator_to_array($DB->request([
            'FROM'  => (new self())->getTable(),
            'WHERE' => ['plugin_vehiclescheduler_drivers_id' => $driverId],
            'ORDER' => ['fine_date DESC'],
        ]));
    }

    /**
     * Builds a compact summary for the driver's infractions.
     *
     * @param array<int, array<string, mixed>> $fines Fine rows.
     *
     * @return array<string, int|string>
     */
    public static function buildDriverSummary(array $fines): array
    {
        $pointsMap   = self::getSeverityPoints();
        $totalPoints = 0;
        $openCount   = 0;

        foreach ($fines as $fine) {
            if ((int) ($fine['status'] ?? 0) !== self::STATUS_CANCELLED) {
                $totalPoints += $pointsMap[(int) ($fine['severity'] ?? 0)] ?? 0;
            }

            if ((int) ($fine['status'] ?? 0) === self::STATUS_OPEN) {
                $openCount++;
            }
        }

        $barColor   = '#22c55e';
        $statusText = '✅ Situação regular';

        if ($totalPoints >= 20) {
            $barColor   = '#dc2626';
            $statusText = '⚠️ Atenção: pontuação elevada';
        } elseif ($totalPoints >= 15) {
            $barColor   = '#f59e0b';
            $statusText = '⚡ Alerta: próximo do limite';
        } elseif ($totalPoints >= 10) {
            $barColor   = '#fbbf24';
            $statusText = '📊 Atenção moderada';
        }

        return [
            'total_points' => $totalPoints,
            'open_count'   => $openCount,
            'total_fines'  => count($fines),
            'bar_color'    => $barColor,
            'status_text'  => $statusText,
            'percentage'   => min(100, (int) round(($totalPoints / 40) * 100)),
        ];
    }

    /**
     * Returns vehicle labels for fine rows.
     *
     * @param array<int, array<string, mixed>> $fines Fine rows.
     *
     * @return array<int, string>
     */
    public static function getVehicleLabels(array $fines): array
    {
        $vehicleIds = [];

        foreach ($fines as $fine) {
            $vehicleId = (int) ($fine['plugin_vehiclescheduler_vehicles_id'] ?? 0);

            if ($vehicleId > 0) {
                $vehicleIds[$vehicleId] = $vehicleId;
            }
        }

        if ($vehicleIds === []) {
            return [];
        }

        global $DB;

        $labels = [];

        foreach ($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
            'WHERE' => ['id' => array_values($vehicleIds)],
        ]) as $row) {
            $labels[(int) $row['id']] = trim(
                (string) ($row['name'] ?? '') . ' (' . (string) ($row['plate'] ?? '') . ')'
            );
        }

        return $labels;
    }

    /**
     * Validates and normalizes input before creating a fine.
     *
     * @param array<string, mixed> $input Raw input.
     *
     * @return array<string, mixed>|false
     */
    public function prepareInputForAdd($input)
    {
        $input = self::normalizeInput($input);

        if ($input['plugin_vehiclescheduler_drivers_id'] <= 0) {
            Session::addMessageAfterRedirect('O motorista é obrigatório.', false, ERROR);
            return false;
        }

        if ($input['fine_date'] === null) {
            Session::addMessageAfterRedirect('A data da infração é obrigatória.', false, ERROR);
            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('A descrição é obrigatória.', false, ERROR);
            return false;
        }

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    /**
     * Validates and normalizes input before updating a fine.
     *
     * @param array<string, mixed> $input Raw input.
     *
     * @return array<string, mixed>|false
     */
    public function prepareInputForUpdate($input)
    {
        $input = self::normalizeInput($input);

        if ($input['id'] <= 0) {
            Session::addMessageAfterRedirect('ID da infração inválido.', false, ERROR);
            return false;
        }

        return $this->prepareInputForAdd($input);
    }

    /**
     * Returns GLPI search options.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rawSearchOptions()
    {
        return [
            ['id' => 'common', 'name' => 'Infrações de Trânsito'],
            ['id' => '1', 'table' => $this->getTable(), 'field' => 'id', 'name' => 'ID', 'datatype' => 'number'],
            ['id' => '2', 'table' => $this->getTable(), 'field' => 'fine_date', 'name' => 'Data', 'datatype' => 'date'],
            ['id' => '3', 'table' => $this->getTable(), 'field' => 'description', 'name' => 'Descrição', 'datatype' => 'text'],
            ['id' => '4', 'table' => $this->getTable(), 'field' => 'severity', 'name' => 'Severidade', 'datatype' => 'specific', 'searchtype' => ['equals']],
            ['id' => '5', 'table' => $this->getTable(), 'field' => 'status', 'name' => 'Status', 'datatype' => 'specific', 'searchtype' => ['equals']],
        ];
    }

    /**
     * Returns display values for specific fields.
     *
     * @param string               $field   Field name.
     * @param mixed                $values  Values payload.
     * @param array<string, mixed> $options Display options.
     *
     * @return mixed
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'severity') {
            return self::getAllSeverities()[$values[$field]] ?? $values[$field];
        }

        if ($field === 'status') {
            return self::getAllStatus()[$values[$field]] ?? $values[$field];
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Normalizes fine input payload.
     *
     * @param array<string, mixed> $input Raw input.
     *
     * @return array<string, mixed>
     */
    private static function normalizeInput(array $input): array
    {
        return [
            'id'                                  => PluginVehicleschedulerInput::int($input, 'id', 0, 0),
            'plugin_vehiclescheduler_drivers_id'  => PluginVehicleschedulerInput::int($input, 'plugin_vehiclescheduler_drivers_id', 0, 0),
            'plugin_vehiclescheduler_vehicles_id' => PluginVehicleschedulerInput::int($input, 'plugin_vehiclescheduler_vehicles_id', 0, 0),
            'entities_id'                         => PluginVehicleschedulerInput::int(
                $input,
                'entities_id',
                (int) ($_SESSION['glpiactive_entity'] ?? 0),
                0
            ),
            'fine_date'                           => PluginVehicleschedulerInput::date($input, 'fine_date'),
            'severity'                            => PluginVehicleschedulerInput::int(
                $input,
                'severity',
                self::SEVERITY_SEVERE,
                self::SEVERITY_MILD,
                self::SEVERITY_VERYSEVERE
            ),
            'status'                              => PluginVehicleschedulerInput::int(
                $input,
                'status',
                self::STATUS_OPEN,
                self::STATUS_OPEN,
                self::STATUS_CANCELLED
            ),
            'description'                         => PluginVehicleschedulerInput::text($input, 'description', 65535, ''),
        ];
    }
}
