<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
    ];

    // A Region can have many Provinces
    public function provinces()
    {
        return $this->hasMany(Province::class);
    }

    // A Region can have many Users
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
