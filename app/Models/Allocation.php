<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'twsp_allocation',
        'twsp_admin_cost',
        'step_allocation',
        'step_admin_cost',
        'legislator_id',
    ];

    public function legislator(){
        return $this->belongsTo(Legislator::class);
    }
}
