<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents contractors upload on their Profile (client request, 14 Oct):
 * licence copy, passport photograph and signature, plus the licence expiry
 * that blocks new work once passed. The two timestamps make sure each
 * reminder email goes once per expiry date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table) {
            $table->string('licence_path')->nullable()->after('engineer_reg_no');
            $table->string('passport_path')->nullable()->after('licence_path');
            $table->string('signature_path')->nullable()->after('passport_path');
            $table->date('licence_expires_at')->nullable()->index()->after('signature_path');
            $table->timestamp('licence_reminded_at')->nullable()->after('licence_expires_at');
            $table->timestamp('licence_expired_notified_at')->nullable()->after('licence_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('contractor_profiles', function (Blueprint $table) {
            $table->dropIndex(['licence_expires_at']);
            $table->dropColumn(['licence_path', 'passport_path', 'signature_path', 'licence_expires_at', 'licence_reminded_at', 'licence_expired_notified_at']);
        });
    }
};
