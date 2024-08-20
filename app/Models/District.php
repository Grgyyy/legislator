<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'municipality_id',
    ];

    public function municipality() {
        return $this->belongsTo(Municipality::class);
    }

    public function particular() {
        return $this->belongsTo(Particular::class);
    }

    public function tvi() {
        return $this->hasMany(Tvi::class);
    }
}
