<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'allocation_id',
        'district_id',
        'municipality_id',
        'tvi_id',
        'tvi_name',
        'qualification_title_id',
        'qualification_title_code',
        'qualification_title_soc_code',
        'qualification_title_name',
        'abdd_id',
        'delivery_mode_id',
        'learning_mode_id',
        'number_of_slots',
        'attribution_allocation_id',
        'total_training_cost_pcc',
        'total_cost_of_toolkit_pcc',
        'total_training_support_fund',
        'total_assessment_fee',
        'total_entrepreneurship_fee',
        'total_new_normal_assisstance',
        'total_accident_insurance',
        'total_book_allowance',
        'total_uniform_allowance',
        'total_misc_fee',
        'admin_cost',
        'total_amount',
        'appropriation_type',
        'description',
        'attribution_allocation_id',
    ];

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function attributionAllocation()
    {
        return $this->belongsTo(Allocation::class, 'attribution_allocation_id');
    }

    public function tvi()
    {
        return $this->belongsTo(Tvi::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function deliveryMode()
    {
        return $this->belongsTo(DeliveryMode::class);
    }

    public function learningMode()
    {
        return $this->belongsTo(LearningMode::class);
    }

    public function qualification_title()
    {
        return $this->belongsTo(QualificationTitle::class);
    }

    public function abdd() {
        return $this->belongsTo(Abdd::class);
    }
}
