<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Tvi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'institution_class_id',
        'tvi_class_id',
        'district_id',
        'address',
    ];

    public function tviClass()
    {
        return $this->belongsTo(TviClass::class);
    }
    public function InstitutionClass()
    {
        return $this->belongsTo(InstitutionClass::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function target()
    {
        return $this->hasMany(Target::class);
    }

    public function targetHistory() {
        return $this->hasMany(targetHistory::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueInstitution();
        });
    }

    public function validateUniqueInstitution()
    {
        $query = self::withTrashed()
            ->where('name', $this->name)
            ->where('institution_class_id', $this->institution_class_id)
            ->where('tvi_class_id', $this->tvi_class_id)
            ->where('district_id', $this->district_id)
            ->where('address', $this->address);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $institution = $query->first();


        if ($institution) {
            if ($institution->deleted_at) {
                $message = 'A Institution data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Institution data already exists.';
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
