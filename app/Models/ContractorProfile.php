<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NemsaCategory;
use Database\Factories\ContractorProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Licence details for a contractor; managed by NSD admins only.
 *
 * @property int $id
 * @property int $user_id
 * @property NemsaCategory $nemsa_category
 * @property string $nemsa_reg_no
 * @property string|null $coren_no
 * @property string|null $firm_name
 * @property-read User $user
 */
class ContractorProfile extends Model
{
    /** @use HasFactory<ContractorProfileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nemsa_category',
        'nemsa_reg_no',
        'coren_no',
        'firm_name',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'nemsa_category' => NemsaCategory::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
