<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'remarks',
    ];

    public function non_compliant()
    {
        return $this->hasMany(NonCompliantRemark::class);
    }


}
