<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryMode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'learning_mode_id'
    ];

    public function learningMode()
    {
        return $this->belongsTo(LearningMode::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }
}
