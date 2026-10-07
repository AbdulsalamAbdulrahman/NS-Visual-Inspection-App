<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateContractor
{
    /**
     * @param  array{name: string, email: string, phone: string|null, nemsa_category: string, nemsa_reg_no: string, coren_no: string|null, firm_name: string|null}  $data
     */
    public function handle(User $contractor, array $data): User
    {
        DB::transaction(function () use ($contractor, $data): void {
            $contractor->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $contractor->contractorProfile()->updateOrCreate([], [
                'nemsa_category' => $data['nemsa_category'],
                'nemsa_reg_no' => $data['nemsa_reg_no'],
                'coren_no' => $data['coren_no'],
                'firm_name' => $data['firm_name'],
            ]);
        });

        return $contractor->refresh();
    }
}
