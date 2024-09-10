<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Tvet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function target() {
        return $this->hasMany(Target::class);
    }

    public function trainingProgram() {
        return $this->hasMany(TrainingProgram::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueTvet();
        });
    }


    public function validateUniqueTvet()
    {
        $query = self::withTrashed()
            ->where('name', $this->name);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $Tvet = $query->first();


        if ($Tvet) {
            if ($Tvet->deleted_at) {
                $message = 'A TVET Sector data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A TVET Sector data already exists.';
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
