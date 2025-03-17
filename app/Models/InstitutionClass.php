<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name'
    ];


    public function tvi()
    {
        return $this->hasMany(Tvi::class);
    }
}
