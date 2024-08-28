<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualificationTitle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'duration',
        'training_cost_pcc',
        'cost_of_toolkit_pcc',
        'status_id',
    ];

    public function trainingPrograms() {
        return $this->belongsTo(TrainingProgram::class);
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
