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
        Schema::create('institution_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tvi_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('training_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('status_id')
            ->nullable()
            ->constrained('statuses')
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
        Schema::dropIfExists('institution_programs');
    }
};
