<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_reference', 32)->unique();
            $table->foreignId('membership_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method');
            $table->string('status')->default('recorded');
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['membership_transaction_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
