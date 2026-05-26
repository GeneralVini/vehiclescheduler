<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerChecklist extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler';

    public const TYPE_DEPARTURE = 1;
    public const TYPE_ARRIVAL = 2;
    public const TYPE_BOTH = 3;

    public static function getTypeName($nb = 0)
    {
        return ($nb === 1) ? __('Checklist', 'vehiclescheduler') : __('Checklists', 'vehiclescheduler');
    }

    public static function getIcon()
    {
        return 'ti ti-checkbox';
    }

    public static function getChecklistTypes(): array
    {
        return [
            self::TYPE_DEPARTURE => __('Departure', 'vehiclescheduler'),
            self::TYPE_ARRIVAL   => __('Arrival', 'vehiclescheduler'),
            self::TYPE_BOTH      => __('Both', 'vehiclescheduler'),
        ];
    }

    public static function getActiveChecklistForType(int $type): ?array
    {
        global $DB;

        foreach (
            $DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => [
                    'is_active' => 1,
                    'OR'        => [
                        ['checklist_type' => $type],
                        ['checklist_type' => self::TYPE_BOTH],
                    ],
                ],
                'ORDER' => ['id ASC'],
                'LIMIT' => 1,
            ]) as $row
        ) {
            return $row;
        }

        return null;
    }

    public static function getChecklistResponseUrl(int $scheduleId, string $type = 'departure'): string
    {
        return plugin_vehiclescheduler_get_front_url('checklistresponse.form.php')
            . '?schedule_id=' . $scheduleId
            . '&type=' . ($type === 'arrival' ? 'arrival' : 'departure');
    }

    public static function maybeOpenDepartureChecklistAfterApproval(PluginVehicleschedulerSchedule $schedule): void
    {
        if (!PluginVehicleschedulerConfig::shouldAutoOpenDepartureChecklistAfterApproval()) {
            return;
        }

        $scheduleId = (int) $schedule->fields['id'];
        $checklist = self::getActiveChecklistForType(self::TYPE_DEPARTURE);
        if (!$checklist) {
            return;
        }

        $followup = new ITILFollowup();
        $ticketId = (int) ($schedule->fields['tickets_id'] ?? 0);
        if ($ticketId <= 0) {
            return;
        }

        $followup->add([
            'itemtype'   => 'Ticket',
            'items_id'   => $ticketId,
            'users_id'   => (int) Session::getLoginUserID(),
            'content'    => "📋 CHECKLIST DE SAÍDA DISPONÍVEL\n\n"
                . __('The reservation was approved and the first operational checklist can now be completed.', 'vehiclescheduler') . "\n\n"
                . __('Access:', 'vehiclescheduler') . ' ' . self::getChecklistResponseUrl($scheduleId, 'departure') . "\n\n"
                . __('Checklist:', 'vehiclescheduler') . ' ' . (string) ($checklist['name'] ?? __('Departure checklist', 'vehiclescheduler')),
            'is_private' => 0,
        ]);
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeInput($input);

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__('Name is required.', 'vehiclescheduler'), false, ERROR);

            return false;
        }

        if (!isset($input['entities_id']) || (int) $input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if (empty($input['id'])) {
            Session::addMessageAfterRedirect(__('Invalid checklist.', 'vehiclescheduler'), false, ERROR);

            return false;
        }

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect(__('Name is required.', 'vehiclescheduler'), false, ERROR);

            return false;
        }

        return $input;
    }

    public function rawSearchOptions(): array
    {
        $tab = [];

        $tab[] = ['id' => 'common', 'name' => __('Checklists', 'vehiclescheduler')];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Name', 'vehiclescheduler'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => $this->getTable(),
            'field'    => 'checklist_type',
            'name'     => __('Type', 'vehiclescheduler'),
            'datatype' => 'specific',
        ];

        $tab[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active', 'vehiclescheduler'),
            'datatype' => 'bool',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'checklist_type') {
            $types = self::getChecklistTypes();

            return $types[(int) ($values[$field] ?? 0)] ?? '';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeInput(array $input): array
    {
        $input['name'] = PluginVehicleschedulerInput::string($input, 'name', 255);
        $input['description'] = PluginVehicleschedulerInput::text($input, 'description', 2000);
        $input['checklist_type'] = PluginVehicleschedulerInput::int(
            $input,
            'checklist_type',
            self::TYPE_DEPARTURE,
            self::TYPE_DEPARTURE,
            self::TYPE_BOTH
        );
        $input['is_active'] = PluginVehicleschedulerInput::bool($input, 'is_active', true);
        $input['is_mandatory'] = PluginVehicleschedulerInput::bool($input, 'is_mandatory', true);

        if (array_key_exists('id', $input)) {
            $input['id'] = PluginVehicleschedulerInput::int($input, 'id', 0, 0);
        }

        if (array_key_exists('entities_id', $input)) {
            $input['entities_id'] = PluginVehicleschedulerInput::int($input, 'entities_id', 0, 0);
        }

        return $input;
    }
}
