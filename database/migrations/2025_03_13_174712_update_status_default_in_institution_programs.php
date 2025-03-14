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
        Schema::table('institution_programs', function (Blueprint $table) {
            DB::statement("ALTER TABLE institution_programs ALTER COLUMN status_id SET DEFAULT 1");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_programs', function (Blueprint $table) {
            DB::statement("ALTER TABLE institution_programs ALTER COLUMN status_id DROP DEFAULT");
        });
    }
};
