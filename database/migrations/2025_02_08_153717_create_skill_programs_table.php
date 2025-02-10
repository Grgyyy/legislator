<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skill_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')
                ->constrained('training_programs')
                ->cascadeOnDelete();
            $table->foreignId('skill_priority_id')
                ->nullable()
                ->constrained('skill_priorities')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_programs');
    }
};
