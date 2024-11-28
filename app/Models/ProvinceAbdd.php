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
        'slots'
    ];
}
