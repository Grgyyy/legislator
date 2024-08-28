<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipTraining extends Model
{
    use HasFactory;

    protected $fillable = [
        'scholarship_program_id',
        'training_program_id',
    ];

}
