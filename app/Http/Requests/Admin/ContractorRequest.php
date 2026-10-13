<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\ContractorRules;
use Illuminate\Foundation\Http\FormRequest;

class ContractorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->replace(ContractorRules::normalise($this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $contractor */
        $contractor = $this->route('contractor');

        return ContractorRules::rules($this->all(), $contractor);
    }

    /**
     * Validated input in the shape CreateContractor / UpdateContractor expect.
     *
     * @return array{name: string, email: string, phone: string|null, nemsa_category: string, nemsa_reg_no: string, coren_no: string|null, firm_name: string|null, engineer_name: string|null, engineer_reg_no: string|null, state: string}
     */
    public function contractorData(): array
    {
        return ContractorRules::data($this->validated());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ContractorRules::messages($this->all());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ContractorRules::attributes();
    }
}
