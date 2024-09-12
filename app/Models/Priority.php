<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Priority extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function target()
    {
        return $this->hasMany(Target::class);
    }
<<<<<<< HEAD

    public function trainingProgram() {
        return $this->hasMany(TrainingProgram::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniquePriority();
        });
    }


    public function validateUniquePriority()
    {
        $query = self::withTrashed()
            ->where('name', $this->name);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $priority = $query->first();


        if ($priority) {
            if ($priority->deleted_at) {
                $message = 'A Top Ten Priority Sector data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Top Ten Priority Sector data already exists.';
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
=======
>>>>>>> bc78683 (Modify Allocation, District, Institution Class, Legislator, Municipality, Particular, Priority, Province, Qualification Title, Region, Scholarship Program, Training Program, TVET, TviClass, TVItype  validation and Exception and integrate it from model to the source model)
}
