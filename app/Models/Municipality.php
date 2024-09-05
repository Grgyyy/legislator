<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
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

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueMunicipality();
        });
    }


    public function validateUniqueMunicipality()
    {
        $query = self::withTrashed()
            ->where('name', $this->name)
            ->where('province_id', $this->province_id);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $municipality = $query->first();


        if ($municipality) {
            if ($municipality->deleted_at) {
                $message = 'A Municipality with this name and Province exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Municipality with this name and Province already exists.';
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
