<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NemsaCategory;
use App\Enums\NigerianState;
use Database\Factories\ContractorProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Licence details for a contractor. NSD admins manage the licence fields; the
 * contractor uploads their documents (licence copy, passport photo,
 * signature) and the licence expiry on their Profile.
 *
 * @property int $id
 * @property int $user_id
 * @property NemsaCategory $nemsa_category
 * @property string $nemsa_reg_no
 * @property string|null $coren_no
 * @property string|null $firm_name
 * @property NigerianState|null $state
 * @property string|null $engineer_name
 * @property string|null $engineer_reg_no
 * @property string|null $licence_path
 * @property string|null $passport_path
 * @property string|null $signature_path
 * @property Carbon|null $licence_issued_at
 * @property Carbon|null $licence_expires_at
 * @property Carbon|null $licence_reminded_at
 * @property Carbon|null $licence_expired_notified_at
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
        'state',
        'engineer_name',
        'engineer_reg_no',
        'licence_path',
        'passport_path',
        'signature_path',
        'licence_issued_at',
        'licence_expires_at',
        'licence_reminded_at',
        'licence_expired_notified_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'state' => NigerianState::class,
        'nemsa_category' => NemsaCategory::class,
        'licence_issued_at' => 'date',
        'licence_expires_at' => 'date',
        'licence_reminded_at' => 'datetime',
        'licence_expired_notified_at' => 'datetime',
    ];

    /** Documents a contractor uploads, keyed as in routes and the UI. */
    public const array DOCUMENTS = ['licence', 'passport', 'signature'];

    /** Days before expiry when the reminder email goes. */
    public const int REMINDER_DAYS = 30;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Past its expiry date (valid through the expiry day itself). No date yet = not expired. */
    public function isLicenceExpired(): bool
    {
        return $this->licence_expires_at !== null && $this->licence_expires_at->lt(today());
    }

    /**
     * What the contractor still has to upload.
     *
     * @return list<string> labels, e.g. ['Licence copy', 'Licence expiry date']
     */
    public function documentsMissing(): array
    {
        return array_values(array_filter([
            $this->licence_path ? null : 'Licence copy',
            $this->licence_issued_at ? null : 'Licence issue date',
            $this->licence_expires_at ? null : 'Licence expiry date',
            $this->passport_path ? null : 'Passport photograph',
            $this->signature_path ? null : 'Signature',
        ]));
    }

    public function documentPath(string $document): ?string
    {
        return match ($document) {
            'licence' => $this->licence_path,
            'passport' => $this->passport_path,
            'signature' => $this->signature_path,
            default => null,
        };
    }

    /** Every document and both licence dates are in: the contractor can take jobs. */
    public function isOnboarded(): bool
    {
        return $this->documentsMissing() === [];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOnboarded(Builder $query): void
    {
        $query->whereNotNull(['licence_path', 'passport_path', 'signature_path', 'licence_issued_at', 'licence_expires_at']);
    }

    /**
     * Fields for new licence dates (Y-m-d or null); a changed expiry means new reminder emails.
     *
     * @return array<string, string|null>
     */
    public function licenceDateFields(?string $issuedAt, ?string $expiresAt): array
    {
        $fields = ['licence_issued_at' => $issuedAt, 'licence_expires_at' => $expiresAt];

        if ($this->licence_expires_at?->toDateString() !== $expiresAt) {
            $fields['licence_reminded_at'] = null;
            $fields['licence_expired_notified_at'] = null;
        }

        return $fields;
    }

    /**
     * May take new work: no expiry recorded yet, or not past it.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLicensed(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('licence_expires_at')->orWhereDate('licence_expires_at', '>=', today()));
    }
}
