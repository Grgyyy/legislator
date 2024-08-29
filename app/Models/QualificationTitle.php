<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualificationTitle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_program_id',
        'scholarship_program_id',
        'training_cost_pcc',
        'cost_of_toolkit_pcc',
        'training_support_fund',
        'assessment_fee',
        'entrepeneurship_fee',
        'new_normal_assisstance',
        'accident_insurance',
        'book_allowance',
        'duration',
        'status_id',
    ];

    public function trainingPrograms() {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function tvet() {
        return $this->belongsTo(Tvet::class);
    }

    public function priority() {
        return $this->belongsTo(Priority::class);
    }

    public function scholarshipPrograms()
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
