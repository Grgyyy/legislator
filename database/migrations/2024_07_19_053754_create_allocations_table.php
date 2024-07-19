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
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->decimal('twsp_allocation', 10, 2);
            $table->decimal('twsp_admin_cost', 10, 2)->default(0);
            $table->decimal('step_allocation', 10, 2);
            $table->decimal('step_admin_cost', 10, 2)->default(0);
            $table->foreignId('legislator_id')
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
        Schema::dropIfExists('allocations');
    }
};
