<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvinceAbdd extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id',
        'abdd_id',
        'available_slots',
        'total_slots',
        'year',
    ];

    public function provinces()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function abdds()
    {
        return $this->belongsTo(Abdd::class, 'abdd_id');
    }
}
