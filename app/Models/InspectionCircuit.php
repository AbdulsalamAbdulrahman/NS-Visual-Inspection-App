<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of B4 · Description of wiring (C1, C2 … by position).
 *
 * @property int $id
 * @property string $uuid
 * @property int $inspection_id
 * @property int $position
 * @property CircuitDescription|null $description
 * @property string|null $description_other
 * @property float|null $rating_a
 * @property float|null $conductor_mm2
 * @property CircuitCondition|null $condition
 * @property string|null $observation
 */
class InspectionCircuit extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'position',
        'description',
        'description_other',
        'rating_a',
        'conductor_mm2',
        'condition',
        'observation',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'description' => CircuitDescription::class,
        'rating_a' => 'float',
        'conductor_mm2' => 'float',
        'condition' => CircuitCondition::class,
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /** "C3" */
    public function label(): string
    {
        return 'C'.$this->position;
    }

    /** "Air conditioner", or the free text for "Other". */
    public function descriptionLabel(): ?string
    {
        if ($this->description === CircuitDescription::Other) {
            return $this->description_other ? "Other · {$this->description_other}" : 'Other';
        }

        return $this->description?->label();
    }
}
