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
        Schema::create('qualification_titles', function (Blueprint $table) {
            $table->id();
            $table->tinyText('code')->unique();
            $table->string('title');
            $table->foreignId('scholarship_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('sector_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('duration')->default(0);
            $table->decimal('training_cost_pcc', 10, 2)->default(0);
            $table->decimal('cost_of_toolkit_pcc', 10, 2)->default(0);
            $table->foreignId('status_id')
                ->constrained()
                ->cascadeOnDelete();
            // ->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualification_titles');
    }
};
