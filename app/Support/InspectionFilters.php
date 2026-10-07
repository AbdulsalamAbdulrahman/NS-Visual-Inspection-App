<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ConnectionType;
use App\Enums\PropertyPurpose;
use App\Models\Inspection;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Search + filters for submitted-inspection lists (admin list and CSV, rep
 * list): ticket / owner / Form 74 search, service areas, purpose,
 * connection type and a submitted-date range.
 */
final class InspectionFilters
{
    /**
     * @param  list<int>  $areaIds
     */
    private function __construct(
        public readonly string $search,
        public readonly array $areaIds,
        public readonly ?PropertyPurpose $purpose,
        public readonly ?ConnectionType $connection,
        public readonly ?CarbonImmutable $from,
        public readonly ?CarbonImmutable $to,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $date = function (string $key) use ($request): ?CarbonImmutable {
            $value = $request->string($key)->toString();

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? CarbonImmutable::createFromFormat('Y-m-d', $value)?->startOfDay() : null;
        };

        $areas = array_values(array_filter(array_map('intval', explode(',', $request->string('areas')->toString()))));

        return new self(
            search: trim($request->string('search')->toString()),
            areaIds: $areas,
            purpose: PropertyPurpose::tryFrom($request->string('purpose')->toString()),
            connection: ConnectionType::tryFrom($request->string('connection')->toString()),
            from: $date('from'),
            to: $date('to'),
        );
    }

    /**
     * @param  Builder<Inspection>  $query
     * @return Builder<Inspection>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->search($this->search)
            ->when($this->areaIds !== [], fn (Builder $q) => $q->whereIn('service_area_id', $this->areaIds))
            ->when($this->purpose, fn (Builder $q) => $q->where('purpose', $this->purpose))
            ->when($this->connection, fn (Builder $q) => $q->where('connection_type', $this->connection))
            ->when($this->from, fn (Builder $q) => $q->where('submitted_at', '>=', $this->from))
            ->when($this->to, fn (Builder $q) => $q->where('submitted_at', '<', $this->to->copy()->addDay()));
    }

    /** Filters other than search (the "Filters" badge count on phones). */
    public function count(): int
    {
        return ($this->areaIds !== [] ? 1 : 0)
            + ($this->purpose ? 1 : 0)
            + ($this->connection ? 1 : 0)
            + ($this->from || $this->to ? 1 : 0);
    }

    /**
     * Echoed back to the page so controls show the current state.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'areas' => $this->areaIds,
            'purpose' => $this->purpose?->value,
            'connection' => $this->connection?->value,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'count' => $this->count(),
        ];
    }
}
