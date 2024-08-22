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


    public function legislator() {
        return $this->hasMany(Legislator::class);
    }


    public function sector()
    {
        return $this->hasMany(Sector::class);
    }
    public function qualificationTitle()
    {
        return $this->hasMany(QualificationTitle::class);
    }
}
