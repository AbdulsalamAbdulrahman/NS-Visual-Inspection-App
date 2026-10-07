<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A rep is assigned 1–3 areas (enforced in validation).
        Schema::create('rep_service_area', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_area_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'service_area_id']);
            $table->index('service_area_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_service_area');
    }
};
