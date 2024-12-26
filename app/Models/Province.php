<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;


class Province extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'region_id',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function district()
    {
        return $this->hasMany(District::class);
    }
    public function municipality()
    {
        return $this->hasMany(Municipality::class);
    }

    public function abdds()
    {
        return $this->belongsToMany(ABDD::class, 'province_abdds')
            ->withTimestamps();
    }

    public function skillPriorities()
    {
        return $this->belongsToMany(TrainingProgram::class, 'skill_priorities')
            ->withTimestamps();
    }
    public function users()
    {
        return $this->belongsTo(User::class);
    }



}
