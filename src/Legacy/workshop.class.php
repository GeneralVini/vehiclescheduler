<?php

use GlpiPlugin\Vehiclescheduler\Maintenance\Workshop;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerWorkshop extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler_management';

    public static function getTypeName($nb = 0)
    {
        return _n('Workshop', 'Workshops', $nb, 'vehiclescheduler');
    }

    public static function getIcon()
    {
        return 'ti ti-building-store';
    }

    public static function getAllTypes(): array
    {
        return Workshop::getTypeLabels();
    }

    public static function getAllSpecialties(): array
    {
        return Workshop::getSpecialtyLabels();
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeInput($input);

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__('Workshop name is required.', 'vehiclescheduler'), false, ERROR);

            return false;
        }

        if ((int) ($input['entities_id'] ?? 0) <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect(__('Invalid workshop.', 'vehiclescheduler'), false, ERROR);

            return false;
        }

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__('Workshop name is required.', 'vehiclescheduler'), false, ERROR);

            return false;
        }

        return $input;
    }

    public function rawSearchOptions(): array
    {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __('ID', 'vehiclescheduler'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => $this->getTable(),
            'field'    => 'name',
            'name'     => __('Name'),
            'datatype' => 'itemlink',
        ];

        $tab[] = [
            'id'         => '3',
            'table'      => $this->getTable(),
            'field'      => 'type',
            'name'       => __('Type', 'vehiclescheduler'),
            'datatype'   => 'specific',
            'searchtype' => ['equals'],
        ];

        $tab[] = [
            'id'         => '4',
            'table'      => $this->getTable(),
            'field'      => 'is_active',
            'name'       => __('Active', 'vehiclescheduler'),
            'datatype'   => 'bool',
            'searchtype' => ['equals'],
        ];

        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'city',
            'name'     => __('City', 'vehiclescheduler'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'state',
            'name'     => __('State', 'vehiclescheduler'),
            'datatype' => 'string',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'type') {
            return self::getAllTypes()[(int) ($values[$field] ?? 0)] ?? '';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeInput(array $input): array
    {
        $input['name'] = PluginVehicleschedulerInput::string($input, 'name', 255);
        $input['entities_id'] = PluginVehicleschedulerInput::int($input, 'entities_id', 0, 0);
        $input['is_recursive'] = PluginVehicleschedulerInput::bool($input, 'is_recursive', false) ? 1 : 0;
        $input['type'] = PluginVehicleschedulerInput::int($input, 'type', Workshop::TYPE_INTERNAL, Workshop::TYPE_INTERNAL, Workshop::TYPE_ACCREDITED);
        $input['document'] = PluginVehicleschedulerInput::string($input, 'document', 50);
        $input['phone'] = PluginVehicleschedulerInput::string($input, 'phone', 50);
        $input['email'] = PluginVehicleschedulerInput::string($input, 'email', 255);
        $input['city'] = PluginVehicleschedulerInput::string($input, 'city', 100);
        $input['state'] = strtoupper(PluginVehicleschedulerInput::string($input, 'state', 2));
        $input['is_active'] = PluginVehicleschedulerInput::bool($input, 'is_active', true) ? 1 : 0;
        $input['specialties'] = $this->normalizeSpecialties($input);
        $input['comment'] = PluginVehicleschedulerInput::text($input, 'comment', 5000);

        if (array_key_exists('id', $input)) {
            $input['id'] = PluginVehicleschedulerInput::int($input, 'id', 0, 0);
        }

        return $input;
    }

    private function normalizeSpecialties(array $input): string
    {
        $allowed = array_keys(Workshop::getSpecialtyLabels());
        $specialties = PluginVehicleschedulerInput::enumList($input, 'specialties', $allowed);

        return implode(',', $specialties);
    }
}
