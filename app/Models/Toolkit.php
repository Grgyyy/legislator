<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Toolkit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lot_name',
        'price_per_toolkit',
        'number_of_toolkit',
        'available_number_of_toolkit',
        'total_abc_per_lot',
        'number_of_items_per_toolkit',
        'year'
    ];
}
