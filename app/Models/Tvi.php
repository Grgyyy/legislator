<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tvi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'district',
        'province_id',
        'municipality_class',
        'tvi_type',
        'tvi_class',
        'address',
    ];

    public function target() {
        return $this->hasMany(Target::class);
    }

    public function province() {
        return $this->belongsTo(Province::class);
    }

}
