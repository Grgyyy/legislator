<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'tvi_id',
        'training_program_id',
    ];

    public function tvi()
    {
        return $this->belongsTo(Tvi::class, 'tvi_id');
    }

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }
}
