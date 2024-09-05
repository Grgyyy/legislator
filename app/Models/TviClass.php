<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class TviClass extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'name',
        'tvi_type_id'
    ];


    public function tviType()
    {
        return $this->belongsTo(tviType::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueTviClassA();
        });
    }


    public function validateUniqueTviClassA()
    {
        $query = self::withTrashed()
            ->where('name', $this->name)
            ->where('tvi_type_id', $this->tvi_type_id);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $class_a = $query->first();


        if ($class_a) {
            if ($class_a->deleted_at) {
                $message = 'A Institution Class A data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Institution Class A data already exists.';
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
