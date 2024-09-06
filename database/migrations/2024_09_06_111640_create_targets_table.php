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
            $table->foreignId('allocation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvi_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('priority_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvet_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('abdd_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('qualification_title_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('number_of_slots');
            $table->string('total_amount')
                ->default(0);
            $table->foreignId('status_id')
                ->default(3)
                ->constrained()
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
        Schema::dropIfExists('targets');
    }
};
