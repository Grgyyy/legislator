<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class LearningMode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    
    public function deliveryMode()
    {
        return $this->belongsToMany(DeliveryMode::class, 'delivery_learnings')->withTimestamps();
    }
    
    public function target()
    {
        return $this->hasMany(Target::class);
    }
}
