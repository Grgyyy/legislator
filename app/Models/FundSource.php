<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function subParticular() {
        return $this->hasMany(SubParticular::class);
    }
}
