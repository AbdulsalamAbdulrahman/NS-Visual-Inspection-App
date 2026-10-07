<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Contractor = 'contractor';
    case Rep = 'rep';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'NSD Admin',
            self::Contractor => 'Contractor',
            self::Rep => 'Service rep',
        };
    }

    /** Named route of the role's landing page. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.overview',
            self::Contractor => 'inspections.index',
            self::Rep => 'rep.inspections.index',
        };
    }
}
