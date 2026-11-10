<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('membership_number', 32)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->foreignId('category_id')->constrained('membership_categories')->restrictOnDelete();
            $table->foreignId('level_id')->constrained('membership_levels')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->date('joined_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->index(['category_id', 'level_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
