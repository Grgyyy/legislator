<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistrictMunicipality extends Model
{
    use HasFactory;


    public function district()
    {
        return $this->belongsToMany(District::class, 'district_municipalities');
    }

    public function municipality()
    {
        return $this->belongsToMany(Municipality::class, 'district_municipalities');
    }
}
