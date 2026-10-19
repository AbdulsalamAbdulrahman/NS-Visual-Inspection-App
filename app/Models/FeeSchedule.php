<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
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
 * is on or before today. Fees are per structure, for a cell of the fee sheet:
 * building type × supply (single / three phase) × premises (stand-alone /
 * multiple). ₦0 means the cell is not offered. A building without a cell falls
 * back to its type's own fee, then to the default (no building type). Future
 * rows are "scheduled" and can be cancelled (soft-deleted) until they take effect.
 *
 * @property int $id
 * @property BuildingType|null $building_type
 * @property ConnectionType|null $connection_type
 * @property Premises|null $premises
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
        'connection_type',
        'premises',
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
        'connection_type' => ConnectionType::class,
        'premises' => Premises::class,
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
        return self::inEffect(null, null, null, $on);
    }

    /**
     * The fee for a building type: its own if set, otherwise the default.
     */
    public static function forType(?BuildingType $type, ?CarbonInterface $on = null): ?self
    {
        return ($type ? self::inEffect($type, null, null, $on) : null) ?? self::inEffect(null, null, null, $on);
    }

    /**
     * The fee for a cell of the sheet, else the building type's own, else the default.
     */
    public static function forCell(?BuildingType $type, ?ConnectionType $connection, Premises $premises, ?CarbonInterface $on = null): ?self
    {
        return ($type && $connection ? self::inEffect($type, $connection, $premises, $on) : null) ?? self::forType($type, $on);
    }

    public static function currentAmountKobo(?BuildingType $type = null): ?int
    {
        return static::forType($type)?->amount_kobo;
    }

    /** "Duplex · Three phase · Stand-alone", or "Default". */
    public function cellLabel(): string
    {
        return collect([$this->building_type?->label() ?? 'Default', $this->connection_type?->label(), $this->premises?->label()])
            ->filter()
            ->implode(' · ');
    }

    /** "duplex|three_phase|stand_alone", "duplex" or "default". */
    public function cellKey(): string
    {
        return self::keyFor($this->building_type, $this->connection_type, $this->premises);
    }

    public static function keyFor(?BuildingType $type, ?ConnectionType $connection, ?Premises $premises): string
    {
        return $type === null ? 'default' : implode('|', array_filter([$type->value, $connection?->value, $premises?->value]));
    }

    private static function inEffect(?BuildingType $type, ?ConnectionType $connection, ?Premises $premises, ?CarbonInterface $on): ?self
    {
        return static::query()
            ->where('building_type', $type)
            ->where('connection_type', $connection)
            ->where('premises', $premises)
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
