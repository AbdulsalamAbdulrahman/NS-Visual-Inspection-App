<?php

declare(strict_types=1);

namespace App\Models;

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
 * is on or before today. Future rows are "scheduled" and can be cancelled
 * (soft-deleted) until they take effect.
 *
 * @property int $id
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
        'amount_kobo',
        'effective_from',
        'reason',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
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
     * The schedule in effect on the given day (today by default).
     */
    public static function current(?CarbonInterface $on = null): ?self
    {
        return static::query()
            ->whereDate('effective_from', '<=', ($on ?? today())->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public static function currentAmountKobo(): ?int
    {
        return static::current()?->amount_kobo;
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
