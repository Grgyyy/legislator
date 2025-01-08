<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Toolkit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'qualification_title_id',
        'price_per_toolkit',
        'number_of_toolkit',
        'available_number_of_toolkit',
        'total_abc_per_lot',
        'number_of_items_per_toolkit',
        'year',
    ];

    public function qualificationTitle()
    {
        return $this->belongsTo(QualificationTitle::class);
    }
}
