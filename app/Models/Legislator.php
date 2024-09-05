<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Legislator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status_id'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function particular()
    {
        return $this->belongsToMany(Particular::class, 'legislator_particular')
            ->withTimestamps();
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }
    public function getFormattedParticularAttribute()
    {
        return $this->particular->map(function ($particular) {
            $district = $particular->district;
            $municipality = $district ? $district->municipality : null;
            $province = $municipality ? $municipality->province : null;

            $particularName = $particular->name;
            $districtName = $district ? $district->name : '';
            $municipalityName = $municipality ? $municipality->name : '';
            $provinceName = $province ? $province->name : '';

            return trim("{$particularName} - {$districtName}, {$municipalityName}, {$provinceName}", ', ');
        })->implode(', ');
    }
}
