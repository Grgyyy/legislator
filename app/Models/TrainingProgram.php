<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'tvet_id',
        'priority_id',
    ];

    public function scholarshipPrograms()
    {
        return $this->belongsToMany(ScholarshipProgram::class, 'scholarship_trainings');
    }

    public function qualificationTitle()
    {
        return $this->hasMany(QualificationTitle::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function tvet()
    {
        return $this->belongsTo(Tvet::class);
    }

    public function getFormattedScholarshipProgramsAttribute()
    {
        return $this->scholarshipPrograms->pluck('name')->implode(', ');
    }
}
