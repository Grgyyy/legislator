<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Tvi;
use App\Models\Abdd;
use App\Models\Role;
use App\Models\Tvet;
use App\Models\User;
use App\Models\Region;
use App\Models\Target;
use App\Models\TviType;
use App\Models\District;
use App\Models\Priority;
use App\Models\Province;
use App\Models\TviClass;
use App\Models\Partylist;
use App\Models\Allocation;
use App\Models\FundSource;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\Permission;
use App\Models\Recognition;
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Models\Municipality;
use App\Models\ProvinceAbdd;
use App\Models\TargetRemark;
use App\Policies\AbddPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Models\SkillPriority;
use App\Models\SubParticular;
use App\Policies\RegionPolicy;
use App\Policies\TargetPolicy;
use App\Policies\TopTenPolicy;
use App\Models\TrainingProgram;
use App\Models\InstitutionClass;
use App\Models\InstitutionProgram;
use App\Policies\DistrictPolicy;
use App\Policies\ProvincePolicy;
use App\Policies\PartyListPolicy;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use App\Policies\AllocationPolicy;
use App\Policies\FundSourcePolicy;
use App\Policies\LegislatorPolicy;
use App\Policies\ParticularPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\TvetSectorPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\DeliveryModePolicy;
use App\Policies\LearningModePolicy;
use App\Policies\MunicipalityPolicy;
use App\Policies\ProvinceAbddPolicy;
use App\Policies\TargetRemarkPolicy;
use App\Policies\SkillPriorityPolicy;
use App\Models\InstitutionRecognition;
use App\Policies\ParticularTypePolicy;
use App\Policies\InstitutionTypePolicy;
use App\Policies\TrainingProgramPolicy;
use App\Policies\RecognitionTitlePolicy;
use App\Policies\InstitutionClassAPolicy;
use App\Policies\InstitutionClassBPolicy;
use App\Policies\InstitutionProgramPolicy;
use App\Policies\QualificationTitlePolicy;
use App\Policies\ScholarshipProgramPolicy;
use App\Policies\InstitutionRecognitionPolicy;
use App\Policies\ProjectProposalPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Region::class => RegionPolicy::class,
        Province::class => ProvincePolicy::class,
        District::class => DistrictPolicy::class,
        Municipality::class => MunicipalityPolicy::class,
        FundSource::class => FundSourcePolicy::class,
        Partylist::class => PartyListPolicy::class,
        SubParticular::class => ParticularTypePolicy::class,
        Particular::class => ParticularPolicy::class,
        Legislator::class => LegislatorPolicy::class,
        Tvi::class => InstitutionPolicy::class,
        TviType::class => InstitutionTypePolicy::class,
        TviClass::class => InstitutionClassAPolicy::class,
        InstitutionClass::class => InstitutionClassBPolicy::class,
        Recognition::class => RecognitionTitlePolicy::class,
        InstitutionRecognition::class => InstitutionRecognitionPolicy::class,
        Tvet::class => TvetSectorPolicy::class,
        Priority::class => TopTenPolicy::class,
        Abdd::class => AbddPolicy::class,
        ProvinceAbdd::class => ProvinceAbddPolicy::class,
        ScholarshipProgram::class => ScholarshipProgramPolicy::class,
        TrainingProgram::class => TrainingProgramPolicy::class,
        QualificationTitle::class => QualificationTitlePolicy::class,
        Allocation::class => AllocationPolicy::class,
        LearningMode::class => LearningModePolicy::class,
        DeliveryMode::class => DeliveryModePolicy::class,
        Target::class => TargetPolicy::class,
        TargetRemark::class => TargetRemarkPolicy::class,
        SkillPriority::class => SkillPriorityPolicy::class,
        InstitutionProgram::class => InstitutionProgramPolicy::class,
        QualificationTitle::class => ProjectProposalPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
