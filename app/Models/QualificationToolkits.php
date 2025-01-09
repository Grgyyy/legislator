<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualificationToolkits extends Model
{
    use HasFactory, SoftDeletes;
    
    public function qualificationTitles()
    {
        return $this->belongsTo(QualificationTitle::class, 'qualification_title_id');
    }

    public function toolkits()
    {
        return $this->belongsTo(Toolkit::class, 'qualification_title_id');
    }
}
