<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'user_id',
        'content'
    ];

    public function target() {
        return $this->belongsTo(Target::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }


}
