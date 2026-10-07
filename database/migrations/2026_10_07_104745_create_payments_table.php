<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->restrictOnDelete();
            $table->foreignId('contractor_id')->constrained('users')->restrictOnDelete();
            // Ours, sent to Monnify as paymentReference.
            $table->string('payment_reference', 64)->unique();
            // Monnify's, e.g. MNFY|20261001|0042871.
            $table->string('transaction_reference', 64)->nullable()->unique();
            // Fee in effect when the payment was started.
            $table->unsignedBigInteger('amount_kobo');
            $table->unsignedBigInteger('amount_paid_kobo')->nullable();
            $table->string('channel', 40)->nullable();
            $table->enum('status', array_column(PaymentStatus::cases(), 'value'))->default(PaymentStatus::Pending->value);
            $table->timestamp('paid_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['inspection_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
