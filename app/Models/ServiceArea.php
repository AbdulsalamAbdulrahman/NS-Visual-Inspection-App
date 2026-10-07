<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ServiceAreaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_active
 */
class ServiceArea extends Model
{
    /** @use HasFactory<ServiceAreaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function reps(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rep_service_area')->withTimestamps();
    }

    /**
     * @return HasMany<Inspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * @param  Builder<ServiceArea>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
