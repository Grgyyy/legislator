<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class District extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'municipality_id',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    public function tvi()
    {
        return $this->hasMany(Tvi::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueDistrict();
        });
    }


    public function validateUniqueDistrict()
    {
        $query = self::withTrashed()
            ->where('name', $this->name)
            ->where('municipality_id', $this->municipality_id);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $district = $query->first();


        if ($district) {
            if ($district->deleted_at) {
                $message = 'A District with this name and Municipality exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A District with this name and Municipality already exists.';
            }
            $this->handleValidationException($message);
        }
    }

    protected function handleValidationException($message)
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();
        throw ValidationException::withMessages([
            'name' => $message,
        ]);
    }

}
