<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 24);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['inspection_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_reviews');
    }
};
