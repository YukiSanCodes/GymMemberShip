<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('fee', 10, 2);
            $table->unsignedSmallInteger('validity_months')->default(1);
            $table->text('benefits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_levels');
    }
};
