<?php

declare(strict_types=1);

namespace App\Actions\Inspections;

use App\Models\Inspection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveDraft
{
    /**
     * Apply a (partial) draft payload. Keys that are absent are left alone;
     * `circuits`, when present, replaces the whole list.
     *
     * @param  array<string, mixed>  $data  validated SaveDraftRequest data
     */
    public function handle(Inspection $inspection, array $data): Inspection
    {
        DB::transaction(function () use ($inspection, $data): void {
            $fields = Arr::only($data, Inspection::DRAFT_FIELDS);

            if (array_key_exists('declaration_accepted', $data)) {
                $fields['declaration_accepted_at'] = $data['declaration_accepted']
                    ? ($inspection->declaration_accepted_at ?? now())
                    : null;
            }

            if (array_key_exists('signature', $data)) {
                $fields['signature_path'] = $this->storeSignature($inspection, $data['signature']);
            }

            $inspection->fill($fields);
            // Always bump updated_at so "Saved 11:03" reflects the latest save.
            $inspection->updated_at = now();
            $inspection->save();

            if (array_key_exists('circuits', $data)) {
                $this->replaceCircuits($inspection, $data['circuits'] ?? []);
            }
        });

        return $inspection;
    }

    private function storeSignature(Inspection $inspection, ?string $dataUrl): ?string
    {
        $disk = Storage::disk('local');

        if ($inspection->signature_path) {
            $disk->delete($inspection->signature_path);
        }

        if ($dataUrl === null) {
            return null;
        }

        $png = base64_decode(Str::after($dataUrl, 'base64,'), true);

        if ($png === false || ! str_starts_with($png, "\x89PNG")) {
            return null;
        }

        $path = $inspection->storageDirectory().'/signature-'.Str::random(8).'.png';
        $disk->put($path, $png);

        return $path;
    }

    /**
     * @param  array<int, array<string, mixed>>  $circuits
     */
    private function replaceCircuits(Inspection $inspection, array $circuits): void
    {
        $inspection->circuits()->delete();

        foreach (array_values($circuits) as $index => $circuit) {
            $inspection->circuits()->create([
                'uuid' => $circuit['uuid'],
                'position' => $index + 1,
                'description' => $circuit['description'] ?? null,
                'description_other' => $circuit['description_other'] ?? null,
                'rating_a' => $circuit['rating_a'] ?? null,
                'conductor_mm2' => $circuit['conductor_mm2'] ?? null,
                'condition' => $circuit['condition'] ?? null,
                'observation' => $circuit['observation'] ?? null,
            ]);
        }

        $inspection->unsetRelation('circuits');
    }
}
