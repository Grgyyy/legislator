<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

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
        'entrepreneurship_fee',
        'new_normal_assisstance',
        'accident_insurance',
        'book_allowance',
        'uniform_allowance',
        'misc_fee',
        'hours_duration',
        'days_duration',
        'pcc',
        'status_id',
        'soc'
    ];

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function scholarshipProgram()
    {
        return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id');
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

    public function targetHistory()
    {
        return $this->hasMany(targetHistory::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function toolkit() {
        return $this->hasMany(Toolkit::class);
    }
}
