<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Payments\GenerateTicket;
use App\Enums\NemsaCategory;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $this->contractor('Danladi Electrical Services', 'info@danladielectrical.ng', '0802 700 4410', NemsaCategory::Corporate, 'NEMSA/CP/2022/0051', 'R.09931', 'Danladi Electrical Services Ltd');
        $this->contractor('Emmanuel Gajere', 'e.gajere@gmail.com', '0817 330 9021', NemsaCategory::CatC, 'NEMSA/C/2024/0610', status: UserStatus::Suspended);

        $this->rep('Grace Ayuba', 'g.ayuba@kadunaelectric.com', ['Barnawa', 'Kakuri', 'Sabon Tasha']);
        $this->rep('Abubakar Shehu', 'a.shehu@kadunaelectric.com', ['Kawo', 'Rigasa']);

        $this->submissions();
        $this->placeholderFiles();
    }

    /**
     * Factory inspections point at signature and attachment paths that don't
     * exist; write simple placeholder images there so reports render.
     */
    private function placeholderFiles(): void
    {
        $disk = Storage::disk('local');

        Inspection::query()->with('attachments')->lazyById(100)->each(function (Inspection $inspection) use ($disk): void {
            if ($inspection->signature_path && ! $disk->exists($inspection->signature_path)) {
                $disk->put($inspection->signature_path, $this->signaturePng());
            }

            foreach ($inspection->attachments as $attachment) {
                if (! $disk->exists($attachment->path)) {
                    $disk->put($attachment->path, $this->placeholderJpeg($attachment->original_name ?? 'photo'));
                }
            }
        });
    }

    private function color(int|false $color): int
    {
        if ($color === false) {
            throw new RuntimeException('Could not allocate a GD colour.');
        }

        return $color;
    }

    private function signaturePng(): string
    {
        $image = imagecreatetruecolor(480, 160);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, $this->color(imagecolorallocatealpha($image, 0, 0, 0, 127)));
        imagesetthickness($image, 4);
        $ink = $this->color(imagecolorallocate($image, 20, 40, 90));

        $points = [[30, 110], [80, 50], [110, 120], [160, 60], [200, 115], [250, 70], [290, 100], [340, 55], [400, 105], [450, 80]];
        for ($i = 1; $i < count($points); $i++) {
            imageline($image, $points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1], $ink);
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function placeholderJpeg(string $label): string
    {
        $image = imagecreatetruecolor(1200, 900);
        imagefill($image, 0, 0, $this->color(imagecolorallocate($image, 9, 80, 46)));
        imagefilledrectangle($image, 0, 600, 1200, 680, $this->color(imagecolorallocate($image, 123, 180, 59)));
        imagestring($image, 5, 40, 40, 'KENS demo - '.pathinfo($label, PATHINFO_FILENAME), $this->color(imagecolorallocate($image, 255, 255, 255)));

        ob_start();
        imagejpeg($image, null, 80);

        return (string) ob_get_clean();
    }

    /**
     * Paid, submitted inspections over the last two months (plus a few failed
     * and abandoned attempts) so lists, the overview chart and payments have
     * something to show. Only runs once.
     */
    private function submissions(int $count = 48): void
    {
        if (Inspection::query()->submitted()->count() >= 10) {
            return;
        }

        $contractors = User::query()->role(Role::Contractor)->where('status', UserStatus::Active)->get();
        $areas = ServiceArea::query()->where('is_active', true)->get();
        $fee = FeeSchedule::currentAmountKobo() ?? 1_500_000;
        $tickets = app(GenerateTicket::class);
        $channels = ['CARD', 'ACCOUNT_TRANSFER', 'USSD'];

        for ($i = 0; $i < $count; $i++) {
            // Weight towards a few busy areas so the chart has shape.
            $area = $areas[min($areas->count() - 1, (int) floor(($i % 7) * ($i % 3 + 1) / 2))];
            $at = now()->subMinutes(fake()->numberBetween(30, 60 * 24 * 55));

            // Tickets come from the real counter, which must run in a transaction.
            $inspection = DB::transaction(fn () => Inspection::factory()->submitted()
                ->forContractor($contractors->random())
                ->inArea($area)
                ->create(['ticket_no' => $tickets->handle(), 'submitted_at' => $at]));

            Payment::factory()->forInspection($inspection)->create([
                'status' => PaymentStatus::Paid,
                'amount_kobo' => $fee,
                'amount_paid_kobo' => $fee,
                'transaction_reference' => 'MNFY|'.fake()->unique()->numerify('##|########|######'),
                'channel' => fake()->randomElement($channels),
                'paid_at' => $at,
                'created_at' => $at->subMinutes(3),
            ]);
        }

        foreach ([PaymentStatus::Failed, PaymentStatus::Failed, PaymentStatus::Abandoned, PaymentStatus::Abandoned, PaymentStatus::Abandoned] as $status) {
            Payment::factory()->create([
                'status' => $status,
                'amount_kobo' => $fee,
                'transaction_reference' => 'MNFY|'.fake()->unique()->numerify('##|########|######'),
                'created_at' => now()->subDays(fake()->numberBetween(0, 20)),
            ]);
        }
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
