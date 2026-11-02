<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who shares each inspection fee. Admin & KE HQ is Monnify's main account, so
 * it needs no sub-account and takes whatever the others don't.
 */
enum SettlementParty: string
{
    case Inspector = 'inspector';
    case Lecan = 'lecan';
    case Reviewers = 'reviewers';
    case AdminHq = 'admin_hq';
    case AppTeam = 'app_team';
    case Nemsa = 'nemsa';
    case Reps = 'reps';

    public function label(): string
    {
        return match ($this) {
            self::Inspector => 'Building Electrical Inspector',
            self::Lecan => 'LECAN State Offices',
            self::Reviewers => 'Inspection Review Committee',
            self::AdminHq => 'Admin & KE HQ team',
            self::AppTeam => 'App development team',
            self::Nemsa => 'NEMSA field offices',
            self::Reps => 'New Service Reps (monthly imprest)',
        };
    }
}
