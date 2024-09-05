<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class InstitutionClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name'
    ];


    public function tvi()
    {
        return $this->hasMany(Tvi::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueTviClassB();
        });
    }


    public function validateUniqueTviClassB()
    {
        $query = self::withTrashed()
            ->where('name', $this->name);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $class_b = $query->first();


        if ($class_b) {
            if ($class_b->deleted_at) {
                $message = 'A Institution Class B data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Institution Class B data already exists.';
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
