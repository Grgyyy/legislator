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
        Schema::create('skill_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')
                ->constrained('provinces')
                ->cascadeOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->cascadeOnDelete();
            $table->string('qualification_title');
            $table->integer('available_slots');
            $table->integer('total_slots');
            $table->year('year');
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
        Schema::dropIfExists('skill_priorities');
    }
};
