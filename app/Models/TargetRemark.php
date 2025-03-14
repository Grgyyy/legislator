<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetRemark extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'remarks',
    ];

    public function non_compliant()
    {
        return $this->hasMany(NonCompliantRemark::class);
    }


}
