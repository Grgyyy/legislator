<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionRecognition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tvi_id',
        'recognition_id',
        'accreditation_date',
        'expiration_date'
    ];

    public function tvi()
    {
        return $this->belongsTo(Tvi::class);
    }

    public function recognition()
    {
        return $this->belongsTo(Recognition::class);
    }
}
