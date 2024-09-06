<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class QualificationTitle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_program_id',
        'scholarship_program_id',
        'training_cost_pcc',
        'cost_of_toolkit_pcc',
        'training_support_fund',
        'assessment_fee',
        'entrepeneurship_fee',
        'new_normal_assisstance',
        'accident_insurance',
        'book_allowance',
        'uniform_allowance',
        'misc_fee',
        'hours_duration',
        'days_duration',
        'status_id',
    ];

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function scholarshipProgram()
    {
        return $this->belongsTo(ScholarshipProgram::class, 'scholarship_program_id');
    }

    public function target() {
        return $this->hasMany(Target::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function getDisplayNameAttribute()
    {
        $trainingProgram = $this->trainingProgram ? $this->trainingProgram->title : 'Unknown Training Program';
        $scholarshipProgram = $this->scholarshipProgram ? $this->scholarshipProgram->name : 'Unknown Scholarship Program';

        return "{$trainingProgram} - {$scholarshipProgram}";
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->validateUniqueQualificationTitle();
        });
    }


    public function validateUniqueQualificationTitle()
    {
        $query = self::withTrashed()
            ->where('training_program_id', $this->training_program_id)
            ->where('scholarship_program_id', $this->scholarship_program_id);

        if ($this->id) {
            $query->where('id', '<>' . $this->id);
        }

        $qualification_title = $query->first();


        if ($qualification_title) {
            if ($qualification_title->deleted_at) {
                $message = 'A Qualification Title data exists and is marked as deleted. Data cannot be created.';
            } else {
                $message = 'A Qualification Title data already exists.';
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
