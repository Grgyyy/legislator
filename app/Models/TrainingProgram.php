<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
    ];

    public function scholarshipPrograms()
    {
        return $this->belongsToMany(ScholarshipProgram::class, 'scholarship_trainings');
    }

    public function qualificationTitle()
    {
        return $this->hasMany(QualificationTitle::class);
    }

    public function getFormattedScholarshipProgramsAttribute()
    {
        return $this->scholarshipPrograms->pluck('name')->implode(', ');
    }
}
