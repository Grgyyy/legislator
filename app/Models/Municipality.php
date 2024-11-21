<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Municipality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'class',
        'province_id',
    ];

    public function district()
    {
        return $this->belongsToMany(District::class, 'district_municipalities')->withTimestamps();
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}
