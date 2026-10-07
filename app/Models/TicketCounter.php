<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $year
 * @property int $last_number
 */
class TicketCounter extends Model
{
    protected $primaryKey = 'year';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'year',
        'last_number',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];
}
