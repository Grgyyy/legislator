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
        Schema::create('non_compliant_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('target_remarks_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->longText('others_remarks')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_compliant_remarks');
    }
};
