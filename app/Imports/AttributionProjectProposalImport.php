<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Abdd;
use App\Models\Allocation;
use App\Models\DeliveryMode;
use App\Models\District;
use App\Models\LearningMode;
use App\Models\Legislator;
use App\Models\Municipality;
use App\Models\Particular;
use App\Models\Partylist;
use App\Models\Province;
use App\Models\QualificationTitle;
use App\Models\Region;
use App\Models\ScholarshipProgram;
use App\Models\SkillPriority;
use App\Models\SkillPrograms;
use App\Models\Status;
use App\Models\SubParticular;
use App\Models\Target;
use App\Models\TargetHistory;
use App\Models\TargetStatus;
use App\Models\TrainingProgram;
use App\Models\Tvi;
use App\Services\NotificationHandler;
use Auth;
use Exception;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class AttributionProjectProposalImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);
            $this->validateNumberOfSlots($row['number_of_slots']);
            $this->validateYear($row['appropriation_year']);

            DB::transaction(function () use ($row) {

                $attributor = $this->getAttributorId($row['attributor']);
                $attribution_region = $this->getRegion($row['attributor_region']);
                $attribution_sub_particular = $this->getSubParticular($row['attributor_particular']);
                $attribution_particular = $this->getAttributorParticularRecord($attribution_sub_particular->id, $attribution_region->name);

                // Receiver
                $legislator = $this->getLegislatorId($row['legislator']);
                $region = $this->getRegion($row['region']);
                $province = $this->getProvince($row['province'], $region->id);
                $district = $this->getDistrict($row['district'], $row['municipality'], $province->id);
                $partylist = $this->getPartylist($row['partylist']);
                $sub_particular = $this->getSubParticular($row['particular']);
                $particular = $this->getParticular($sub_particular->id, $partylist->id, $district->id);

                $scholarship_program = $this->getScholarshipProgram($row['scholarship_program']);

                $allocation = $this->getAllocation($attributor->id, $attribution_particular->id, $legislator->id, $particular->id, $scholarship_program->id, $row['appropriation_year']);

                $abddSector = $this->getAbddSector($row['abdd_sector']);
                $delivery_mode = $this->getDeliveryMode($row['delivery_mode']);
                $learning_mode = $this->getLearningMode($row['learning_mode'], $delivery_mode->id);
                $tvi = $this->getTvi(Helper::capitalizeWords($row['institution']), $row['school_id']);
                $numberOfSlots = $row['number_of_slots'];

                $qualificationTitle = $this->getQualificationTitle($row['qualification_title'], $row['soc_code'], $row['qualification_title_scholarship_program'], $scholarship_program);
                $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots);

                $this->validateInstitutionProgram($tvi->id, $qualificationTitle->trainingProgram->id);

                $skillPriority = $this->getSkillPriority(
                    $qualificationTitle->training_program_id,
                    $tvi->district_id,
                    $tvi->district->province_id,
                    $row['appropriation_year']
                );

                $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

                if ($row['per_capita_cost']) {
                    if ($row['per_capita_cost'] > 0) {
                        $cost = $row['per_capita_cost'] * $numberOfSlots;
                    } else {
                        $message = "The Per Capita Cost Value must be greater than 0. No changes are saved.";
                        NotificationHandler::handleValidationException('Something went wrong', $message);
                    }
                } else {
                    throw new Exception("The Per Capita Cost is required. No changes are saved.");
                }

                $targetData = [
                    'allocation_id' => $allocation->id,
                    'district_id' => $tvi->district_id,
                    'municipality_id' => $tvi->municipality_id,
                    'tvi_id' => $tvi->id,
                    'tvi_name' => $tvi->name,
                    'abdd_id' => $abddSector->id,
                    'qualification_title_id' => $qualificationTitle->id,
                    'qualification_title_code' => $qualificationTitle->trainingProgram->code,
                    'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code,
                    'qualification_title_name' => Helper::capitalizeWords($qualificationTitle->trainingProgram->title),
                    'delivery_mode_id' => $delivery_mode->id,
                    'learning_mode_id' => $learning_mode->id,
                    'number_of_slots' => $row['number_of_slots'],
                    'total_training_cost_pcc' => $totals['total_training_cost_pcc'],
                    'total_cost_of_toolkit_pcc' => $totals['total_cost_of_toolkit_pcc'],
                    'total_training_support_fund' => $totals['total_training_support_fund'],
                    'total_assessment_fee' => $totals['total_assessment_fee'],
                    'total_entrepreneurship_fee' => $totals['total_entrepreneurship_fee'],
                    'total_new_normal_assisstance' => $totals['total_new_normal_assisstance'],
                    'total_accident_insurance' => $totals['total_accident_insurance'],
                    'total_book_allowance' => $totals['total_book_allowance'],
                    'total_uniform_allowance' => $totals['total_uniform_allowance'],
                    'total_misc_fee' => $totals['total_misc_fee'],
                    'total_amount' => $cost,
                    'appropriation_type' => $row['appropriation_type'],
                    'target_status_id' => $pendingStatus->id,
                ];

                if ($skillPriority->available_slots < $numberOfSlots) {
                    $message = "Insufficient available slots in Skills Priorities to create the target.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }

                if ($allocation->balance < $totals['total_amount']) {
                    $message = "Insufficient allocation balance to create the target.";
                    NotificationHandler::handleValidationException('Something went wrong', $message);
                }

                $target = Target::create($targetData);

                $skillPriority->decrement('available_slots', $numberOfSlots);
                $allocation->decrement('balance', $totals['total_amount']);

                $this->logTargetHistory($target, $allocation, $totals);

            });
        } catch (Throwable $e) {
            $message = "Import failed: " . $e->getMessage();
            NotificationHandler::handleValidationException('Something went wrong', $message);
            throw $e;
        }
    }


    protected function validateRow(array $row)
    {
        $requiredFields = [
            'legislator',
            'particular',
            'scholarship_program',
            'district',
            'province',
            'region',
            'partylist',
            'appropriation_year',
            'appropriation_type',
            'institution',
            'soc_code',
            'qualification_title',
            'qualification_title_scholarship_program',
            'abdd_sector',
            'delivery_mode',
            'learning_mode',
            'number_of_slots',
            'per_capita_cost'
        ];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                $message = "The field '{$field}' is required and cannot be null or empty. No changes were saved.";
                NotificationHandler::handleValidationException('Something went wrong', $message);
            }
        }
    }

    protected function validateNumberOfSlots(int $number_of_slots)
    {
        if ($number_of_slots < 10 || $number_of_slots > 25) {
            $message = "The field '{$number_of_slots}' in Number of Slot should be greater than or equal to 10 and less than equal to 25.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }

    protected function validateYear(int $year)
    {
        $currentYear = date('Y');
        $pastYear = $currentYear - 1;
        if ($year != $currentYear && $year != $pastYear) {
            $message = "The provided year '{$year}' must be either the current year '{$currentYear}' or the previous year '{$pastYear}'.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }


    protected function validateInstitutionProgram(int $tviId, int $qualiId)
    {
        $institution = Tvi::find($tviId);
        $quali = TrainingProgram::find($qualiId);

        if (!$institution) {
            $message = "Institution with ID {$tviId} not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        if (!$quali) {
            $message = "Qualification with ID {$qualiId} not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $instiPrograms = $institution->trainingPrograms()->pluck('training_programs.id');

        if (!$instiPrograms->contains($qualiId)) {
            $message = "The qualification title '{$quali->title}' is not registered under the institution '{$institution->name}";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
    }

    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->whereNull('deleted_at')
            ->has('allocation')
            ->first();

        if (!$legislator) {
            throw new Exception("No active legislator with an allocation found for name: {$legislatorName}.");
        }

        return $legislator;
    }

    protected function getAttributorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->whereNull('deleted_at')
            ->has('attributions')
            ->first();

        if (!$legislator) {
            throw new Exception("No active legislator with an allocation found for name: {$legislatorName}.");
        }

        return $legislator;
    }

    protected function getRegion(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            throw new Exception("Region with name '{$regionName}' not found.");
        }

        return $region;
    }

    protected function getProvince(string $provinceName, int $regionId)
    {
        $region = Region::find($regionId);
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$province) {
            throw new Exception("Province with name '{$provinceName}' under the region named '{$region->name}' not found.");
        }

        return $province;
    }

    protected function getDistrict(string $districtName, string $municipalityName, int $provinceId)
    {
        $province = Province::find($provinceId);

        $districtQuery = District::where('name', $districtName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at');

        if ($municipalityName !== 'Not Applicable') {
            $municipality = Municipality::where('name', $municipalityName)
                ->where('province_id', $provinceId)
                ->whereNull('deleted_at')
                ->first();

            if (!$municipality) {
                throw new Exception("The Municipality named '{$municipalityName}' is not existing.");
            }

            $districtQuery->where('municipality_id', $municipality->id);
        } else {
            $districtQuery->where('municipality_id', null);
        }

        $district = $districtQuery->first();

        if (!$district) {
            if ($municipalityName === 'Not Applicable') {
                throw new Exception("The District named '{$districtName}' under Province named '{$province->name}' is not existing.");
            }
            else {
                throw new Exception("The District named '{$districtName}' under Municipality named '{$municipalityName}' and Province named '{$province->name}' is not existing.");
            }
        }

        return $district;
    }

    protected function getMunicipality(string $municipalityName, int $provinceId)
    {
        $municipality = Municipality::where('name', $municipalityName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$municipality) {
            throw new Exception("Municipality with name '{$municipalityName}' not found.");
        }

        return $municipality;
    }

    protected function getPartylist(string $partylistName)
    {
        $partylist = Partylist::where('name', $partylistName)
            ->whereNull('deleted_at')
            ->first();

        if (!$partylist) {
            throw new Exception("Partylist with name '{$partylistName}' not found.");
        }

        return $partylist;
    }

    protected function getSubParticular(string $subParticularName)
    {
        $subParticular = SubParticular::where('name', $subParticularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$subParticular) {
            throw new Exception("Sub-Particular with name '{$subParticularName}' not found.");
        }

        return $subParticular;
    }

    protected function getParticular(int $sub_particular_id, int $partylist_id, int $district_id)
    {
        $subParticular = SubParticular::find($sub_particular_id);
        $particular = Particular::where('sub_particular_id', $sub_particular_id)
            ->where('partylist_id', $partylist_id)
            ->where('district_id', $district_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$particular) {
            $partylist = Partylist::find($partylist_id);
            $district = District::find($district_id);
            
            if ($subParticular->name === 'Party-list') {
                throw new Exception("Particular with name '{$subParticular->name}' under the Party-list named '{$partylist->name}' not found.");
            }
            elseif ($subParticular->name === 'RO Regular' || $subParticular->name === 'CO Regular') {
                throw new Exception("Particular with name '{$subParticular->name}' under the Region named '{$district->province->region->name}' not found.");
            }
            elseif ($subParticular->name === 'District') {
                throw new Exception("Particular with name '{$subParticular->name}' under the District named '{$district->name}' under the Province named '{$district->province->name}' not found.");
            }
            elseif ($subParticular->name === 'Senator' || $subParticular->name === 'House Speaker' || $subParticular->name === 'House Speaker (LAKAS)') {
                throw new Exception("The particular with the name '{$subParticular->name}' was not found. Please set the District, Municipality, Province, and Region to 'Not Applicable'.");
            }
            else {
                throw new Exception("Particular with name '{$subParticular->name}' not found.");
            }
        }

        return $particular;
    }

    protected function getScholarshipProgram(string $scholarshipProgramName)
    {
        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)
            ->whereNull('deleted_at')
            ->first();

        if (!$scholarshipProgram) {
            throw new Exception("Scholarship Program with name '{$scholarshipProgramName}' not found.");
        }

        return $scholarshipProgram;
    }

    protected function getAllocation(int $attributorId, int $attributorParticularId, int $legislatorId, int $particularId, int $scholarshipProgramId, int $appropriationYear)
    {
        $legislator = Legislator::find($legislatorId);
        $particular = Particular::find($particularId);
        $attributor = Legislator::find($attributorId);
        $attributorParticular = Particular::find($attributorParticularId);
        $schoPro = ScholarshipProgram::find($scholarshipProgramId);


        $allocation = Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('attributor_id', $attributorId)
            ->where('attributor_particular_id', $attributorParticularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->where('year', $appropriationYear)
            ->whereNull('deleted_at')
            ->first();

        if (!$allocation) {
            throw new Exception("No allocation found for legislator '{$legislator->name}' under the particular '{$particular->subParticular->name}' for the scholarship program '{$schoPro->name}' in the year '{$appropriationYear}' that has been attributed by '{$attributor->name}' under the particular '{$attributorParticular->subParticular->name}'.");
        }

        return $allocation;
    }

    protected function getAbddSector(string $abddSectorName)
    {
        $abddSector = Abdd::where('name', $abddSectorName)
            ->whereNull('deleted_at')
            ->first();

        if (!$abddSector) {
            throw new Exception("ABDD Sector with name '{$abddSectorName}' not found.");
        }

        return $abddSector;
    }

    protected function getDeliveryMode(string $deliveryModeName)
    {
        $deliveryMode = DeliveryMode::where('name', $deliveryModeName)
            ->whereNull('deleted_at')
            ->first();

        if (!$deliveryMode) {
            throw new Exception("Delivery Mode with name '{$deliveryModeName}' not found.");
        }

        return $deliveryMode;
    }

    protected function getLearningMode(string $learningModeName, int $deliveryModeId)
    {
        $learningMode = LearningMode::where('name', $learningModeName)
            ->whereHas('deliveryMode', function ($query) use ($deliveryModeId) {
                $query->where('delivery_mode_id', $deliveryModeId);
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$learningMode) {
            throw new Exception("Learning Mode with the specified name and associated Delivery Mode was not found.");
        }

        return $learningMode;
    }

    protected function getTvi(string $tviName, string $schoolId)
    {
        $tvi = Tvi::where('name', $tviName)
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->first();

        if (!$tvi) {
            throw new Exception("Institution '{$tviName}' with School ID '{$schoolId}' not found.");
        }

        return $tvi;
    }

    protected function getQualificationTitle(string $qualificationTitleName, string $socCode, string $qualCodeSchoPro, $scholarshipProgram)
    {

        $scholarship = ScholarshipProgram::where('name', $qualCodeSchoPro)
            ->first();

        if (!$scholarship) {
            throw new Exception("The scholarship program named'{$qualCodeSchoPro}' does not exists.");
        }

        if ($scholarship->code !== 'TWSP' && $scholarship->code !== 'TTSP') {
            throw new Exception("The Project Proposal is permitted to use only TWSP and TTSP scholarship programs.");
        }

        $qualSchoPro = ScholarshipProgram::where('name', $qualCodeSchoPro)
            ->where('code', $scholarshipProgram->name)
            ->first();

        if (!$qualSchoPro) {
            throw new Exception("Scholarship Program named '{$qualCodeSchoPro}' with a code of '{$scholarshipProgram->name}' not found.");
        }

        $qualificationTitle = QualificationTitle::where('scholarship_program_id', $qualSchoPro->id)
            ->whereHas('trainingProgram', function ($query) use ($qualificationTitleName, $socCode) {
                $query->where('title', $qualificationTitleName)
                    ->where('soc_code', $socCode);
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$qualificationTitle) {
            throw new Exception("Qualification Title with name '{$qualificationTitleName}' not found.");
        }

        return $qualificationTitle;
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots): array
    {
        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $qualificationTitle->cost_of_toolkit_pcc * $numberOfSlots,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assisstance' => $qualificationTitle->new_normal_assistance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $qualificationTitle->pcc * $numberOfSlots,
        ];
    }

    private function getSkillPriority(int $trainingProgramId, $districtId, int $provinceId, int $appropriationYear)
    {
        $active = Status::where('desc', 'Active')->first();
        $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
            ->whereHas('skillPriority', function ($query) use ($districtId, $provinceId, $appropriationYear, $active) {
                $query->where('province_id', $provinceId)
                    ->where('district_id', $districtId)
                    ->where('year', $appropriationYear)
                    ->where('status_id', $active->id);
            })
            ->first();

        $trainingProgram = TrainingProgram::where('id', $trainingProgramId)->first();
        $province = Province::where('id', $provinceId)->first();
        
        if (!$skillPrograms) {
            $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                ->whereHas('skillPriority', function ($query) use ($provinceId, $appropriationYear) {
                    $query->where('province_id', $provinceId)
                        ->whereNull('district_id')
                        ->where('year', $appropriationYear);
                })
                ->first();
        }

        if (!$skillPrograms) {
            throw new Exception("Skill Priority for the training program '{$trainingProgram->title}' in the province '{$province->name}' not found.");
        }

        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

        if (!$skillsPriority) {
            $district = District::where('id', $districtId)->first();

            if (!$trainingProgram || !$province || !$district) {
                throw new Exception("Invalid training program, province, or district.");
            }

            throw new Exception("Skill Priority for the training program '{$trainingProgram->title}' in district '{$district->id}' within '{$province->name}' not found.");
        }

        return $skillsPriority;
    }

    protected function getAttributorParticularRecord(int $subParticularId, string $regionName)
    {

        $region = Region::where('name', $regionName)
            ->first();

        if (!$region) {
            throw new Exception("The Region named '{$regionName}' is not existing.");
        }

        $province = Province::where('name', 'Not Applicable')
            ->where('region_id', $region->id)
            ->first();

        $district = District::where('name', 'Not Applicable')
            ->where('province_id', $province->id)
            ->first();

        $partylist = Partylist::where('name', 'Not Applicable')
            ->first();

        $particular = Particular::where('sub_particular_id', $subParticularId)
            ->where('partylist_id', $partylist->id)
            ->where('district_id', $district->id)
            ->first();

        $subParticular = SubParticular::find($subParticularId);

        if (!$particular) {
            throw new Exception("The Particular named '{$subParticular->name}' is not existing.");
        }

        return $particular;
    }


    private function logTargetHistory(Target $target, Allocation $allocation, array $totals): void
    {
        TargetHistory::create([
            'target_id' => $target->id,
            'allocation_id' => $allocation->id,
            'district_id' => $target->district_id,
            'municipality_id' => $target->municipality_id,
            'tvi_id' => $target['tvi_id'],
            'tvi_name' => $target->tvi_name,
            'qualification_title_id' => $target->qualification_title_id,
            'qualification_title_code' => $target->qualification_title_code,
            'qualification_title_soc_code' => $target->qualification_title_soc_code,
            'qualification_title_name' => $target->qualification_title_name,
            'abdd_id' => $target['abdd_id'],
            'delivery_mode_id' => $target['delivery_mode_id'],
            'learning_mode_id' => $target['learning_mode_id'],
            'number_of_slots' => $target['number_of_slots'],
            'total_training_cost_pcc' => $totals['total_training_cost_pcc'],
            'total_cost_of_toolkit_pcc' => $totals['total_cost_of_toolkit_pcc'],
            'total_training_support_fund' => $totals['total_training_support_fund'],
            'total_assessment_fee' => $totals['total_assessment_fee'],
            'total_entrepreneurship_fee' => $totals['total_entrepreneurship_fee'],
            'total_new_normal_assisstance' => $totals['total_new_normal_assisstance'],
            'total_accident_insurance' => $totals['total_accident_insurance'],
            'total_book_allowance' => $totals['total_book_allowance'],
            'total_uniform_allowance' => $totals['total_uniform_allowance'],
            'total_misc_fee' => $totals['total_misc_fee'],
            'total_amount' => $totals['total_amount'],
            'appropriation_type' => $target['appropriation_type'],
            'description' => 'Target Created',
            'user_id' => Auth::user()->id,
        ]);
    }

}
