<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;


    protected $fillable = [
        'desc',
    ];


    public function legislator()
    {
        return $this->hasMany(Legislator::class);
    }


    public function qualificationTitle()
    {
        return $this->hasMany(QualificationTitle::class);
    }

    public function scholarshipPrograms()
    {
        return $this->hasMany(ScholarshipProgram::class);
    }

    public function tvis()
    {
        return $this->hasMany(Tvi::class);
    }
}
