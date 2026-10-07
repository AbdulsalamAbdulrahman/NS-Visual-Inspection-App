<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\NemsaCategory;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * People and sample data from the designs. Every demo account signs in with
 * the password "Password-123". Never runs in production.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Password-123';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder must not run in production.');
        }

        $this->call(DatabaseSeeder::class);

        $this->user('Hadiza Musa', 'h.musa@kadunaelectric.com', Role::Admin, phone: '0803 100 2201');

        $this->contractor('Engr. Yusuf Bello', 'y.bello@bellopower.ng', '0803 412 7790', NemsaCategory::CatA, 'NEMSA/A/2023/0142', 'R.12873', 'Bello Power Systems Ltd');
        $this->contractor('Aisha Lawal', 'aisha.lawal@gmail.com', '0806 220 1145', NemsaCategory::CatB, 'NEMSA/B/2022/0388');
        $this->contractor('Chinedu Okafor', 'c.okafor@okaforelectric.ng', '0809 551 0032', NemsaCategory::CatB, 'NEMSA/B/2021/0217', 'R.20456');
        $this->contractor('Danladi Electrical Services', 'info@danladielectrical.ng', '0802 700 4410', NemsaCategory::Corporate, 'NEMSA/CORP/2020/0051', 'R.09931', 'Danladi Electrical Services Ltd');
        $this->contractor('Emmanuel Gajere', 'e.gajere@gmail.com', '0817 330 9021', NemsaCategory::CatC, 'NEMSA/C/2024/0610', status: UserStatus::Suspended);

        $this->rep('Grace Ayuba', 'g.ayuba@kadunaelectric.com', ['Barnawa', 'Kakuri', 'Sabon Tasha']);
        $this->rep('Abubakar Shehu', 'a.shehu@kadunaelectric.com', ['Kawo', 'Rigasa']);
    }

    private function user(string $name, string $email, Role $role, UserStatus $status = UserStatus::Active, ?string $phone = null): User
    {
        return User::query()->updateOrCreate(['email' => $email], [
            'name' => $name,
            'phone' => $phone,
            'role' => $role,
            'status' => $status,
            'must_change_password' => false,
            'password' => self::PASSWORD,
        ]);
    }

    private function contractor(
        string $name,
        string $email,
        string $phone,
        NemsaCategory $category,
        string $regNo,
        ?string $coren = null,
        ?string $firm = null,
        UserStatus $status = UserStatus::Active,
    ): User {
        $user = $this->user($name, $email, Role::Contractor, $status, $phone);

        $user->contractorProfile()->updateOrCreate([], [
            'nemsa_category' => $category,
            'nemsa_reg_no' => $regNo,
            'coren_no' => $coren,
            'firm_name' => $firm,
        ]);

        return $user;
    }

    /**
     * @param  list<string>  $areas
     */
    private function rep(string $name, string $email, array $areas): User
    {
        $user = $this->user($name, $email, Role::Rep);

        $user->serviceAreas()->sync(ServiceArea::query()->whereIn('name', $areas)->pluck('id'));

        return $user;
    }
}
