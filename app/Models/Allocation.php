<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



class Allocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'soft_or_commitment',
        'legislator_id',
        'attributor_id',
        'particular_id',
        'attributor_particular_id',
        'scholarship_program_id',
        'allocation',
        'admin_cost',
        'balance',
        'year'
    ];

    public function legislator()
    {
        return $this->belongsTo(Legislator::class);
    }

    public function attributor()
    {
        return $this->belongsTo(Legislator::class, 'attributor_id');
    }

    public function scholarship_program()
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    public function attributorParticular()
    {
        return $this->belongsTo(Particular::class, 'attributor_particular_id');
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

    public function targetHistory()
    {
        return $this->hasMany(targetHistory::class);
    }

}
