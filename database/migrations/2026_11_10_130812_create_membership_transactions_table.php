<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 32)->unique();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('processed_by')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->string('status')->default('Pending');
            $table->decimal('amount', 10, 2);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
            $table->index(['member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_transactions');
    }
};
