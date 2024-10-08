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
            $table->foreignId('training_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('scholarship_program_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('training_cost_pcc', 10, 2)->default(0);
            $table->decimal('cost_of_toolkit_pcc', 10, 2)->default(0);
            $table->decimal('training_support_fund', 10, 2)->default(0);
            $table->decimal('assessment_fee', 10, 2)->default(0);
            $table->decimal('entrepreneurship_fee', 10, 2)->default(0);
            $table->decimal('new_normal_assisstance', 10, 2)->default(0);
            $table->decimal('accident_insurance', 10, 2)->default(0);
            $table->decimal('book_allowance', 10, 2)->default(0);
            $table->decimal('uniform_allowance', 10, 2)->default(0);
            $table->decimal('misc_fee', 10, 2)->default(0);
            $table->integer('hours_duration')->default(0);
            $table->integer('days_duration')->default(0);
            $table->decimal('pcc', 10, 2)->default(0);
            $table->foreignId('status_id')
                ->default(1)
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
        Schema::dropIfExists('qualification_titles');
    }
};
