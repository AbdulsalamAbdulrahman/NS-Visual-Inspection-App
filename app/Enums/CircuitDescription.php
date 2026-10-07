<?php

declare(strict_types=1);

namespace App\Enums;

enum CircuitDescription: string
{
    case Lighting = 'lighting';
    case SocketOutlet = 'socket_outlet';
    case RingMains = 'ring_mains';
    case Cooker = 'cooker';
    case ImmersionHeater = 'immersion_heater';
    case AirConditioner = 'air_conditioner';
    case ElectricalMotor = 'electrical_motor';
    case BoreholePump = 'borehole_pump';
    case OutdoorEquipment = 'outdoor_equipment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Lighting => 'Lighting',
            self::SocketOutlet => 'Socket outlet',
            self::RingMains => 'Ring mains',
            self::Cooker => 'Cooker',
            self::ImmersionHeater => 'Immersion heater',
            self::AirConditioner => 'Air conditioner',
            self::ElectricalMotor => 'Electrical motor',
            self::BoreholePump => 'Borehole pump',
            self::OutdoorEquipment => 'Outdoor equipment',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
