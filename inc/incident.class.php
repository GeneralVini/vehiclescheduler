<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerIncident extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler';

    public const TYPE_ACCIDENT = 1;
    public const TYPE_BREAKDOWN = 2;
    public const TYPE_THEFT = 3;
    public const TYPE_DAMAGE = 4;
    public const TYPE_OBSERVATION = 5;
    public const TYPE_OTHER = 6;

    public const STATUS_OPEN = 1;
    public const STATUS_ANALYZING = 2;
    public const STATUS_RESOLVED = 3;
    public const STATUS_CLOSED = 4;

    public static function getTypeName($nb = 0)
    {
        return _n('Incident', 'Incidents', $nb, 'vehiclescheduler');
    }

    public static function getMenuName()
    {
        return __('Incidents', 'vehiclescheduler');
    }

    public static function getIcon()
    {
        return 'ti ti-alert-triangle';
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }

        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = '/plugins/vehiclescheduler/front/incident.php';
        $menu['icon'] = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/incident.php';

        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/incident.form.php';
        }

        $menu['options']['incident'] = [
            'title'          => self::getTypeName(2),
            'page'           => '/plugins/vehiclescheduler/front/incident.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/incident.php',
                'add'    => '/plugins/vehiclescheduler/front/incident.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerIncident',
        ];

        return $menu;
    }

    public static function getAllTypes(): array
    {
        return [
            self::TYPE_ACCIDENT    => 'Acidente',
            self::TYPE_BREAKDOWN   => 'Pane/Falha',
            self::TYPE_THEFT       => 'Roubo/Furto',
            self::TYPE_DAMAGE      => 'Dano/Avaria',
            self::TYPE_OBSERVATION => 'Observacao',
            self::TYPE_OTHER       => 'Outro',
        ];
    }

    public static function getAllStatus(): array
    {
        return [
            self::STATUS_OPEN      => 'Aberto',
            self::STATUS_ANALYZING => 'Analisando',
            self::STATUS_RESOLVED  => 'Resolvido',
            self::STATUS_CLOSED    => 'Fechado',
        ];
    }

    public function defineTabs($options = []): array
    {
        $tabs = [];
        $this->addDefaultFormTab($tabs);
        $this->addStandardTab('Log', $tabs, $options);

        return $tabs;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Veiculo e obrigatorio.', false, ERROR);

            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('Descricao e obrigatoria.', false, ERROR);

            return false;
        }

        if ($input['name'] === '') {
            $types = self::getAllTypes();
            $typeLabel = $types[(int) ($input['incident_type'] ?? self::TYPE_OTHER)] ?? 'Incidente';
            $input['name'] = $typeLabel . ' - ' . date('d/m/Y');
        }

        if (!isset($input['entities_id']) || (int) $input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        if (!isset($input['users_id']) || (int) $input['users_id'] <= 0) {
            $input['users_id'] = (int) Session::getLoginUserID();
        }

        if ($input['incident_date'] === '') {
            $input['incident_date'] = date('Y-m-d H:i:s');
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Incidente invalido.', false, ERROR);

            return false;
        }

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Veiculo e obrigatorio.', false, ERROR);

            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('Descricao e obrigatoria.', false, ERROR);

            return false;
        }

        return $input;
    }

    public function post_addItem()
    {
        parent::post_addItem();
        $ticketId = $this->createTicketFromIncident();

        if ($ticketId) {
            $_SESSION['vehiclescheduler_created_ticket_id'] = $ticketId;
        }
    }

    public function createTicketFromIncident()
    {
        $vehicle = new PluginVehicleschedulerVehicle();
        $vehicleName = '';

        if ($vehicle->getFromDB((int) $this->fields['plugin_vehiclescheduler_vehicles_id'])) {
            $vehicleName = (string) ($vehicle->fields['name'] ?? '')
                . ' ('
                . (string) ($vehicle->fields['plate'] ?? '')
                . ')';
        }

        $types = self::getAllTypes();
        $typeLabel = $types[(int) ($this->fields['incident_type'] ?? self::TYPE_OTHER)] ?? 'Incidente';

        $title = 'Incidente com Veiculo: ' . $vehicleName . ' - ' . $typeLabel;

        $content = "Reporte de Incidente:\n\n"
            . 'Tipo: ' . $typeLabel . "\n"
            . 'Veiculo: ' . $vehicleName . "\n"
            . 'Data: ' . Html::convDateTime((string) ($this->fields['incident_date'] ?? '')) . "\n"
            . 'Local: ' . (string) ($this->fields['location'] ?? '') . "\n"
            . 'Relatado por: ' . getUserName((int) ($this->fields['users_id'] ?? 0)) . "\n"
            . 'Telefone: ' . (string) ($this->fields['contact_phone'] ?? '') . "\n\n"
            . "Descricao:\n"
            . (string) ($this->fields['description'] ?? '');

        $ticket = new Ticket();

        return $ticket->add([
            'name'                => $title,
            'content'             => $content,
            'entities_id'         => (int) ($this->fields['entities_id'] ?? 0),
            'type'                => Ticket::INCIDENT_TYPE,
            'urgency'             => 4,
            'impact'              => 3,
            'priority'            => CommonITILObject::computePriority(4, 3),
            '_users_id_requester' => (int) ($this->fields['users_id'] ?? 0),
        ]);
    }

    public function rawSearchOptions(): array
    {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];
        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => 'Titulo',
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];
        $tab[] = [
            'id'       => '2',
            'table'    => 'glpi_plugin_vehiclescheduler_vehicles',
            'field'    => 'name',
            'name'     => 'Veiculo',
            'datatype' => 'dropdown',
        ];
        $tab[] = [
            'id'         => '3',
            'table'      => $this->getTable(),
            'field'      => 'incident_type',
            'name'       => 'Tipo',
            'datatype'   => 'specific',
            'searchtype' => ['equals'],
        ];
        $tab[] = [
            'id'         => '4',
            'table'      => $this->getTable(),
            'field'      => 'status',
            'name'       => 'Status',
            'datatype'   => 'specific',
            'searchtype' => ['equals'],
        ];
        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'incident_date',
            'name'     => 'Data',
            'datatype' => 'datetime',
        ];
        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'groups_id',
            'name'     => 'Grupo',
            'datatype' => 'dropdown',
            'itemtype' => 'Group',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'incident_type') {
            return self::getAllTypes()[(int) ($values[$field] ?? 0)] ?? '';
        }

        if ($field === 'status') {
            return self::getAllStatus()[(int) ($values[$field] ?? 0)] ?? '';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeInput(array $input): array
    {
        $input['name'] = PluginVehicleschedulerInput::string($input, 'name', 255);
        $input['users_id'] = PluginVehicleschedulerInput::int($input, 'users_id', 0, 0);
        $input['groups_id'] = PluginVehicleschedulerInput::int($input, 'groups_id', 0, 0);
        $input['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_vehicles_id',
            0,
            0
        );
        $input['plugin_vehiclescheduler_drivers_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_drivers_id',
            0,
            0
        );
        $input['incident_type'] = PluginVehicleschedulerInput::int(
            $input,
            'incident_type',
            self::TYPE_OTHER,
            self::TYPE_ACCIDENT,
            self::TYPE_OTHER
        );
        $input['status'] = PluginVehicleschedulerInput::int(
            $input,
            'status',
            self::STATUS_OPEN,
            self::STATUS_OPEN,
            self::STATUS_CLOSED
        );
        $input['needs_maintenance'] = PluginVehicleschedulerInput::bool($input, 'needs_maintenance', false);
        $input['needs_insurance'] = PluginVehicleschedulerInput::bool($input, 'needs_insurance', false);
        $input['location'] = PluginVehicleschedulerInput::string($input, 'location', 255);
        $input['contact_phone'] = PluginVehicleschedulerInput::string($input, 'contact_phone', 20);
        $input['description'] = PluginVehicleschedulerInput::text($input, 'description', 5000);
        $input['incident_date'] = $this->normalizeIncidentDate((string) ($input['incident_date'] ?? ''));

        if (array_key_exists('id', $input)) {
            $input['id'] = PluginVehicleschedulerInput::int($input, 'id', 0, 0);
        }

        if (array_key_exists('entities_id', $input)) {
            $input['entities_id'] = PluginVehicleschedulerInput::int($input, 'entities_id', 0, 0);
        }

        return $input;
    }

    private function normalizeIncidentDate(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        $timestamp = strtotime($trimmed);

        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
