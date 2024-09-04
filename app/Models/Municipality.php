<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Municipality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'province_id',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->hasMany(District::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueMunicipality();
        });
    }

    public function validateUniqueMunicipality()
    {
        $query = self::where('name', $this->name)
            ->where('province_id', $this->province_id)
            ->whereNotNull('deleted_at');

        if ($this->id) {
            $query->where('id', '<>', $this->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A municipality with this name and province already exists and is marked as deleted.',
            ]);
        }

        $query = self::where('name', $this->name)
            ->where('province_id', $this->province_id)
            ->whereNull('deleted_at');

        if ($this->id) {
            $query->where('id', '<>', $this->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A municipality with this name and province already exists.',
            ]);
        }
    }


}
