<?php

declare(strict_types=1);

namespace App\Http\Requests\Rep;

use App\Support\JobRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class JobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isRep();
    }

    protected function prepareForValidation(): void
    {
        $this->replace(JobRules::normalise($this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return JobRules::rules($this->user());
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach (JobRules::crossCheck($validator->getData()) as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return JobRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return JobRules::attributes();
    }
}
