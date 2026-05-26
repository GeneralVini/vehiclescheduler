<?php

namespace GlpiPlugin\Vehiclescheduler\Maintenance;

final class Workshop
{
    public const TYPE_INTERNAL = 1;
    public const TYPE_ACCREDITED = 2;

    public const SPECIALTY_MECHANICAL = 'mechanical';
    public const SPECIALTY_ELECTRICAL = 'electrical';
    public const SPECIALTY_BODYWORK = 'bodywork';
    public const SPECIALTY_PAINTING = 'painting';
    public const SPECIALTY_TIRES = 'tires';
    public const SPECIALTY_BRAKES = 'brakes';
    public const SPECIALTY_SUSPENSION = 'suspension';
    public const SPECIALTY_ENGINE = 'engine';
    public const SPECIALTY_TRANSMISSION = 'transmission';
    public const SPECIALTY_AC = 'air_conditioning';
    public const SPECIALTY_DIAGNOSTICS = 'diagnostics';
    public const SPECIALTY_OTHER = 'other';

    /**
     * @return array<int, string>
     */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_INTERNAL => __('Internal', 'vehiclescheduler'),
            self::TYPE_ACCREDITED => __('Accredited', 'vehiclescheduler'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getSpecialtyLabels(): array
    {
        return [
            self::SPECIALTY_MECHANICAL => __('Mechanical', 'vehiclescheduler'),
            self::SPECIALTY_ELECTRICAL => __('Electrical', 'vehiclescheduler'),
            self::SPECIALTY_BODYWORK => __('Bodywork', 'vehiclescheduler'),
            self::SPECIALTY_PAINTING => __('Painting', 'vehiclescheduler'),
            self::SPECIALTY_TIRES => __('Tires', 'vehiclescheduler'),
            self::SPECIALTY_BRAKES => __('Brakes', 'vehiclescheduler'),
            self::SPECIALTY_SUSPENSION => __('Suspension', 'vehiclescheduler'),
            self::SPECIALTY_ENGINE => __('Engine', 'vehiclescheduler'),
            self::SPECIALTY_TRANSMISSION => __('Transmission', 'vehiclescheduler'),
            self::SPECIALTY_AC => __('Air conditioning', 'vehiclescheduler'),
            self::SPECIALTY_DIAGNOSTICS => __('Diagnostics', 'vehiclescheduler'),
            self::SPECIALTY_OTHER => __('Other', 'vehiclescheduler'),
        ];
    }
}
