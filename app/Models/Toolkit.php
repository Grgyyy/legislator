<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toolkit extends Model
{
    use HasFactory;

    public function qualificationTitle()
    {
        return $this->belongsTo(QualificationTitle::class);
    }
}
