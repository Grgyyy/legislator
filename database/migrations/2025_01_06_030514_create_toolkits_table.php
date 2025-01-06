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
        Schema::create('toolkits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_title_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('price_per_toolkit', 15, 2)->default(0);
            $table->integer('number_of_toolkit')->default(0);
            $table->decimal('total_abc_per_lot', 15, 2)->default(0);
            $table->integer('number_of_items_per_toolkit')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toolkits');
    }
};
