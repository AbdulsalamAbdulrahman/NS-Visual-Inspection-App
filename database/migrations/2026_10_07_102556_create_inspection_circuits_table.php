<?php

declare(strict_types=1);

use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_circuits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            // Client-generated so cards keep their identity while editing offline.
            $table->uuid();
            $table->unsignedSmallInteger('position');
            $table->enum('description', array_column(CircuitDescription::cases(), 'value'))->nullable();
            $table->string('description_other')->nullable();
            $table->decimal('rating_a', 7, 1)->nullable();
            $table->decimal('conductor_mm2', 6, 2)->nullable();
            $table->enum('condition', array_column(CircuitCondition::cases(), 'value'))->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'uuid']);
            $table->index(['inspection_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_circuits');
    }
};
