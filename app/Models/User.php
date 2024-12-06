<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

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
        'region_id', // Add region_id and province_id for the relationships
        'province_id',
    ];

    public function comments()
    {
        return $this->hasMany(TargetComment::class);
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

    /**
     * Determine if the user can access the Filament panel based on their roles.
     *
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Define roles that are explicitly allowed to access the panel
        $allowedRoles = [
            'Super Admin',
            'Admin',
            'SMD Head',
            'SMD Focal',
            'RO',
            'PO/DO',
            'TESDO',
        ];

        // Check if the user has any of the explicitly allowed roles
        if ($this->hasAnyRole($allowedRoles)) {
            return true;
        }

        // Additional check for dynamic region roles
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

        // Check if the user has any of the region-specific roles
        if ($this->hasAnyRole($regionRoles)) {
            return true;
        }

        // Default to denying access if no roles match
        return false;
    }
}
