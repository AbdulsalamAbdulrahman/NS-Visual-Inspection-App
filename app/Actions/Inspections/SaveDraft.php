<?php

declare(strict_types=1);

namespace App\Actions\Inspections;

use App\Models\Inspection;
use App\Support\Form74Range;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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
        $disk = Storage::disk('local');
        $signatureChanged = array_key_exists('signature', $data);
        $oldSignature = $inspection->signature_path;
        // Files don't roll back with the transaction: write the new one first and
        // delete the old one only once the save has committed.
        $newSignature = $signatureChanged ? $this->storeSignature($inspection, $data['signature']) : null;

        try {
            $this->save($inspection, $data, $signatureChanged, $newSignature);
        } catch (Throwable $e) {
            if ($newSignature !== null) {
                $disk->delete($newSignature);
            }

            throw $e;
        }

        if ($signatureChanged && $oldSignature !== null && $oldSignature !== $newSignature) {
            $disk->delete($oldSignature);
        }

        return $inspection;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(Inspection $inspection, array $data, bool $signatureChanged, ?string $newSignature): void
    {
        DB::transaction(function () use ($inspection, $data, $signatureChanged, $newSignature): void {
            $fields = Arr::only($data, Inspection::DRAFT_FIELDS);

            if ($inspection->hasRepDetails()) {
                $fields = Arr::except($fields, Inspection::REP_FIELDS);
            }

            if (array_key_exists('declaration_accepted', $data)) {
                $fields['declaration_accepted_at'] = $data['declaration_accepted']
                    ? ($inspection->declaration_accepted_at ?? now())
                    : null;
            }

            if ($signatureChanged) {
                $fields['signature_path'] = $newSignature;
            }

            $inspection->fill($fields);
            // Estates: the Form 74 range gives the number of structures charged for.
            $count = Form74Range::count($inspection->form74_no, $inspection->form74_last_no);
            $inspection->structure_count = is_int($count) ? $count : 1;
            // Always bump updated_at so "Saved 11:03" reflects the latest save.
            $inspection->updated_at = now();
            $inspection->save();

            if (array_key_exists('circuits', $data)) {
                $this->replaceCircuits($inspection, $data['circuits'] ?? []);
            }
        });
    }

    private function storeSignature(Inspection $inspection, ?string $dataUrl): ?string
    {
        if ($dataUrl === null) {
            return null;
        }

        $png = base64_decode(Str::after($dataUrl, 'base64,'), true);

        if ($png === false || ! str_starts_with($png, "\x89PNG")) {
            return null;
        }

        $path = $inspection->storageDirectory().'/signature-'.Str::random(8).'.png';
        Storage::disk('local')->put($path, $png);

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
                'earth_conductor_mm2' => $circuit['earth_conductor_mm2'] ?? null,
                'live_colour' => $circuit['live_colour'] ?? null,
                'neutral_colour' => $circuit['neutral_colour'] ?? null,
                'earth_colour' => $circuit['earth_colour'] ?? null,
                'condition' => $circuit['condition'] ?? null,
                'observation' => $circuit['observation'] ?? null,
            ]);
        }

        $inspection->unsetRelation('circuits');
    }
}
