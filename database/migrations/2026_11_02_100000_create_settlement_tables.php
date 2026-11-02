<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_parties', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->unsignedInteger('percentage_bp');
            $table->string('bank_code', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('account_name')->nullable();
            $table->string('sub_account_code', 60)->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('settlement_parties')->insert(array_map(fn (string $key, int $bp): array => [
            'key' => $key, 'percentage_bp' => $bp, 'created_at' => $now, 'updated_at' => $now,
        ], ['inspector', 'lecan', 'reviewers', 'admin_hq', 'app_team', 'nemsa', 'reps'], [4000, 1500, 1000, 2000, 500, 500, 500]));

        Schema::table('contractor_profiles', function (Blueprint $table) {
            $table->string('bank_code', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('account_name')->nullable();
            $table->string('sub_account_code', 60)->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_kobo')->nullable();
            $table->unsignedBigInteger('charge_kobo')->default(0);
        });

        Schema::create('payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('party', 40)->index();
            $table->foreignId('contractor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('percentage_bp');
            $table->unsignedBigInteger('amount_kobo');
            $table->string('sub_account_code', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_splits');
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['fee_kobo', 'charge_kobo']));
        Schema::table('contractor_profiles', fn (Blueprint $table) => $table->dropColumn(['bank_code', 'bank_name', 'account_number', 'account_name', 'sub_account_code']));
        Schema::dropIfExists('settlement_parties');
    }
};
