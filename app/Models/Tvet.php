<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tvet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];
}
