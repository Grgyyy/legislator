<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Target extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'allocation_id',
        'tvi_id',
        'priority_id',
        'tvet_id',
        'abdd_id',
        'qualification_title_id',
        'number_of_slots',
        'total_amount',
        'remarks',
        'status_id',
    ];

    public function allocation() {
        return $this->belongsTo(Allocation::class);
    }

    public function tvi() {
        return $this->belongsTo(Tvi::class);
    }

    public function priority() {
        return $this->belongsTo(Priority::class);
    }

    public function tvet() {
        return $this->belongsTo(Tvet::class);
    }

    public function abdd() {
        return $this->belongsTo(Abdd::class);
    }

    public function qualification_title() {
        return $this->belongsTo(QualificationTitle::class);
    }

    public function status() {
        return $this->belongsTo(Status::class);
    }
}
