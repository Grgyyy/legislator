<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Particular extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function legislator() {
        return $this->belongsTo(Legislator::class);
    }
}
