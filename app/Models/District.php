<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class District extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'province_id',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function municipality() {
        return $this->hasMany(Municipality::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    public function tvi()
    {
        return $this->hasMany(Tvi::class);
    }
}
