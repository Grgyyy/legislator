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
        'training_program_id',
        'available_slots',
        'total_slots',
        'year',
    ];

    public function provinces()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function trainingPrograms()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }
}
