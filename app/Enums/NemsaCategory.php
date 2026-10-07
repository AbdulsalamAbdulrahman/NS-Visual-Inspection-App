<?php

declare(strict_types=1);

namespace App\Enums;

enum NemsaCategory: string
{
    case CatA = 'cat_a';
    case CatB = 'cat_b';
    case CatC = 'cat_c';
    case Corporate = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::CatA => 'Cat A',
            self::CatB => 'Cat B',
            self::CatC => 'Cat C',
            self::Corporate => 'Corporate',
        };
    }

    /** Letter used in registration numbers like NEMSA/A/2023/0142. */
    public function letter(): string
    {
        return match ($this) {
            self::CatA => 'A',
            self::CatB => 'B',
            self::CatC => 'C',
            self::Corporate => 'CORP',
        };
    }
}
