<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetCommentRead extends Model
{
    use HasFactory;

    protected $fillable = ['target_comment_id', 'user_id'];

    public function comment()
    {
        return $this->belongsTo(TargetComment::class, 'target_comment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}