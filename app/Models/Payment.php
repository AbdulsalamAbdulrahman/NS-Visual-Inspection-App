<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Monnify checkout attempt for an inspection fee.
 *
 * @property int $id
 * @property int $inspection_id
 * @property int $contractor_id
 * @property string $payment_reference
 * @property string|null $transaction_reference
 * @property int $amount_kobo
 * @property int|null $amount_paid_kobo
 * @property string|null $channel
 * @property PaymentStatus $status
 * @property CarbonImmutable|null $paid_at
 * @property array<string, mixed>|null $gateway_payload
 * @property CarbonImmutable|null $created_at
 * @property-read Inspection $inspection
 * @property-read User $contractor
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inspection_id',
        'contractor_id',
        'payment_reference',
        'transaction_reference',
        'amount_kobo',
        'amount_paid_kobo',
        'channel',
        'status',
        'paid_at',
        'gateway_payload',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount_kobo' => 'integer',
        'amount_paid_kobo' => 'integer',
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'payment_reference';
    }

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
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id')->withTrashed();
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    /** "Card", "Transfer", "USSD" from Monnify's paymentMethod. */
    public function channelLabel(): ?string
    {
        return match ($this->channel) {
            null => null,
            'CARD' => 'Card',
            'ACCOUNT_TRANSFER' => 'Transfer',
            'USSD' => 'USSD',
            'PHONE_NUMBER' => 'Phone',
            default => ucwords(strtolower(str_replace('_', ' ', $this->channel))),
        };
    }
}
