<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Particular extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'province_id',
    ];

    public function legislator() {
        return $this->belongsToMany(Legislator::class, 'LegislatorParticular')->withTimestamps();
    }

    public function province() {
        return $this->belongsTo(Province::class);
    }
}
