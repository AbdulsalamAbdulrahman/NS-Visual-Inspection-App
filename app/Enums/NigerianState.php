<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * States in Kaduna Electric's franchise. Each area office belongs to one; it
 * names the LECAN state chapter on certificates.
 */
enum NigerianState: string
{
    case Kaduna = 'kaduna';
    case Kebbi = 'kebbi';
    case Sokoto = 'sokoto';
    case Zamfara = 'zamfara';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
