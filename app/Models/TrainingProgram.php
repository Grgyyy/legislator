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
        'soc_code',
        'full_coc_ele',
        'nc_level',
        'title',
        'tvet_id',
        'priority_id',
        'soc'
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

    public function tvis()
    {
        return $this->belongsToMany(Tvi::class, 'institution_programs')
            ->withTimestamps();
    }

    public function skillPriority()
    {
        return $this->belongsToMany(SkillPriority::class, 'skill_programs')
            ->withTimestamps();
    }
}