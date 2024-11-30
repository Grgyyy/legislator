<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function learningMode()
    {
        return $this->belongsTo(LearningMode::class);
    }
}
