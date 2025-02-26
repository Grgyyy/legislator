<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistrictMunicipality extends Model
{
    use HasFactory;

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_regions')->withTimestamps();
    }
}
