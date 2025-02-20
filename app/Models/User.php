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
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Correct relationship methods
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
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
