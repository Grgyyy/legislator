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
        'user_id'
    ];

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }

    public function user()
    {
        return $this->belongsToMany(User::class, 'user_regions')->withTimestamps();
    }
}
