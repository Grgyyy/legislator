<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkillPriority extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'province_id',
        'district_id',
        'qualification_title',
        'available_slots',
        'total_slots',
        'year',
        'status_id'
    ];

    public function provinces()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function trainingProgram()
    {
        return $this->belongsToMany(TrainingProgram::class, 'skill_programs')
            ->withTimestamps();
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
