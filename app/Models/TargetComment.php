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
        'content',
        'is_read'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            $comment->user_id = auth()->id();
        });
    }

    public function readByUsers()
    {
        return $this->hasMany(TargetCommentRead::class, 'target_comment_id');
    }

    public function isReadByUser($userId)
    {
        return $this->readByUsers()->where('user_id', $userId)->exists();
    }

    public function target() {
        return $this->belongsTo(Target::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }


}
