<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;


class Province extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'region_id',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function municipality()
    {
        return $this->hasMany(Municipality::class);
    }
    public function target()
    {
        return $this->hasMany(Target::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueProvince();
        });
    }


    // public function validateUniqueProvince()
    // {
    //     $query = self::where('name', $this->name)
    //         ->where('region_id', $this->region_id);

    //     if ($this->id) {
    //         $query->where('id', '<>', $this->id);
    //     }

    //     $existingProvince = $query->exists();

    //     if ($existingProvince) {
    //         $message = 'A province with this name and region already exists.';

    //         try {
    //             throw ValidationException::withMessages([
    //                 'name' => $message,
    //             ]);
    //         } catch (ValidationException $e) {
    //             Notification::make()
    //                 ->title('Error')
    //                 ->body($e->errors()['name'][0])
    //                 ->danger()
    //                 ->send();


    //             throw $e;
    //         }
    //     }
    // }


    public function validateUniqueProvince()
    {
        $query = self::withTrashed()
            ->where('name', $this->name)
            ->where('region_id', $this->region_id);

        if ($this->id) {
            $query->where('id', '<>', $this->id);
        }

        $province = $query->first();

        if ($province) {
            if ($province->deleted_at) {
                $message = 'A province with this name and region exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A province with this name and region already exists.';
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
