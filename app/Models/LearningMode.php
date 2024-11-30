<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function deliveryMode()
    {
        return $this->hasMany(DeliveryMode::class);
    }
}
