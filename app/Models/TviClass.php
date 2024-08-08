<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TviClass extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'name',
        'tvi_type_id'
    ];


    public function tviType()
    {
        return $this->belongsTo(tviType::class);
    }
}
