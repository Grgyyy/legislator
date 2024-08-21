<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tvi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'institution_class_id',
        'tvi_class_id',
        'district_id',
        'address',
    ];

    public function tviClass()
    {
        return $this->belongsTo(TviClass::class);
    }
    public function InstitutionClass()
    {
        return $this->belongsTo(InstitutionClass::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

}
