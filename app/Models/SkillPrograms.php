<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkillPrograms extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_program_id',
        'skill_priority_id',
    ];

    public function qualificationTitle()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function skillPriority()
    {
        return $this->belongsTo(SkillPriority::class, 'skill_priority_id');
    }

}
