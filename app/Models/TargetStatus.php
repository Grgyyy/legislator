<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetStatus extends Model
{
    use HasFactory;

    public function target() {
        return $this->hasMany(related: Target::class);
    }

}
