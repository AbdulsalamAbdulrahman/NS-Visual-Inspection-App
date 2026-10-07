<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NemsaCategory;
use App\Models\ContractorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractorProfile>
 */
class ContractorProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement([NemsaCategory::CatA, NemsaCategory::CatB, NemsaCategory::CatC]);

        return [
            'nemsa_category' => $category,
            'nemsa_reg_no' => sprintf('NEMSA/%s/%d/%04d', $category->letter(), fake()->numberBetween(2018, 2026), fake()->unique()->numberBetween(1, 9999)),
            'coren_no' => fake()->optional()->numerify('R.#####'),
            'firm_name' => null,
        ];
    }

    public function corporate(string $firm = 'Danladi Electrical Services'): static
    {
        return $this->state([
            'nemsa_category' => NemsaCategory::Corporate,
            'nemsa_reg_no' => sprintf('NEMSA/CORP/%d/%04d', fake()->numberBetween(2018, 2026), fake()->unique()->numberBetween(1, 9999)),
            'firm_name' => $firm,
        ]);
    }
}
