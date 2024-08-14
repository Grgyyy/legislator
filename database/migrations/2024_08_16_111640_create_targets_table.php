<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('province_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('scholarship_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvi_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('number_of_slots');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
