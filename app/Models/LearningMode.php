<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class LearningMode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'acronym',
        'name',
    ];

    public function deliveryMode()
    {
        return $this->hasMany(DeliveryMode::class);
    }
}
