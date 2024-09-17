<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fund_source_id'
    ];

    public function subParticular() {
        return $this->belongsTo(SubParticular::class);
    }
}
