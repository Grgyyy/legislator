<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualificationScholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'qualification_title_id',
        'scholarship_program_id'
    ];

    public function qualificationTitles()
    {
        return $this->hasMany(QualificationTitle::class);
    }

    public function scholarshipPrograms()
    {
        return $this->hasMany(ScholarshipProgram::class);
    }
}
