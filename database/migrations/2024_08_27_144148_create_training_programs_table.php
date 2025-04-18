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
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')
                // ->unique()
                ->nullable();
            $table->string('soc_code')
                ->unique();
            $table->string('full_coc_ele');
            $table->string('nc_level')
                ->nullable();    
            $table->string('title');
            $table->foreignId('priority_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvet_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->boolean('soc')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};
