<?php

/**
 * Vehicle entity for Vehicle Scheduler.
 *
 * Handles fleet vehicle registration and basic operational validation.
 *
 * @method void addDefaultFormTab(array &$ong)
 * @method void addStandardTab(string $itemtype, array &$ong, array $options = [])
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerVehicle extends \CommonDBTM
{
    public $dohistory = true;

    public static $rightname = 'plugin_vehiclescheduler_management';

    public const MIN_YEAR = 1900;
    public const MAX_YEAR = 2100;
    public const MIN_SEATS = 1;
    public const MAX_SEATS = 100;

    public const REQUIRED_CNH_A = 'A';
    public const REQUIRED_CNH_B = 'B';
    public const REQUIRED_CNH_D = 'D';

    public static function getTypeName($nb = 0)
    {
        return _n('Vehicle', 'Vehicles', $nb, 'vehiclescheduler');
    }

    public static function getMenuName()
    {
        return __('Vehicles', 'vehiclescheduler');
    }

    public static function getIcon()
    {
        return 'ti ti-car';
    }

    public static function getRequiredCNHOptions(): array
    {
        return [
            self::REQUIRED_CNH_A => __('A - Motorcycle', 'vehiclescheduler'),
            self::REQUIRED_CNH_B => __('B - Car', 'vehiclescheduler'),
            self::REQUIRED_CNH_D => __('D - Truck or van', 'vehiclescheduler'),
        ];
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        $menu = [
            'title' => self::getMenuName(),
            'page'  => '/plugins/vehiclescheduler/front/vehicle.php',
            'icon'  => self::getIcon(),
            'links' => [
                'search' => '/plugins/vehiclescheduler/front/vehicle.php',
            ],
            'options' => [
                'vehicle' => [
                    'title'          => self::getMenuName(),
                    'page'           => '/plugins/vehiclescheduler/front/vehicle.php',
                    'icon'           => self::getIcon(),
                    'links'          => [
                        'search' => '/plugins/vehiclescheduler/front/vehicle.php',
                    ],
                    'lists_itemtype' => self::class,
                ],
            ],
        ];

        if (Session::haveRight(self::$rightname, CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/vehicle.form.php';
            $menu['options']['vehicle']['links']['add'] = '/plugins/vehiclescheduler/front/vehicle.form.php';
        }

        return $menu;
    }

    public static function dropdown($options = [])
    {
        $params = [
            'name'      => 'plugin_vehiclescheduler_vehicles_id',
            'value'     => 0,
            'condition' => ['is_active' => 1],
            'display'   => true,
        ];

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        return Dropdown::show(self::class, $params);
    }

    public static function getVehicleRequiredCNHMap(): array
    {
        global $DB;

        $table = (new self())->getTable();
        $labels = self::getRequiredCNHOptions();
        $map = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'required_cnh_category'],
            'FROM'   => $table,
            'WHERE'  => ['is_active' => 1],
            'ORDER'  => ['name ASC', 'id ASC'],
        ]);

        foreach ($iterator as $row) {
            $requiredCategory = (string) ($row['required_cnh_category'] ?? self::REQUIRED_CNH_B);

            $map[(string) ((int) $row['id'])] = [
                'id'                 => (int) $row['id'],
                'name'               => (string) ($row['name'] ?? ''),
                'requiredCategory'   => $requiredCategory,
                'requiredLabel'      => (string) ($labels[$requiredCategory] ?? $requiredCategory),
            ];
        }

        return $map;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        if (!function_exists('plugin_vehiclescheduler_apply_configured_locale')) {
            require_once dirname(__DIR__, 2) . '/src/Bootstrap/common.php';
        }

        plugin_vehiclescheduler_apply_configured_locale();

        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        plugin_vehiclescheduler_apply_configured_locale();

        echo "<tr><td colspan='4'>";
        require_once dirname(__DIR__, 2) . '/front/vehicle.render.php';
        vs_render_vehicle_form($this);
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = self::normalizeVehicleInput($input);

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__('Vehicle name is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['plate'] === '') {
            Session::addMessageAfterRedirect(__('Plate is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['year'] < self::MIN_YEAR || $input['year'] > self::MAX_YEAR) {
            Session::addMessageAfterRedirect(__('Invalid year.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['seats'] < self::MIN_SEATS || $input['seats'] > self::MAX_SEATS) {
            Session::addMessageAfterRedirect(__('Invalid passenger capacity.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if (!array_key_exists($input['required_cnh_category'], self::getRequiredCNHOptions())) {
            Session::addMessageAfterRedirect(__('Select the required CNH category for the vehicle.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if (self::isPlateAlreadyUsed($input['plate'])) {
            Session::addMessageAfterRedirect(__('The informed plate is already in use.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = self::normalizeVehicleInput($input);

        if ($input['id'] <= 0) {
            Session::addMessageAfterRedirect(__('Invalid vehicle ID.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__('Vehicle name is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['plate'] === '') {
            Session::addMessageAfterRedirect(__('Plate is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['year'] < self::MIN_YEAR || $input['year'] > self::MAX_YEAR) {
            Session::addMessageAfterRedirect(__('Invalid year.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['seats'] < self::MIN_SEATS || $input['seats'] > self::MAX_SEATS) {
            Session::addMessageAfterRedirect(__('Invalid passenger capacity.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if (!array_key_exists($input['required_cnh_category'], self::getRequiredCNHOptions())) {
            Session::addMessageAfterRedirect(__('Select the required CNH category for the vehicle.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if (self::isPlateAlreadyUsed($input['plate'], $input['id'])) {
            Session::addMessageAfterRedirect(__('The informed plate is already in use.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(2)
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => $this->getTable(),
            'field'    => 'plate',
            'name'     => __('Plate', 'vehiclescheduler'),
            'datatype' => 'string'
        ];

        $tab[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'brand',
            'name'     => __('Brand', 'vehiclescheduler'),
            'datatype' => 'string'
        ];

        $tab[] = [
            'id'       => '4',
            'table'    => $this->getTable(),
            'field'    => 'model',
            'name'     => __('Model', 'vehiclescheduler'),
            'datatype' => 'string'
        ];

        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'year',
            'name'     => __('Year', 'vehiclescheduler'),
            'datatype' => 'number'
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'seats',
            'name'     => __('Passengers', 'vehiclescheduler'),
            'datatype' => 'number'
        ];

        $tab[] = [
            'id'       => '7',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active', 'vehiclescheduler'),
            'datatype' => 'bool'
        ];

        $tab[] = [
            'id'         => '8',
            'table'      => $this->getTable(),
            'field'      => 'required_cnh_category',
            'name'       => __('Required CNH', 'vehiclescheduler'),
            'datatype'   => 'specific',
            'searchtype' => ['equals', 'notequals']
        ];

        $tab[] = [
            'id'       => '19',
            'table'    => $this->getTable(),
            'field'    => 'date_mod',
            'name'     => __('Last update', 'vehiclescheduler'),
            'datatype' => 'datetime'
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'required_cnh_category') {
            return self::getRequiredCNHOptions()[$values[$field]] ?? $values[$field];
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private static function normalizeVehicleInput(array $input): array
    {
        $plate = PluginVehicleschedulerInput::string($input, 'plate', 50, '');
        $plate = self::normalizePlate($plate);

        return [
            'id'          => PluginVehicleschedulerInput::int($input, 'id', 0, 0),
            'entities_id' => PluginVehicleschedulerInput::int(
                $input,
                'entities_id',
                (int) ($_SESSION['glpiactive_entity'] ?? 0),
                0
            ),
            'is_recursive' => PluginVehicleschedulerInput::int($input, 'is_recursive', 0, 0, 1),
            'name'        => PluginVehicleschedulerInput::string($input, 'name', 255, ''),
            'plate'       => $plate,
            'brand'       => PluginVehicleschedulerInput::string($input, 'brand', 100, ''),
            'model'       => PluginVehicleschedulerInput::string($input, 'model', 100, ''),
            'year'        => PluginVehicleschedulerInput::int($input, 'year', (int) date('Y')),
            'seats'       => PluginVehicleschedulerInput::int($input, 'seats', 5),
            'is_active'   => PluginVehicleschedulerInput::bool($input, 'is_active', true),
            'required_cnh_category' => PluginVehicleschedulerInput::enum(
                $input,
                'required_cnh_category',
                array_keys(self::getRequiredCNHOptions()),
                self::REQUIRED_CNH_B
            ),
            'comment'     => PluginVehicleschedulerInput::text($input, 'comment', 65535, ''),
        ];
    }

    private static function normalizePlate(string $plate): string
    {
        $plate = mb_strtoupper(trim($plate));
        return preg_replace('/[^A-Z0-9]/', '', $plate) ?? '';
    }

    private static function isPlateAlreadyUsed(string $plate, int $current_id = 0): bool
    {
        global $DB;

        if ($plate === '') {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['plate' => $plate]
        ]);

        foreach ($iterator as $row) {
            if ((int) $row['id'] !== $current_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns normalized rows for the custom vehicle management grid.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getManagementGridRows(): array
    {
        global $DB;

        $rows = [];
        $table = (new self())->getTable();
        $requiredLabels = self::getRequiredCNHOptions();

        $iterator = $DB->request([
            'FROM'  => $table,
            'ORDER' => [
                'is_active DESC',
                'name ASC',
                'id ASC',
            ],
        ]);

        foreach ($iterator as $row) {
            $requiredCategory = (string) ($row['required_cnh_category'] ?? self::REQUIRED_CNH_B);
            $comment = trim((string) ($row['comment'] ?? ''));
            $commentExcerpt = $comment;

            if ($commentExcerpt !== '' && function_exists('mb_substr') && function_exists('mb_strlen')) {
                if (mb_strlen($commentExcerpt) > 72) {
                    $commentExcerpt = (string) mb_substr($commentExcerpt, 0, 72) . '...';
                }
            } elseif ($commentExcerpt !== '' && strlen($commentExcerpt) > 72) {
                $commentExcerpt = substr($commentExcerpt, 0, 72) . '...';
            }

            $rows[] = [
                'id'                    => (int) ($row['id'] ?? 0),
                'name'                  => (string) ($row['name'] ?? ''),
                'plate'                 => (string) ($row['plate'] ?? ''),
                'brand'                 => (string) ($row['brand'] ?? ''),
                'model'                 => (string) ($row['model'] ?? ''),
                'year'                  => (int) ($row['year'] ?? 0),
                'seats'                 => (int) ($row['seats'] ?? 0),
                'required_cnh_category' => $requiredCategory,
                'required_cnh_label'    => (string) ($requiredLabels[$requiredCategory] ?? $requiredCategory),
                'comment'               => (string) ($row['comment'] ?? ''),
                'comment_excerpt'       => $commentExcerpt,
                'is_active'             => (int) ($row['is_active'] ?? 0),
                'date_mod'              => (string) ($row['date_mod'] ?? ''),
            ];
        }

        return $rows;
    }
}
