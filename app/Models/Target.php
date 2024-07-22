<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'legislator_id',
        'province_id',
        'scholarship_program_id',
        'tvi_id',
        'number_of_slots'
    ];

    public function legislator() {
        return $this->belongsTo(Legislator::class);
    }

    public function province() {
        return $this->belongsTo(Province::class);
    }

    public function scholarship_program() {
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function tvi() {
        return $this->belongsTo(Tvi::class);
    }
}
