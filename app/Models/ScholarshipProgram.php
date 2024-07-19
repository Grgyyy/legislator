<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'qualification_title',
        'training_cost',
        'toolkit_cost'
    ];
}
