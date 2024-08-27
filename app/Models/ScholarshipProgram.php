<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'desc',
    ];


    public function allocation() {
        return $this->hasMany(Allocation::class);
    }

    public function qualificationTitle()
    {
        return $this->belongsToMany(QualificationTitle::class, 'QualificationScholarship')
            ->withTimestamps();
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }
}
