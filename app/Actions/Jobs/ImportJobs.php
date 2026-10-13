<?php

declare(strict_types=1);

namespace App\Actions\Jobs;

use App\Enums\BuildingType;
use App\Enums\PropertyPurpose;
use App\Models\ServiceArea;
use App\Models\User;
use App\Support\Form74Range;
use App\Support\JobRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('The file could not be read.');
        }

        try {
            $header = fgetcsv($handle, escape: '');
            $header = is_array($header) ? array_map(fn ($h): string => Str::snake(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header) : [];
            $missing = array_diff(self::COLUMNS, $header);

            if ($missing !== []) {
                throw new RuntimeException('Use the template: the first row must have the column names. Missing: '.implode(', ', $missing).'.');
            }

            $areas = $rep->serviceAreas()->get()->keyBy(fn (ServiceArea $area): string => $this->key($area->name));
            $rows = [];
            $line = 1;

            while (($cells = fgetcsv($handle, escape: '')) !== false) {
                $line++;

                if ($cells === [null] || implode('', array_map('trim', array_map('strval', $cells))) === '') {
                    continue;
                }

                if (count($rows) >= self::MAX_ROWS) {
                    throw new RuntimeException('Upload at most '.self::MAX_ROWS.' jobs at a time.');
                }

                $values = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''));
                $rows[] = $this->check($rep, $line, array_map(fn ($v): string => trim((string) $v), $values), $areas);
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw new RuntimeException('The file has no jobs under the column names.');
        }

        return $rows;
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
        $area = $areas->get($this->key($values['area_office']));
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
        $wanted = $this->key($text);

        foreach ($cases as $case) {
            if ($wanted !== '' && ($this->key($case->value) === $wanted || $this->key($case->label()) === $wanted)) {
                return $case;
            }
        }

        return null;
    }

    private function key(string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower($text)) ?? '';
    }
}
