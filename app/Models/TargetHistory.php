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
        'tvi_id',
        'abdd_id',
        'qualification_title_id',
        'number_of_slots',
        'total_training_cost_pcc',
        'total_cost_of_toolkit_pcc',
        'total_training_support_fund',
        'total_assessment_fee',
        'total_entrepeneurship_fee',
        'total_new_normal_assisstance',
        'total_accident_insurance',
        'total_book_allowance',
        'total_uniform_allowance',
        'total_misc_fee',
        'total_amount',
        'appropriation_type'
    ];

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function qualification_title()
    {
        return $this->belongsTo(QualificationTitle::class);
    }

    public function abdd() {
        return $this->belongsTo(Abdd::class);
    }
}
