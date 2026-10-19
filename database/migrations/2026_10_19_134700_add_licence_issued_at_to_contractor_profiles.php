<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licence issue date (client contractor template, Oct 2026), next to the expiry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table): void {
            $table->date('licence_issued_at')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table): void {
            $table->dropColumn('licence_issued_at');
        });
    }
};
