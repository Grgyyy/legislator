<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('municipality_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_regions');
    }
};
