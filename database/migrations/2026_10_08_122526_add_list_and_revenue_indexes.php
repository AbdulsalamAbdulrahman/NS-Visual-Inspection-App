<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 index review: the admin and rep lists filter submitted inspections
 * and sort newest first; the overview sums paid payments by paid_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // Replaces the single-column index: (status, submitted_at) also serves status alone.
            $table->index(['status', 'submitted_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->index('status');
            $table->dropIndex(['status', 'submitted_at']);
        });
    }
};
