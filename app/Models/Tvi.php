<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Tvi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'institution_class_id',
        'tvi_class_id',
        'tvi_type_id',
        'district_id',
        'municipality_id',
        'address',
    ];

    public function tviType() 
    {
        return $this->belongsTo(TviType::class);
    }

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

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

    public function targetHistory()
    {
        return $this->hasMany(targetHistory::class);
    }

    public function recognitions()
    {
        return $this->belongsToMany(Recognition::class, 'institution_recognitions')
            ->withTimestamps();
    }

    public function trainingPrograms()
    {
        return $this->belongsToMany(TrainingProgram::class, 'institution_programs')
            ->withTimestamps();
    }

}
