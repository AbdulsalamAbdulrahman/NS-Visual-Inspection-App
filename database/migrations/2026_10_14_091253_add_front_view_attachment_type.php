<?php

use App\Enums\AttachmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client request (14 Oct): a photo of the house's front view, as its own
 * attachment type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_attachments', function (Blueprint $table) {
            $table->enum('type', array_column(AttachmentType::cases(), 'value'))->change();
        });
    }

    public function down(): void
    {
        Schema::table('inspection_attachments', function (Blueprint $table) {
            $table->enum('type', ['layout', 'photo', 'calibration'])->change();
        });
    }
};
