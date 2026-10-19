<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * "Description of building" (client spreadsheet, Oct 2026), grouped by the use
 * of property. Each type can carry its own inspection fee, charged per structure.
 */
enum BuildingType: string
{
    // Residential
    case SingleRoom = 'single_room';
    case TwoBedroomFlat = 'two_bedroom_flat';
    case ThreeBedroomFlat = 'three_bedroom_flat';
    case Duplex = 'duplex';
    case BlockOfFlats = 'block_of_flats';
    case BoysQuarters = 'boys_quarters';
    case MixedStructure = 'mixed_structure';
    // Commercial
    case Shop = 'shop';
    case Plaza = 'plaza';
    case LargePlaza = 'large_plaza';
    case FillingStation = 'filling_station';
    case Market = 'market';
    // Industrial
    case SmallFactory = 'small_factory';
    case MediumFactory = 'medium_factory';
    case LargeFactory = 'large_factory';
    case Fabricators = 'fabricators';
    // Government
    case Mda = 'mda';
    case Institution = 'institution';
    case Offices = 'offices';
    // Essential service
    case Hospital = 'hospital';
    case School = 'school';
    case WorshipCentre = 'worship_centre';

    public function label(): string
    {
        return match ($this) {
            self::SingleRoom => 'Single room',
            self::TwoBedroomFlat => '2-bedroom flat',
            self::ThreeBedroomFlat => '3-bedroom flat',
            self::Duplex => 'Duplex',
            self::BlockOfFlats => 'Block of flats',
            self::BoysQuarters => "Boys' quarters",
            self::MixedStructure => 'Mixed structure',
            self::Shop => 'Shop',
            self::Plaza => 'Plaza (up to 15 shops)',
            self::LargePlaza => 'Plaza (above 15 shops)',
            self::FillingStation => 'Filling station',
            self::Market => 'Market',
            self::SmallFactory => 'Small factory',
            self::MediumFactory => 'Medium factory',
            self::LargeFactory => 'Large factory',
            self::Fabricators => 'Fabricators',
            self::Mda => 'MDA',
            self::Institution => 'College / institution',
            self::Offices => 'Offices',
            self::Hospital => 'Hospital',
            self::School => 'School',
            self::WorshipCentre => 'Worship centre',
        };
    }

    public function purpose(): PropertyPurpose
    {
        return match ($this) {
            self::SingleRoom, self::TwoBedroomFlat, self::ThreeBedroomFlat, self::Duplex,
            self::BlockOfFlats, self::BoysQuarters, self::MixedStructure => PropertyPurpose::Residential,
            self::Shop, self::Plaza, self::LargePlaza, self::FillingStation, self::Market => PropertyPurpose::Commercial,
            self::SmallFactory, self::MediumFactory, self::LargeFactory, self::Fabricators => PropertyPurpose::Industrial,
            self::Mda, self::Institution, self::Offices => PropertyPurpose::Government,
            self::Hospital, self::School, self::WorshipCentre => PropertyPurpose::EssentialService,
        };
    }

    /**
     * @return list<array{value: string, label: string, purpose: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'purpose' => $case->purpose()->value,
        ], self::cases());
    }
}
