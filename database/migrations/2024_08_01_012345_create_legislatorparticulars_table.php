<?php

use App\Models\Legislator;
use App\Models\Particular;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('legislatorparticular', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Legislator::class);
            $table->foreignIdFor(Particular::class);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legislatorparticular');
    }
};
