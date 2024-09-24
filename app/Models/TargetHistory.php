<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetHistory extends Model
{
    use HasFactory;

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function qualification_title()
    {
        return $this->belongsTo(QualificationTitle::class);
    }

    public function abdd() {
        return $this->belongsTo(Abdd::class);
    }
}
