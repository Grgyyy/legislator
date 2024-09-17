<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubParticular extends Model
{
    use HasFactory;

    public function particular() {
        return $this->hasMany(Particular::class);
    }
}
