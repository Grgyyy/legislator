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
        Schema::create('target_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('abscap_id')
                ->nullable();
            $table->integer('rqm_code')
                ->unique()
                ->nullable();
            $table->foreignId('target_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('allocation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('district_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tvi_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('tvi_name');
            $table->foreignId('qualification_title_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('qualification_title_code')
                ->nullable();
            $table->string('qualification_title_soc_code');
            $table->string('qualification_title_name');
            $table->foreignId('abdd_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('delivery_mode_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('learning_mode_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('number_of_slots');
            $table->decimal('total_training_cost_pcc', 15, 2)
                ->default(0);
            $table->decimal('total_cost_of_toolkit_pcc', 15, 2)
                ->default(0);
            $table->decimal('total_training_support_fund', 15, 2)
                ->default(0);
            $table->decimal('total_assessment_fee', 15, 2)
                ->default(0);
            $table->decimal('total_entrepreneurship_fee', 15, 2)
                ->default(0);
            $table->decimal('total_new_normal_assisstance', 15, 2)
                ->default(0);
            $table->decimal('total_accident_insurance', 15, 2)
                ->default(0);
            $table->decimal('total_book_allowance', 15, 2)
                ->default(0);
            $table->decimal('total_uniform_allowance', 15, 2)
                ->default(0);
            $table->decimal('total_misc_fee', 15, 2)
                ->default(0);
            $table->decimal('total_amount', 15, 2)
                ->default(0);
            $table->string('appropriation_type');
            $table->string('description');
            $table->foreignId('user_id')
                ->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.php
     */
    public function down(): void
    {
        Schema::dropIfExists('target_histories');
    }
};
