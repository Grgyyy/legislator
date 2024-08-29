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
        Schema::create('scholarship_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('training_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_trainings');
    }
};
