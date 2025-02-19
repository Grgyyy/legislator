<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Target extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'abscap_id',
        'rqm_code',
        'allocation_id',
        'district_id',
        'municipality_id',
        'tvi_id',
        'tvi_name',
        'abdd_id',
        'qualification_title_id',
        'qualification_title_code',
        'qualification_title_soc_code',
        'qualification_title_name',

        'delivery_mode_id',
        'learning_mode_id',

        'number_of_slots',
        'total_training_cost_pcc',
        'total_cost_of_toolkit_pcc',
        'total_training_support_fund',
        'total_assessment_fee',
        'total_entrepreneurship_fee',
        'total_new_normal_assisstance',
        'total_accident_insurance',
        'total_book_allowance',
        'total_uniform_allowance',
        'total_misc_fee',
        'total_amount',
        'appropriation_type',
        'target_status_id',

        'is_new',
        'seen_by_users'
    ];
    
    public function seenByUsers()
    {
        return $this->belongsToMany(User::class, 'target_seen_by')
            ->withTimestamps();
    }

    public function hasBeenSeenByUser($userId)
    {
        return $this->seenByUsers()->where('user_id', $userId)->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($target) {
            $target->update(['is_new' => true]);

            $target->seenByUsers()->create(['user_id' => auth()->id()]);
        });

        static::retrieved(function ($target) {
            $userId = auth()->id();

            if (!$userId || !$target->id) {
                return;
            }
        
            $sessionKey = "viewed_target_{$target->id}_user_{$userId}";
        
            if ($target->seenByUsers()->where('user_id', $userId)->exists()) {
                return;
            }
        
            if (!session()->has($sessionKey)) {
                session()->put($sessionKey, true);
            } else {
                $target->seenByUsers()->attach($userId);
                session()->forget($sessionKey);
        
                $totalUsers = User::count();
                if ($target->seenByUsers()->count() >= $totalUsers) {
                    $target->update(['is_new' => false]);
                }
            }
        });
    }

    // WAG TANGGALIN
    // public static function boot()
    // {
    //     parent::boot();

    //     static::created(function ($target) {
    //         $creatorId = auth()->id();
    //         $target->update([
    //             'is_new' => true, 
    //             'seen_by_users' => json_encode([$creatorId])
    //         ]);
    //     });

    //     static::retrieved(function ($target) {
    //         $userId = auth()->id();
    //         $sessionKey = "viewed_fundsource_{$target->id}_user_{$userId}";

    //         $seenBy = json_decode($target->seen_by_users, true) ?? [];

    //         if (in_array($userId, $seenBy)) {
    //             return;
    //         }

    //         if ($target->is_new) {
    //             if (!session()->has($sessionKey)) {
    //                 session()->put($sessionKey, 1);
    //             } else {
    //                 $seenBy[] = $userId;
    //                 $target->update(['seen_by_users' => json_encode($seenBy)]);

    //                 session()->forget($sessionKey);
    //             }

    //             $totalUsers = User::count();
    //             if (count($seenBy) >= $totalUsers) {
    //                 $target->update(['is_new' => false]);
    //             }
    //         }
    //     });
    // }

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function attributionAllocation()
    {
        return $this->belongsTo(Allocation::class, 'attribution_allocation_id');
    }

    public function tvi()
    {
        return $this->belongsTo(Tvi::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }


    public function qualification_title()
    {
        return $this->belongsTo(QualificationTitle::class);
    }

    public function targetStatus()
    {
        return $this->belongsTo(TargetStatus::class);
    }

    public function abdd()
    {
        return $this->belongsTo(Abdd::class);
    }

    public function deliveryMode()
    {
        return $this->belongsTo(DeliveryMode::class);
    }

    public function learningMode()
    {
        return $this->belongsTo(LearningMode::class);
    }

    public function targetHistory()
    {
        return $this->hasMany(TargetHistory::class);
    }

    public function comments()
    {
        return $this->hasMany(TargetComment::class);
    }

    public function nonCompliantRemark()
    {
        return $this->hasOne(NonCompliantRemark::class);
    }

}
