<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRegion extends Model
{
    use HasFactory;


    public function region()
    {
        return $this->belongsToMany(Region::class, 'user_regions')->withTimestamps();
    }
    public function user()
    {
        return $this->belongsToMany(User::class, 'user_regions')->withTimestamps();
    }
    public function district()
    {
        return $this->belongsToMany(District::class, 'user_regions')->withTimestamps();
    }

}
