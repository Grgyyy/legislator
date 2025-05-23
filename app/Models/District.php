<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'municipality_id',
        'province_id',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function municipality()
    {
        return $this->belongsToMany(Municipality::class, 'district_municipalities')->withTimestamps();
    }

    public function underMunicipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id');
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    public function tvi()
    {
        return $this->hasMany(Tvi::class);
    }
    public function user()
    {
        return $this->belongsToMany(User::class, 'user_regions')->withTimestamps();
    }

    public function userRegions()
    {
        return $this->hasMany(UserRegion::class, 'user_id');
    }
}
