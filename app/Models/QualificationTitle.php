<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualificationTitle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'duration',
        'training_cost_pcc',
        'cost_of_toolkit_pcc',
        'status_id',
    ];

    public function scholarshipPrograms()
    {
        return $this->belongsTo(ScholarshipProgram::class)
            ->withTimestamps();
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
