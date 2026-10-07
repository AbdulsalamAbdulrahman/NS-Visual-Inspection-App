<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('amount_kobo');
            $table->date('effective_from')->index();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // Soft delete = a cancelled scheduled change.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
    }
};
