<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'region_id',
        'district_id'
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function particular()
    {
        return $this->hasMany(Particular::class);
    }

    public function tvi()
    {
        return $this->hasMany(Tvi::class);
    }
    public function target()
    {
        return $this->hasMany(Target::class);
    }
}
