<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Particular extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sub_particular_id',
        'partylist_id',
        'district_id',
    ];

    public function subParticular()
    {
        return $this->belongsTo(SubParticular::class);
    }

    public function legislator()
    {
        return $this->belongsToMany(Legislator::class, 'legislator_particular')->withTimestamps();
    }

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function partylist()
    {
        return $this->belongsTo(Partylist::class);
    }
}
