<?php

namespace App\Providers;

use App\Models\Abdd;
use App\Models\Allocation;

use App\Models\DeliveryMode;
use App\Models\District;
use App\Models\FundSource;
use App\Models\InstitutionClass;
use App\Models\InstitutionProgram;
use App\Models\InstitutionRecognition;
use App\Models\LearningMode;
use App\Models\Legislator;
use App\Models\Municipality;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Permission;
use App\Models\Priority;
use App\Models\Province;
use App\Models\ProvinceAbdd;
use App\Models\QualificationTitle;
use App\Models\Recognition;
use App\Models\Region;
use App\Models\Role;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetRemark;
use App\Models\Toolkit;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use App\Models\Tvi;
use App\Models\TviClass;
use App\Models\TviType;
use App\Models\User;
use App\Policies\AbddPolicy;
use App\Policies\AllocationPolicy;

use App\Policies\DeliveryModePolicy;
use App\Policies\DistrictPolicy;
use App\Policies\FundSourcePolicy;
use App\Policies\InstitutionClassAPolicy;
use App\Policies\InstitutionClassBPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\InstitutionProgramPolicy;
use App\Policies\InstitutionRecognitionPolicy;
use App\Policies\InstitutionTypePolicy;
use App\Policies\LearningModePolicy;
use App\Policies\LegislativeTargetPolicy;
use App\Policies\LegislatorPolicy;
use App\Policies\MunicipalityPolicy;
use App\Policies\ParticularPolicy;
use App\Policies\ParticularTypePolicy;
use App\Policies\PartyListPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ProjectProposalPolicy;
use App\Policies\ProvinceAbddPolicy;
use App\Policies\ProvincePolicy;
use App\Policies\QualificationTitlePolicy;
use App\Policies\RecognitionTitlePolicy;
use App\Policies\RegionPolicy;
use App\Policies\RolePolicy;
use App\Policies\ScheduleOfCostPolicy;
use App\Policies\ScholarshipProgramPolicy;
use App\Policies\SkillPriorityPolicy;
use App\Policies\TargetPolicy;
use App\Policies\TargetRemarkPolicy;
use App\Policies\ToolkitPolicy;
use App\Policies\TopTenPolicy;
use App\Policies\TvetSectorPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
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
        TrainingProgram::class => QualificationTitlePolicy::class,
        QualificationTitle::class => ScheduleOfCostPolicy::class,
        Allocation::class => AllocationPolicy::class,
        LearningMode::class => LearningModePolicy::class,
        DeliveryMode::class => DeliveryModePolicy::class,
        Target::class => TargetPolicy::class,
        TargetRemark::class => TargetRemarkPolicy::class,
        SkillPriority::class => SkillPriorityPolicy::class,
        InstitutionProgram::class => InstitutionProgramPolicy::class,
        Toolkit::class => ToolkitPolicy::class,
        Target::class => LegislativeTargetPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('view-legislative-targets-report', [LegislativeTargetPolicy::class, 'viewTargetReport']);
        Gate::define('view-any-legislative-targets-report', [LegislativeTargetPolicy::class, 'viewAnyTargetReport']);
        Gate::define('export-legislative-targets-report', [LegislativeTargetPolicy::class, 'exportTargetReport']);
    }
}
