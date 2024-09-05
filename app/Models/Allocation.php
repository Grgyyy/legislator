<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Allocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legislator_id',
        'particular_id',
        'scholarship_program_id',
        'allocation',
        'admin_cost',
        'balance',
        'year'
    ];

    public function legislator()
    {
        return $this->belongsTo(Legislator::class);
    }

    public function scholarship_program()
    {
        return $this->belongsTo(ScholarshipProgram::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueAllocation();
        });
    }


    public function validateUniqueAllocation()
    {
        $query = self::withTrashed()
            ->where('legislator_id', $this->legislator_id)
            ->where('particular_id', $this->particular_id)
            ->where('scholarship_program_id', $this->scholarship_program_id);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $allocation = $query->first();


        if ($allocation) {
            if ($allocation->deleted_at) {
                $message = 'A Allocation data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Allocation data already exists.';
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
