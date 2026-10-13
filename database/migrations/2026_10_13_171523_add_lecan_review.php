<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client feedback (stage 4): a LECAN official of the area's state chapter
 * reviews each paid report first, then NSD gives the final approval.
 * Reports already waiting for review go to LECAN first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', array_column(Role::cases(), 'value'))->change();
            // LECAN officials: the state chapter whose reports they review.
            $table->string('lecan_state', 16)->nullable()->after('role');
        });

        Schema::table('inspections', function (Blueprint $table) {
            $table->foreignId('lecan_approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('lecan_approved_at')->nullable()->after('lecan_approved_by');
            // Copied at approval, like the NSD signatory, for the certificate.
            $table->string('lecan_approver_name')->nullable()->after('lecan_approved_at');
        });

        DB::table('inspections')->where('review_status', 'pending')->update(['review_status' => 'pending_lecan']);
    }

    public function down(): void
    {
        DB::table('inspections')->whereIn('review_status', ['pending_lecan', 'pending_nsd'])->update(['review_status' => 'pending']);

        Schema::table('inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lecan_approved_by');
            $table->dropColumn(['lecan_approved_at', 'lecan_approver_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lecan_state');
        });
    }
};
