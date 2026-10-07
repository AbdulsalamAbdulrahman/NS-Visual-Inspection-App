<?php

declare(strict_types=1);

use App\Enums\NemsaCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('nemsa_category', array_column(NemsaCategory::cases(), 'value'));
            $table->string('nemsa_reg_no', 40)->unique();
            $table->string('coren_no', 40)->nullable();
            // Required when the category is corporate (enforced in validation).
            $table->string('firm_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_profiles');
    }
};
