<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonCompliantRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'target_remarks_id',
        'others_remarks'
    ];

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function target_remarks()
    {
        return $this->belongsTo(TargetRemark::class);
    }

}
