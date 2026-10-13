<?php

declare(strict_types=1);

namespace App\Actions\Jobs;

use App\Enums\BuildingType;
use App\Enums\PropertyPurpose;
use App\Models\ServiceArea;
use App\Models\User;
use App\Support\CsvRows;
use App\Support\Form74Range;
use App\Support\JobRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * CSV bulk upload of jobs. read() checks every row without saving anything,
 * so the rep sees a preview with each row's problems; create() saves the
 * rows that passed, in one transaction.
 */
class ImportJobs
{
    public const COLUMNS = [
        'owner_name', 'property_address', 'phone', 'email', 'area_office',
        'purpose', 'building_type', 'form74_first', 'form74_last', 'contractor_email',
    ];

    public const MAX_ROWS = 300;

    public function __construct(private readonly SaveJob $save) {}

    /**
     * @return list<array{line: int, data: array<string, mixed>, summary: array<string, string|null>, errors: list<string>}>
     *
     * @throws RuntimeException when the file isn't a CSV in the template's shape
     */
    public function read(User $rep, string $path): array
    {
        $areas = $rep->serviceAreas()->get()->keyBy(fn (ServiceArea $area): string => CsvRows::key($area->name));

        return array_map(
            fn (array $row): array => $this->check($rep, $row['line'], $row['values'], $areas),
            CsvRows::read($path, self::COLUMNS, self::MAX_ROWS),
        );
    }

    /**
     * Rows are checked again: a contractor may have been suspended since the preview.
     *
     * @param  list<array{data: array<string, mixed>, errors: list<string>}>  $rows
     * @return int the number of jobs created
     */
    public function create(User $rep, array $rows): int
    {
        $valid = array_values(array_filter($rows, fn (array $row): bool => $row['errors'] === [] && $this->errors($rep, $row['data']) === []));

        DB::transaction(function () use ($rep, $valid): void {
            foreach ($valid as $row) {
                $this->save->handle($rep, $row['data']);
            }
        });

        return count($valid);
    }

    /**
     * @param  array<string, string>  $values
     * @param  Collection<string, ServiceArea>  $areas
     * @return array{line: int, data: array<string, mixed>, summary: array<string, string|null>, errors: list<string>}
     */
    private function check(User $rep, int $line, array $values, $areas): array
    {
        $area = $areas->get(CsvRows::key($values['area_office']));
        $purpose = $this->match(PropertyPurpose::cases(), $values['purpose']);
        $type = $this->match(BuildingType::cases(), $values['building_type']);

        $data = JobRules::normalise([
            'owner_name' => $values['owner_name'],
            'property_address' => $values['property_address'],
            'owner_phone' => $values['phone'],
            'owner_email' => $values['email'],
            'service_area_id' => $area?->id,
            'purpose' => $purpose->value ?? $values['purpose'],
            'building_type' => $type->value ?? $values['building_type'],
            'form74_no' => $values['form74_first'],
            'form74_last_no' => $values['form74_last'],
            'contractor' => $values['contractor_email'],
        ]);

        $errors = $this->errors($rep, $data, [
            'service_area_id.required' => $values['area_office'] === '' ? 'Area office is missing.' : "“{$values['area_office']}” isn't one of your area offices.",
            'purpose.enum' => "Purpose “{$values['purpose']}” isn't recognised.",
            'building_type.enum' => "Building type “{$values['building_type']}” isn't recognised.",
        ]);

        $contractor = $area && filled($data['contractor']) ? JobRules::contractor((string) $data['contractor'], $area) : null;

        return [
            'line' => $line,
            'data' => $data,
            'summary' => [
                'owner' => $data['owner_name'],
                'address' => $data['property_address'],
                'phone' => $data['owner_phone'],
                'area' => $area->name ?? ($values['area_office'] ?: null),
                'building' => $type?->label() ?? ($values['building_type'] ?: null),
                'form74' => Form74Range::label($data['form74_no'], $data['form74_last_no']),
                'contractor' => $contractor->name ?? ($values['contractor_email'] ?: null),
            ],
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $messages
     * @return list<string>
     */
    private function errors(User $rep, array $data, array $messages = []): array
    {
        $errors = Validator::make($data, JobRules::rules($rep), [...JobRules::messages(), ...$messages], JobRules::attributes())->errors()->all();

        return $errors === [] ? array_values(JobRules::crossCheck($data)) : array_values($errors);
    }

    /**
     * Matches an enum by its value or label, ignoring case and punctuation.
     *
     * @template T of PropertyPurpose|BuildingType
     *
     * @param  list<T>  $cases
     * @return T|null
     */
    private function match(array $cases, string $text): PropertyPurpose|BuildingType|null
    {
        $wanted = CsvRows::key($text);

        foreach ($cases as $case) {
            if ($wanted !== '' && (CsvRows::key($case->value) === $wanted || CsvRows::key($case->label()) === $wanted)) {
                return $case;
            }
        }

        return null;
    }
}
