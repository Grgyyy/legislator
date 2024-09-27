<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

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
    // public function getFormattedParticularAttribute()
    // {
    //     return $this->particular->map(function ($particular) {
    //         $district = $particular->district;
    //         $municipality = $district ? $district->municipality : null;
    //         $province = $municipality ? $municipality->province : null;

    //         $particularName = $particular->name;
    //         $districtName = $district ? $district->name : '';
    //         $municipalityName = $municipality ? $municipality->name : '';
    //         $provinceName = $province ? $province->name : '';

    //         return trim("{$particularName} - {$districtName}, {$municipalityName}, {$provinceName}", ', ');
    //     })->implode(', ');
    // }

    public function getFormattedParticularAttribute()
    {
        return $this->particular->map(function ($particular) {
            $district = $particular->district;
            $municipality = $district ? $district->municipality : null;

            $subParticular = $particular->subParticular ? $particular->subParticular->name : null;
            $formattedName = '';

            if ($subParticular === 'Senator' || $subParticular === 'House Speaker' || $subParticular === 'House Speaker (LAKAS)') {
                $formattedName = "{$subParticular}";
            } elseif ($subParticular === 'Partylist') {
                $formattedName = "{$subParticular} - {$particular->partylist->name}";
            } else {
                $districtName = $district ? $district->name : '';
                $municipalityName = $municipality ? $municipality->name : '';
                $province = $municipality ? $municipality->province : null;
                $provinceName = $province ? $province->name : '';

                $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
            }

            return trim($formattedName, ', ');
        })->implode(', ');
    }

}
