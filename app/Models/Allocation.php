<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legislator_id',
        'particular_id',
        'scholarship_program_id',
        'allocation',
        'admin_cost',
        'balance',
        'year'
    ];

    public function legislator(){
        return $this->belongsTo(Legislator::class);
    }

    public function scholarship_program(){
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function particular() {
        return $this->belongsTo(Particular::class);
    }
}
