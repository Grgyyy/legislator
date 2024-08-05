<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'qualification_title',
        'qualification_code',
        'training_cost',
        'toolkit_cost'
    ];

    public function target() {
        return $this->hasMany(Target::class);
    }
}
