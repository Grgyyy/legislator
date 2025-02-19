<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetSeenBy extends Model
{
    use HasFactory;

    protected $fillable = ['target_id', 'user_id'];

    protected $table = 'target_seen_by';
    
    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}