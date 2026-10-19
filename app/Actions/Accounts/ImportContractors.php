<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\NemsaCategory;
use App\Enums\NigerianState;
use App\Support\ContractorRules;
use App\Support\CsvRows;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * CSV bulk registration of contractors. read() checks every row (including
 * duplicates within the file) without saving; create() registers the rows
 * that passed, each emailed a temporary password like a single registration.
 */
class ImportContractors
{
    /** Passport photo, licence copy and signature aren't here: the contractor adds them at first login. */
    public const COLUMNS = [
        'name', 'email', 'phone', 'resident_state', 'nemsa_category', 'nemsa_reg_no',
        'coren_no', 'firm_name', 'engineer_name', 'engineer_reg_no',
        'licence_issued_on', 'licence_expires_on',
    ];

    /** The first template's state column, and the client's own headers. */
    public const ALIASES = [
        'state' => 'resident_state',
        'licence_issuance_date' => 'licence_issued_on',
        'liscence_issuance_date' => 'licence_issued_on',
        'licence_issue_date' => 'licence_issued_on',
        'licence_expiry_date' => 'licence_expires_on',
        'liscence_expiry_date' => 'licence_expires_on',
    ];

    /** Files from the first template have no licence dates; the client's own sheet has no engineer reg. no. */
    public const OPTIONAL = ['engineer_reg_no', 'licence_issued_on', 'licence_expires_on'];

    public const MAX_ROWS = 300;

    public function __construct(private readonly CreateContractor $create) {}

    /**
     * @return list<array{line: int, data: array<string, mixed>, summary: array<string, string|null>, errors: list<string>}>
     *
     * @throws RuntimeException when the file isn't a CSV in the template's shape
     */
    public function read(string $path): array
    {
        $seen = ['email' => [], 'nemsa_reg_no' => []];
        $rows = [];

        foreach (CsvRows::read($path, self::COLUMNS, self::MAX_ROWS, self::ALIASES, self::OPTIONAL) as $row) {
            $values = $row['values'];
            $category = $this->category($values['nemsa_category']);
            $state = $this->state($values['resident_state']);

            $data = ContractorRules::normalise([
                ...array_diff_key($values, array_flip(['resident_state', 'licence_issued_on', 'licence_expires_on'])),
                'nemsa_category' => $category->value ?? $values['nemsa_category'],
                'state' => $state->value ?? $values['resident_state'],
                'licence_issued_at' => $this->date($values['licence_issued_on']),
                'licence_expires_at' => $this->date($values['licence_expires_on']),
            ]);

            $errors = $this->errors($data, [
                'nemsa_category.enum' => "NEMSA category “{$values['nemsa_category']}” isn't recognised (Cat A, Cat B, Cat C or Corporate).",
                'state.enum' => "State “{$values['resident_state']}” isn't one of Kaduna, Kebbi, Sokoto or Zamfara.",
                'licence_issued_at.date_format' => "Licence issue date “{$values['licence_issued_on']}” isn't a date (use DD/MM/YYYY).",
                'licence_expires_at.date_format' => "Licence expiry date “{$values['licence_expires_on']}” isn't a date (use DD/MM/YYYY).",
                'licence_expires_at.after_or_equal' => 'The licence expired on '.$this->dateLabel($data['licence_expires_at'] ?? null).'.',
                'firm_name.required' => 'Firm name is required for Corporate contractors.',
                'engineer_name.required' => 'Engineer name is required for Corporate contractors.',
            ]);

            // The same person twice in one file would fail half-way through creating.
            foreach (['email' => 'Email', 'nemsa_reg_no' => 'NEMSA registration no.'] as $field => $label) {
                if (filled($data[$field]) && isset($seen[$field][$data[$field]])) {
                    $errors[] = "{$label} is also on row {$seen[$field][$data[$field]]}.";
                } elseif (filled($data[$field])) {
                    $seen[$field][$data[$field]] = $row['line'];
                }
            }

            $rows[] = [
                'line' => $row['line'],
                'data' => $data,
                'summary' => [
                    'name' => $data['name'] ?: null,
                    'email' => $data['email'] ?: null,
                    'state' => $state?->label() ?? ($values['resident_state'] ?: null),
                    'category' => $category?->label() ?? ($values['nemsa_category'] ?: null),
                    'regNo' => $data['nemsa_reg_no'] ?: null,
                    'firm' => $data['firm_name'],
                    'expires' => $this->dateLabel($data['licence_expires_at']),
                ],
                'errors' => $errors,
            ];
        }

        return $rows;
    }

    /**
     * Rows are checked again: someone may have registered the same email since the preview.
     *
     * @param  list<array{data: array<string, mixed>, errors: list<string>}>  $rows
     * @return int the number of contractors registered
     */
    public function create(array $rows): int
    {
        $valid = array_values(array_filter($rows, fn (array $row): bool => $row['errors'] === [] && $this->errors($row['data']) === []));

        DB::transaction(function () use ($valid): void {
            foreach ($valid as $row) {
                $this->create->handle(ContractorRules::data($row['data']));
            }
        });

        return count($valid);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $messages
     * @return list<string>
     */
    private function errors(array $data, array $messages = []): array
    {
        return array_values(Validator::make(
            $data,
            ContractorRules::rules($data),
            [...ContractorRules::messages($data), ...$messages],
            ContractorRules::attributes(),
        )->errors()->all());
    }

    /** "Cat A", "A", "cat_a" or "Corporate". */
    private function category(string $text): ?NemsaCategory
    {
        $key = CsvRows::key($text);

        foreach (NemsaCategory::cases() as $case) {
            if ($key !== '' && in_array($key, [CsvRows::key($case->value), CsvRows::key($case->label()), CsvRows::key($case->letter())], true)) {
                return $case;
            }
        }

        return null;
    }

    /**
     * "12/03/2027", "12-03-27", "2027-03-12" or "12 Mar 2027" as Y-m-d; anything
     * else comes back as typed so validation names it. Blank stays blank.
     */
    private function date(string $text): string
    {
        if ($text === '') {
            return '';
        }

        foreach (['!d/m/Y', '!j/n/Y', '!d/m/y', '!j/n/y', '!d-m-Y', '!d-m-y', '!d.m.Y', '!Y-m-d', '!j M Y', '!j F Y', '!d M Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $text);
            } catch (InvalidFormatException) {
                continue;
            }

            // Round-trip check: rejects 31/02 rolling over into March.
            if ($date !== null && $date->format(ltrim($format, '!')) === $text) {
                return $date->toDateString();
            }
        }

        return $text;
    }

    private function dateLabel(mixed $date): ?string
    {
        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? CarbonImmutable::parse($date)->format('j M Y') : null;
    }

    private function state(string $text): ?NigerianState
    {
        return NigerianState::tryFrom(CsvRows::key($text));
    }
}
