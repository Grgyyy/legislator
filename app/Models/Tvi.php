<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Tvi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'institution_class_id',
        'tvi_class_id',
        'district_id',
        'address',
    ];

    public function tviClass()
    {
        return $this->belongsTo(TviClass::class);
    }
    public function InstitutionClass()
    {
        return $this->belongsTo(InstitutionClass::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

    public function targetHistory()
    {
        return $this->hasMany(targetHistory::class);
    }

    public function getFormattedDistrictAttribute()
    {
        $district = $this->district;

        if (!$district) {
            return 'No District Information';
        }

        $municipality = $district->municipality;
        $province = $municipality ? $municipality->province : null;

        $districtName = $district ? $district->name : 'Unknown District';
        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
        $provinceName = $province ? $province->name : 'Unknown Province';

        return trim("{$districtName} - {$municipalityName}, {$provinceName}", ', ');
    }

}
