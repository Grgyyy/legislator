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
        Schema::create('tvis', function (Blueprint $table) {
            $table->id();
            $table->string('school_id')
                ->nullable();
            $table->string('name');
            $table->foreignId('district_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvi_class_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('institution_class_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('status_id')
                ->default(1)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tvis');
    }
};
