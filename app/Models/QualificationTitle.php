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
        'scholarship_program_id',
        'sector_id',
        'duration',
        'training_cost_pcc',
        'cost_of_toolkit_pcc',
        'status_id',
    ];

    public function scholarshipProgram()
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
    public function status()
    {
        return $this->belongsTo(Status::class);
    }


}

