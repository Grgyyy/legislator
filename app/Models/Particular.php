<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Particular extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'district_id',
    ];

    public function legislator()
    {
        return $this->belongsToMany(Legislator::class, 'legislator_particular')->withTimestamps();
    }

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }


    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueParticular();
        });
    }


    public function validateUniqueParticular()
    {
        $query = self::withTrashed()
            ->where('name', $this->name)
            ->where('district_id', $this->district_id);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $particular = $query->first();


        if ($particular) {
            if ($particular->deleted_at) {
                $message = 'A Particular with this name and District exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Particular with this name and District already exists.';
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
