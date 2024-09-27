<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Allocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'soft_or_commitment',
        'legislator_id',
        'particular_id',
        'scholarship_program_id',
        'allocation',
        'admin_cost',
        'balance',
        'year'
    ];

    public function legislator()
    {
        return $this->belongsTo(Legislator::class);
    }

    public function scholarship_program()
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

    public function targetHistory()
    {
        return $this->hasMany(targetHistory::class);
    }

    public function getFormattedParticularAttribute()
    {
        $particular = $this->particular;

        if (!$particular) {
            return 'No Particular Available';
        }

        $district = $particular->district;
        $municipality = $district ? $district->municipality : null;
        $province = $municipality ? $municipality->province : null;

        $districtName = $district ? $district->name : 'Unknown District';
        $municipalityName = $municipality ? $municipality->name : 'Unknown Municipality';
        $provinceName = $province ? $province->name : 'Unknown Province';

        $subParticular = $particular->subParticular->name ?? 'Unknown Sub-Particular';

        if ($subParticular === 'Partylist') {
            return "{$subParticular} - {$particular->partylist->name}";
        } elseif (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
            return "{$subParticular}";
        } else {
            return "{$subParticular} - {$districtName}, {$municipalityName}";
        }
    }


}
