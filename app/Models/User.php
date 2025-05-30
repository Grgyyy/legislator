<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
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
    ];

    protected $with = ['region', 'province', 'municipality', 'district'];

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

    public function district()
    {
        return $this->belongsToMany(District::class, 'user_regions')->withTimestamps();
    }
    public function municipality()
    {
        return $this->belongsToMany(Municipality::class, 'user_regions')->withTimestamps();
    }

    public function userRegions()
    {
        return $this->hasMany(UserRegion::class, 'user_id');
    }


    public function canAccessPanel(Panel $panel): bool
    {
        $allowedRoles = DB::table('roles')->pluck('name')->toArray();

        if ($this->hasAnyRole($allowedRoles)) {
            return true;
        }

        $regionRoles = Region::table('regions')->pluck('name')->toArray();

        foreach ($regionRoles as $regionRole) {
            if ($this->hasRole($regionRole) && $this->region->contains('name', $regionRole)) {
                return true;
            }
        }

        return false;
    }

}
