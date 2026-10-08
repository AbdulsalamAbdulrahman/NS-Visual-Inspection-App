<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('review_status', 24)->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
            $table->timestamp('approved_at')->nullable()->after('review_note');
            // Certificate signatory, copied at approval so later settings changes
            // never alter a certificate that was already issued.
            $table->string('signatory_name')->nullable()->after('approved_at');
            $table->string('signatory_title')->nullable()->after('signatory_name');
            $table->string('signatory_signature_path')->nullable()->after('signatory_title');

            $table->index(['review_status', 'submitted_at']);
        });

        // Anything already paid starts in the review queue.
        DB::table('inspections')->where('status', 'submitted')->update(['review_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropIndex(['review_status', 'submitted_at']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'review_status', 'reviewed_at', 'review_note', 'approved_at',
                'signatory_name', 'signatory_title', 'signatory_signature_path',
            ]);
        });
    }
};
