<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionRecognition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tvi_id',
        'recognition_id',
        'year',
    ];

    /**
     * Get the TVI associated with this institution recognition.
     */
    public function tvi()
    {
        return $this->belongsTo(Tvi::class);
    }

    /**
     * Get the recognition associated with this institution recognition.
     */
    public function recognition()
    {
        return $this->belongsTo(Recognition::class);
    }
}
