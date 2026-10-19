<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NemsaCategory;
use App\Enums\NigerianState;
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
            // Individuals: letters and numbers only (client format, Oct 2026).
            'nemsa_reg_no' => sprintf('NEMSA%s%d%04d', $category->letter(), fake()->numberBetween(2018, 2026), fake()->unique()->numberBetween(1, 9999)),
            'coren_no' => fake()->optional()->numerify('R.#####'),
            'firm_name' => null,
            'state' => NigerianState::Kaduna,
            // Onboarded: documents and licence dates in (see withoutDocuments()).
            'licence_path' => 'contractors/test/licence.jpg',
            'passport_path' => 'contractors/test/passport.jpg',
            'signature_path' => 'contractors/test/signature.png',
            'licence_issued_at' => today()->subYear(),
            'licence_expires_at' => today()->addYear(),
        ];
    }

    /** Fresh from the register or import: documents to add at first sign-in. */
    public function withoutDocuments(): static
    {
        return $this->state([
            'licence_path' => null,
            'passport_path' => null,
            'signature_path' => null,
            'licence_issued_at' => null,
            'licence_expires_at' => null,
        ]);
    }

    public function corporate(string $firm = 'Danladi Electrical Services'): static
    {
        return $this->state([
            'nemsa_category' => NemsaCategory::Corporate,
            // Corporate: slash-separated groups like CC/CE1F/PR/08634.
            'nemsa_reg_no' => sprintf('CC/CE%dF/PR/%05d', fake()->numberBetween(1, 9), fake()->unique()->numberBetween(1, 99999)),
            'firm_name' => $firm,
            'engineer_name' => 'Engr. '.fake()->name(),
        ]);
    }
}
