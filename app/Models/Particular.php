<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Particular extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sub_particular_id',
        'district_id',
    ];

    public function subParticular() {
        return $this->belongsTo(SubParticular::class);
    }

    public function legislator()
    {
        return $this->belongsToMany(Legislator::class, 'legislator_particular')->withTimestamps();
    }

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}
