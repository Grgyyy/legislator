<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Legislator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status_id'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function allocation()
    {
        return $this->hasMany(Allocation::class);
    }

    public function particular()
    {
        return $this->belongsToMany(Particular::class, 'legislator_particular')
            ->withTimestamps();
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }
    public function getFormattedParticularAttribute()
    {
        return $this->particular->map(function ($particular) {
            $district = $particular->district;
            $municipality = $district ? $district->municipality : null;
            $province = $municipality ? $municipality->province : null;

            $particularName = $particular->name;
            $districtName = $district ? $district->name : '';
            $municipalityName = $municipality ? $municipality->name : '';
            $provinceName = $province ? $province->name : '';

            return trim("{$particularName} - {$districtName}, {$municipalityName}, {$provinceName}", ', ');
        })->implode(', ');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueLegislator();
        });
    }


    public function validateUniqueLegislator()
    {
        $query = self::withTrashed()
            ->where('name', $this->name);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $legislator = $query->first();


        if ($legislator) {
            if ($legislator->deleted_at) {
                $message = 'A Legislator with this name and Particular exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Legislator with this name and Particular already exists.';
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
