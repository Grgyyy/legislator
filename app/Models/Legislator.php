<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Legislator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status_id'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function attributions()
    {
        return $this->hasMany(Allocation::class, 'attributor_id');
    }

    public function particular()
    {
        return $this->belongsToMany(Particular::class, 'legislator_particular')
            ->withTimestamps();
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }
}