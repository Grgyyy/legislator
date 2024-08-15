<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Legislator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function particular()
    {
        return $this->belongsToMany(Particular::class, 'LegislatorParticular')->withTimestamps();
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }
}
