<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Role $role
 * @property UserStatus $status
 * @property bool $must_change_password
 * @property Carbon|null $last_active_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ContractorProfile|null $contractorProfile
 * @property-read Collection<int, ServiceArea> $serviceAreas
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'status',
        'must_change_password',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'role' => Role::class,
        'status' => UserStatus::class,
        'must_change_password' => 'boolean',
        'last_active_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Only the public uuid is generated; the integer id stays the primary key.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return HasOne<ContractorProfile, $this>
     */
    public function contractorProfile(): HasOne
    {
        return $this->hasOne(ContractorProfile::class);
    }

    /**
     * Areas a service rep may view (1–3).
     *
     * @return BelongsToMany<ServiceArea, $this>
     */
    public function serviceAreas(): BelongsToMany
    {
        return $this->belongsToMany(ServiceArea::class, 'rep_service_area')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isContractor(): bool
    {
        return $this->role === Role::Contractor;
    }

    public function isRep(): bool
    {
        return $this->role === Role::Rep;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /**
     * @param  Builder<User>  $query
     */
    public function scopeRole(Builder $query, Role $role): void
    {
        $query->where('role', $role);
    }

    /**
     * Two-letter initials, skipping honorifics ("Engr. Yusuf Bello" → "YB").
     */
    public function initials(): string
    {
        $words = $this->nameWords();

        if ($words === []) {
            return '';
        }

        $first = mb_substr($words[0], 0, 1);
        $last = count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    /**
     * First name for greetings ("Welcome, Yusuf."), skipping honorifics.
     */
    public function firstName(): string
    {
        return $this->nameWords()[0] ?? $this->name;
    }

    /**
     * @return list<string>
     */
    private function nameWords(): array
    {
        $honorifics = ['engr', 'dr', 'mr', 'mrs', 'ms', 'alhaji', 'hajiya', 'mallam', 'chief', 'prof'];

        return array_values(array_filter(
            preg_split('/\s+/u', trim($this->name)) ?: [],
            fn (string $word): bool => $word !== '' && ! in_array(mb_strtolower(rtrim($word, '.')), $honorifics, true),
        ));
    }
}
