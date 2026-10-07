<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\ContractorProfile;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Defaults to an active admin with a settled password; use the role states
 * for contractors (with licence profile) and reps (with areas).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('080# ### ####'),
            'role' => Role::Admin,
            'status' => UserStatus::Active,
            'must_change_password' => false,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    public function contractor(): static
    {
        return $this->state(['role' => Role::Contractor])
            ->has(ContractorProfile::factory(), 'contractorProfile');
    }

    /**
     * @param  int|list<ServiceArea>  $areas  number of new areas, or existing areas to assign
     */
    public function rep(int|array $areas = 1): static
    {
        $factory = $this->state(['role' => Role::Rep]);

        return is_int($areas)
            ? $factory->hasAttached(ServiceArea::factory()->count($areas), [], 'serviceAreas')
            : $factory->hasAttached($areas, [], 'serviceAreas');
    }

    /** Freshly created by an admin: temporary password, must change it. */
    public function invited(): static
    {
        return $this->state([
            'status' => UserStatus::Invited,
            'must_change_password' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => UserStatus::Suspended]);
    }
}
