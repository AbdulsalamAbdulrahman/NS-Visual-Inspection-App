<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewAction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step in an inspection's review history (submitted, changes requested,
 * resubmitted, approved). Append-only.
 *
 * @property int $id
 * @property int $inspection_id
 * @property int|null $user_id
 * @property ReviewAction $action
 * @property string|null $note
 * @property CarbonImmutable $created_at
 * @property-read User|null $user
 */
class InspectionReview extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'note',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'action' => ReviewAction::class,
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
