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

    public function scholarshipProgram()
    {
        return $this->belongsToMany(ScholarshipProgram::class, 'ScholarshipTraining');
    }
}
