<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettlementParty as Party;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One party's share of a payment, fixed when the checkout opens, so later
 * ratio changes never alter past payments.
 *
 * @property int $id
 * @property int $payment_id
 * @property Party $party
 * @property int|null $contractor_id
 * @property int $percentage_bp
 * @property int $amount_kobo
 * @property string|null $sub_account_code
 * @property-read Payment $payment
 */
class PaymentSplit extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['payment_id', 'party', 'contractor_id', 'percentage_bp', 'amount_kobo', 'sub_account_code'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'party' => Party::class,
        'percentage_bp' => 'integer',
        'amount_kobo' => 'integer',
    ];

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
