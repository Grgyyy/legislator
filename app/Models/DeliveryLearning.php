<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLearning extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_mode_id',
        'learning_mode_id'
    ];


    public function learningMode()
    {
        return $this->belongsToMany(LearningMode::class, 'institution_recognitions')
            ->withTimestamps();
    }

    public function deliveryMode()
    {
        return $this->belongsToMany(DeliveryMode::class, 'institution_recognitions')
            ->withTimestamps();
    }
}
