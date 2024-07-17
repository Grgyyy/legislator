<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Legislator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
