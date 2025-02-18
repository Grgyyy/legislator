<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'region_id',
        'province_id',
    ];

    public function comments()
    {
        return $this->hasMany(TargetComment::class);
    }

    public function seenTargets()
    {
        return $this->belongsToMany(Target::class, 'target_seen_by');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function region()
    {
        return $this->belongsToMany(Region::class, 'user_regions')->withTimestamps();
    }

    public function province()
    {
        return $this->belongsToMany(Province::class, 'user_regions')->withTimestamps();
    }

    public function districtMunicipalities()
    {
        return $this->belongsToMany(DistrictMunicipality::class, 'user_regions')->withTimestamps();
    }

    public function district()
    {
        return $this->belongsToMany(District::class, 'district_municipalities', 'municipality_id', 'district_id')->withTimestamps();
    }

    public function municipalities()
    {
        return $this->belongsToMany(Municipality::class, 'district_municipalities', 'district_id', 'municipality_id')->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $allowedRoles = [
            'Super Admin',
            'Admin',
            'Director',
            'SMD Head',
            'SMD Focal',
            'Planning Office',
            'RO',
            'PO/DO',
            'TESDO',
        ];

        if ($this->hasAnyRole($allowedRoles)) {
            return true;
        }

        $regionRoles = [
            'Region I',
            'Region II',
            'Region III',
            'Region IV-A',
            'Region IV-B',
            'Region V',
            'Region VI',
            'Region VII',
            'Region VIII',
            'Region IX',
            'Region X',
            'Region XI',
            'Region XII',
            'NCR',
            'CAR',
            'CARAGA',
            'Negros Island Region',
            'BARMM'
        ];

        foreach ($regionRoles as $regionRole) {
            if ($this->hasRole($regionRole)) {
                if ($this->region && $this->region->name === $regionRole) {
                    return true;
                }
            }
        }
        return false;
    }
}
