<?php

namespace GlpiPlugin\Vehiclescheduler\Maintenance;

final class WorkOrder
{
    public const ORIGIN_MANUAL = 1;
    public const ORIGIN_INCIDENT = 2;
    public const ORIGIN_CHECKLIST = 3;
    public const ORIGIN_PREVENTIVE = 4;

    public const PRIORITY_LOW = 1;
    public const PRIORITY_NORMAL = 2;
    public const PRIORITY_HIGH = 3;
    public const PRIORITY_CRITICAL = 4;

    public const STATUS_OPEN = 1;
    public const STATUS_ANALYSIS = 2;
    public const STATUS_WORKSHOP_LINKED = 3;
    public const STATUS_DIAGNOSIS = 4;
    public const STATUS_APPROVAL = 5;
    public const STATUS_EXECUTION = 6;
    public const STATUS_CONCLUDED = 7;
    public const STATUS_RELEASED = 8;
    public const STATUS_CANCELLED = 9;

    public const APPROVAL_NOT_REQUIRED = 1;
    public const APPROVAL_PENDING = 2;
    public const APPROVAL_APPROVED = 3;
    public const APPROVAL_REJECTED = 4;

    /**
     * @return array<int, string>
     */
    public static function getOriginLabels(): array
    {
        return [
            self::ORIGIN_MANUAL => __('Manual', 'vehiclescheduler'),
            self::ORIGIN_INCIDENT => __('Incident', 'vehiclescheduler'),
            self::ORIGIN_CHECKLIST => __('Checklist', 'vehiclescheduler'),
            self::ORIGIN_PREVENTIVE => __('Preventive', 'vehiclescheduler'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getPriorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => __('Low', 'vehiclescheduler'),
            self::PRIORITY_NORMAL => __('Normal', 'vehiclescheduler'),
            self::PRIORITY_HIGH => __('High', 'vehiclescheduler'),
            self::PRIORITY_CRITICAL => __('Critical', 'vehiclescheduler'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_OPEN => __('Open', 'vehiclescheduler'),
            self::STATUS_ANALYSIS => __('Analysis', 'vehiclescheduler'),
            self::STATUS_WORKSHOP_LINKED => __('Workshop linked', 'vehiclescheduler'),
            self::STATUS_DIAGNOSIS => __('Diagnosis / estimate', 'vehiclescheduler'),
            self::STATUS_APPROVAL => __('Approval', 'vehiclescheduler'),
            self::STATUS_EXECUTION => __('Execution', 'vehiclescheduler'),
            self::STATUS_CONCLUDED => __('Concluded', 'vehiclescheduler'),
            self::STATUS_RELEASED => __('Vehicle released', 'vehiclescheduler'),
            self::STATUS_CANCELLED => __('Cancelled', 'vehiclescheduler'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getApprovalStatusLabels(): array
    {
        return [
            self::APPROVAL_NOT_REQUIRED => __('Not required', 'vehiclescheduler'),
            self::APPROVAL_PENDING => __('Pending', 'vehiclescheduler'),
            self::APPROVAL_APPROVED => __('Approved', 'vehiclescheduler'),
            self::APPROVAL_REJECTED => __('Rejected', 'vehiclescheduler'),
        ];
    }
}
