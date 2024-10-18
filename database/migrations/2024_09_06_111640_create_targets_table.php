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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvi_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('qualification_title_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('abdd_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('number_of_slots');

            $table->integer('total_training_cost_pcc')
                ->default(0);
            $table->integer('total_cost_of_toolkit_pcc')
                ->default(0);
            $table->integer('total_training_support_fund')
                ->default(0);
            $table->integer('total_assessment_fee')
                ->default(0);
            $table->integer('total_entrepreneurship_fee')
                ->default(0);
            $table->integer('total_new_normal_assisstance')
                ->default(0);
            $table->integer('total_accident_insurance')
                ->default(0);
            $table->integer('total_book_allowance')
                ->default(0);
            $table->integer('total_uniform_allowance')
                ->default(0);
            $table->integer('total_misc_fee')
                ->default(0);
            $table->integer('total_amount')
                ->default(1);
            $table->string('appropriation_type');
            $table->foreignId('target_status_id')
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
        Schema::dropIfExists('targets');
    }
};
