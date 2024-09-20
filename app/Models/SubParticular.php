<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubParticular extends Model
{
    use HasFactory, SoftDeletes;

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
