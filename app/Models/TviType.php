<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class TviType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',

    ];

    public function tviClasses()
    {
        return $this->hasMany(TviClass::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueTviType();
        });
    }


    public function validateUniqueTviType()
    {
        $query = self::withTrashed()
            ->where('name', $this->name);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $tvi_type = $query->first();


        if ($tvi_type) {
            if ($tvi_type->deleted_at) {
                $message = 'A TVI Type data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A TVI Type data already exists.';
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
