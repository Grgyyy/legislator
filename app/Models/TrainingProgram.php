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
}
