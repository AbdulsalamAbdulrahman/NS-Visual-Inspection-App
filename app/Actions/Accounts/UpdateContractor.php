<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\NemsaCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateContractor
{
    /**
     * @param  array{name: string, email: string, phone: string|null, nemsa_category: string, nemsa_reg_no: string, coren_no: string|null, firm_name: string|null, engineer_name: string|null, engineer_reg_no: string|null, state: string, licence_issued_at: string|null, licence_expires_at: string|null}  $data
     */
    public function handle(User $contractor, array $data): User
    {
        DB::transaction(function () use ($contractor, $data): void {
            $contractor->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $profile = $contractor->contractorProfile()->firstOrNew();

            $profile->fill([
                'nemsa_category' => $data['nemsa_category'],
                'nemsa_reg_no' => $data['nemsa_reg_no'],
                'coren_no' => $data['coren_no'],
                'firm_name' => $data['firm_name'],
                'state' => $data['state'],
                // Only corporate firms name an engineer/technologist.
                'engineer_name' => $data['nemsa_category'] === NemsaCategory::Corporate->value ? $data['engineer_name'] : null,
                'engineer_reg_no' => $data['nemsa_category'] === NemsaCategory::Corporate->value ? $data['engineer_reg_no'] : null,
                ...$profile->licenceDateFields($data['licence_issued_at'], $data['licence_expires_at']),
            ])->save();
        });

        return $contractor->refresh();
    }
}
