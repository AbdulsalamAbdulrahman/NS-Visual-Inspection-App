<?php

declare(strict_types=1);

use App\Enums\AttachmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->enum('type', array_column(AttachmentType::cases(), 'value'));
            // Private disk: storage/app/private/inspections/{inspection uuid}/…
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size_bytes');
            // Read from the photo's EXIF before browser compression.
            $table->decimal('exif_lat', 10, 7)->nullable();
            $table->decimal('exif_lng', 10, 7)->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamps();

            $table->index(['inspection_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_attachments');
    }
};
