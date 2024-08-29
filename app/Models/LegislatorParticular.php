<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegislatorParticular extends Model
{
    use HasFactory;

    public function particular()
    {
        return $this->belongsToMany(Particular::class, 'legislator_particular')
            ->withTimestamps();
    }

    public function legislator()
    {
        return $this->belongsToMany(Legislator::class, 'legislator_particular')
            ->withTimestamps();
    }

}
