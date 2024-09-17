<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubParticular extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fund_source_id'
    ];

    public function particular() {
        return $this->hasMany(Particular::class);
    }

    public function fundSource() {
        return $this->belongsTo(FundSource::class);
    }
}
