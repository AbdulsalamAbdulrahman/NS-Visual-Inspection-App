<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttachmentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file on the private disk, only served through AttachmentController.
 *
 * @property int $id
 * @property string $uuid
 * @property int $inspection_id
 * @property AttachmentType $type
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size_bytes
 * @property float|null $exif_lat
 * @property float|null $exif_lng
 * @property CarbonImmutable|null $taken_at
 * @property-read Inspection $inspection
 */
class InspectionAttachment extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'path',
        'original_name',
        'mime',
        'size_bytes',
        'exif_lat',
        'exif_lng',
        'taken_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'type' => AttachmentType::class,
        'size_bytes' => 'integer',
        'exif_lat' => 'float',
        'exif_lng' => 'float',
        'taken_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
