<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\FeeScheduleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The inspection fee in effect is the latest schedule whose effective_from
 * is on or before today. Fees are per structure and per building type; a row
 * with no building type is the default for types without a fee of their own. Future rows are "scheduled" and can be cancelled
 * (soft-deleted) until they take effect.
 *
 * @property int $id
 * @property BuildingType|null $building_type
 * @property int $amount_kobo
 * @property CarbonImmutable $effective_from
 * @property string|null $reason
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property-read User|null $creator
 */
class FeeSchedule extends Model
{
    /** @use HasFactory<FeeScheduleFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'building_type',
        'amount_kobo',
        'effective_from',
        'reason',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'building_type' => BuildingType::class,
        'amount_kobo' => 'integer',
        'effective_from' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * The default fee in effect on the given day (today by default).
     */
    public static function current(?CarbonInterface $on = null): ?self
    {
        return self::inEffect(null, $on);
    }

    /**
     * The fee for a building type: its own if set, otherwise the default.
     */
    public static function forType(?BuildingType $type, ?CarbonInterface $on = null): ?self
    {
        return ($type ? self::inEffect($type, $on) : null) ?? self::inEffect(null, $on);
    }

    public static function currentAmountKobo(?BuildingType $type = null): ?int
    {
        return static::forType($type)?->amount_kobo;
    }

    private static function inEffect(?BuildingType $type, ?CarbonInterface $on): ?self
    {
        return static::query()
            ->where('building_type', $type)
            ->whereDate('effective_from', '<=', ($on ?? today())->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  Builder<FeeSchedule>  $query
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->whereDate('effective_from', '>', today()->toDateString());
    }

    public function isScheduled(): bool
    {
        return $this->effective_from->isAfter(today());
    }
}
