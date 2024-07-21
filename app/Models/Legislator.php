<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Legislator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legislator_name',
        'particular'
    ];

    public function allocation(){
        return $this->hasMany(Allocation::class);
    }

    public function target() {
        return $this->hasMany(Target::class);
    }
}
