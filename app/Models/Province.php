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

    public function skillPriorities()
    {
        return $this->belongsToMany(TrainingProgram::class, 'skill_priorities')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'user_regions')->withTimestamps();
    }
}
