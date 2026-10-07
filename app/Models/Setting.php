<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Small admin-editable settings (key → string). Cached; writes clear the cache.
 *
 * @property string $key
 * @property string|null $value
 * @property int|null $updated_by
 * @property CarbonImmutable|null $updated_at
 */
class Setting extends Model
{
    /** Head of New Service Department, printed on certificates. */
    public const SIGNATORY_NAME = 'certificate.signatory_name';

    public const SIGNATORY_TITLE = 'certificate.signatory_title';

    /** Path on the private disk to the signature image. */
    public const SIGNATORY_SIGNATURE = 'certificate.signatory_signature_path';

    private const CACHE_KEY = 'settings.all';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        /** @var array<string, string|null> $all */
        $all = Cache::rememberForever(self::CACHE_KEY, fn (): array => self::query()->pluck('value', 'key')->all());

        return $all[$key] ?? $default;
    }

    public static function put(string $key, ?string $value, ?User $by = null): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $by?->id]);
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The current certificate signatory, or null until an admin has set all of it.
     *
     * @return array{name: string, title: string, signature_path: string}|null
     */
    public static function signatory(): ?array
    {
        $name = self::get(self::SIGNATORY_NAME);
        $title = self::get(self::SIGNATORY_TITLE);
        $path = self::get(self::SIGNATORY_SIGNATURE);

        return filled($name) && filled($title) && filled($path)
            ? ['name' => (string) $name, 'title' => (string) $title, 'signature_path' => (string) $path]
            : null;
    }
}
