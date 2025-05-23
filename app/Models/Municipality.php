<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'class',
        'province_id',
    ];

    public function district()
    {
        return $this->belongsToMany(District::class, 'district_municipalities')->withTimestamps();
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
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
