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
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legislator_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('particular_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('scholarship_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('allocation', 15, 2);
            $table->decimal('admin_cost', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->integer('year');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
