<?php

declare(strict_types=1);

use App\Enums\InspectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Failed draft saves (the ceiling-fan enum bug) deleted the old signature file
 * while the database kept pointing at it. Clear those paths so the checklist
 * asks the contractor to sign again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $disk = Storage::disk('local');

        DB::table('inspections')
            ->where('status', InspectionStatus::Draft->value)
            ->whereNotNull('signature_path')
            ->select(['id', 'signature_path'])
            ->orderBy('id')
            ->each(function (object $row) use ($disk): void {
                if (! $disk->exists($row->signature_path)) {
                    DB::table('inspections')->where('id', $row->id)->update(['signature_path' => null]);
                }
            });
    }

    public function down(): void
    {
        // Nothing to restore: the files were already gone.
    }
};
