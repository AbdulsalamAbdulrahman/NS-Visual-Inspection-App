<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettlementParty as Party;
use Illuminate\Database\Eloquent\Model;

/**
 * A share of each fee and the account it settles to. The inspector row holds
 * only the rate: each inspector has their own sub-account on their profile.
 *
 * @property int $id
 * @property Party $key
 * @property int $percentage_bp
 * @property string|null $bank_code
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property string|null $account_name
 * @property string|null $sub_account_code
 */
class SettlementParty extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['percentage_bp', 'bank_code', 'bank_name', 'account_number', 'account_name', 'sub_account_code'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'key' => Party::class,
        'percentage_bp' => 'integer',
    ];

    public function hasOwnAccount(): bool
    {
        return $this->key !== Party::Inspector && $this->key !== Party::AdminHq;
    }
}
