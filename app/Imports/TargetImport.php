<?php

namespace App\Imports;

use App\Models\DeliveryMode;
use App\Models\LearningMode;
use App\Models\Particular;
use App\Models\SkillPriority;
use App\Models\SkillPrograms;
use App\Models\Status;
use App\Models\SubParticular;
use App\Models\TargetStatus;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Auth;
use Throwable;
use App\Models\Tvi;
use App\Models\Abdd;
use App\Models\Region;
use App\Models\Target;
use App\Models\District;
use App\Models\Province;
use App\Models\Partylist;
use App\Models\Allocation;
use App\Models\Legislator;
use App\Models\TargetHistory;
use App\Models\QualificationTitle;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TargetImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        try {
            $this->validateRow($row);
            $this->validateNumberOfSlots($row['number_of_slots']);
            $this->validateYear($row['appropriation_year']);

            DB::transaction(function () use ($row) {
                $legislator = $this->getLegislatorId($row['legislator']);
                $region = $this->getRegion($row['region']);
                $province = $this->getProvince($row['province'], $region->id);
                $district = $this->getDistrict($row['district'], $province->id);
                $partylist = $this->getPartylist($row['partylist']);
                $sub_particular = $this->getSubParticular($row['particular']);
                $particular = $this->getParticular($sub_particular->id, $partylist->id, $district->id);
                $scholarship_program = $this->getScholarshipProgram($row['scholarship_program']);
                $allocation = $this->getAllocation($legislator->id, $particular->id, $scholarship_program->id, $row['appropriation_year']);
                $abddSector = $this->getAbddSector($row['abdd_sector']);
                $delivery_mode = $this->getDeliveryMode($row['delivery_mode']);
                $learning_mode = $this->getLearningMode($row['learning_mode'], $delivery_mode->id);
                $tvi = $this->getTvi($row['institution']);
                $numberOfSlots = $row['number_of_slots'];

                $qualificationTitle = $this->getQualificationTitle($row['qualification_title'], $scholarship_program->id);
                $totals = $this->calculateTotals($qualificationTitle, $numberOfSlots, $row['appropriation_year']);

                $skillPriority = $this->getSkillPriority(
                    $qualificationTitle->training_program_id,
                    $tvi->district_id ?? null,
                    $tvi->district->province_id,
                    $row['appropriation_year']
                );

                $pendingStatus = TargetStatus::where('desc', 'Pending')->first();

                $targetData = [
                    'allocation_id' => $allocation->id,
                    'district_id' => $tvi->district_id,
                    'municipality_id' => $tvi->municipality_id,
                    'tvi_id' => $tvi->id,
                    'tvi_name' => $tvi->name,
                    'abdd_id' => $abddSector->id,
                    'qualification_title_id' => $qualificationTitle->id,
                    'qualification_title_soc_code' => $qualificationTitle->trainingProgram->soc_code,
                    'qualification_title_code' => $qualificationTitle->trainingProgram->code,
                    'qualification_title_name' => $qualificationTitle->trainingProgram->title,
                    'delivery_mode_id' => $delivery_mode->id,
                    'learning_mode_id' => $learning_mode->id,
                    'number_of_slots' => $numberOfSlots,
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
                    'appropriation_type' => $row['appropriation_type'],
                    'target_status_id' => $pendingStatus->id,
                ];

                if ($skillPriority->available_slots < $numberOfSlots) {
                    $message = "Insufficient target benificiaries in Skill Priorities of the {$qualificationTitle->trainingProgram->title} under the under District {$tvi->district->name} in {$tvi->district->province->name} to create the target.";
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
            $message = "Import failed: ". $e->getMessage();
            NotificationHandler::handleValidationException('Something went wrong', $message);
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
            'qualification_title',
            'abdd_sector',
            'delivery_mode',
            'learning_mode',
            'number_of_slots',
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
            $message = "The field '{$number_of_slots}' in Number of Slot should be greater than or equal to 10 and less than equal to 25";
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


    protected function getLegislatorId(string $legislatorName)
    {
        $legislator = Legislator::where('name', $legislatorName)
            ->whereNull('deleted_at')
            ->has('allocation')
            ->first();

        if (!$legislator) {
            $message = "No active legislator with an allocation found for name: {$legislatorName}.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $legislator;
    }
    protected function getRegion(string $regionName)
    {
        $region = Region::where('name', $regionName)
            ->whereNull('deleted_at')
            ->first();

        if (!$region) {
            $message = "Region with name '{$regionName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $region;
    }

    protected function getProvince(string $provinceName, int $regionId)
    {
        $province = Province::where('name', $provinceName)
            ->where('region_id', $regionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$province) {
            $message = "Province with name '{$provinceName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $province;
    }
    protected function getDistrict(string $districtName, int $provinceId)
    {
        $district = District::where('name', $districtName)
            ->where('province_id', $provinceId)
            ->whereNull('deleted_at')
            ->first();

        if (!$district) {
            $message = "District with name '{$districtName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }
        return $district;
    }
    protected function getPartylist(string $partylistName)
    {
        $partylist = Partylist::where('name', $partylistName)
            ->whereNull('deleted_at')
            ->first();

        if (!$partylist) {
            $message = "Partylist with name '{$partylistName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $partylist;
    }

    protected function getSubParticular(string $subParticularName)
    {
        $subParticular = SubParticular::where('name', $subParticularName)
            ->whereNull('deleted_at')
            ->first();

        if (!$subParticular) {
            $message = "Sub-Particular with name '{$subParticularName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
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
            $message = "Particular with name '{$subParticular->name}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $particular;
    }

    protected function getScholarshipProgram(string $scholarshipProgramName)
    {
        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)
            ->whereNull('deleted_at')
            ->first();

        if (!$scholarshipProgram) {
            $message = "Scholarship Program with name '{$scholarshipProgramName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $scholarshipProgram;
    }

    protected function getAllocation(int $legislatorId, int $particularId, int $scholarshipProgramId, int $appropriationYear)
    {
        $allocation = Allocation::where('legislator_id', $legislatorId)
            ->where('particular_id', $particularId)
            ->where('scholarship_program_id', $scholarshipProgramId)
            ->where('year', $appropriationYear)
            ->whereNull('deleted_at')
            ->first();

        if (!$allocation) {
            $message = "No allocation found matching the provided legislator, particular, scholarship program, and year.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $allocation;
    }

    protected function getAbddSector(string $abddSectorName)
    {
        $abddSector = Abdd::where('name', $abddSectorName)
            ->whereNull('deleted_at')
            ->first();

        if (!$abddSector) {
            $message = "ABDD Sector with name '{$abddSectorName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $abddSector;
    }

    protected function getDeliveryMode(string $deliveryModeName)
    {
        $deliveryMode = DeliveryMode::where('name', $deliveryModeName)
            ->whereNull('deleted_at')
            ->first();

        if (!$deliveryMode) {
            $message = "Delivery Mode with name '{$deliveryModeName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
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
            $message = "Learning Mode with the specified name and associated Delivery Mode was not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $learningMode;
    }
    protected function getTvi(string $tviName)
    {
        $tvi = Tvi::where('name', $tviName)
            ->whereNull('deleted_at')
            ->first();

        if (!$tvi) {
            $message = "Institution with name '{$tviName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $tvi;
    }

    protected function getQualificationTitle(string $qualificationTitleName, int $scholarshipProgramId)
    {
        $qualificationTitle = QualificationTitle::where('scholarship_program_id', $scholarshipProgramId)
            ->whereHas('trainingProgram', function ($query) use ($qualificationTitleName) {
                $query->where('title', $qualificationTitleName);
            })
            ->whereNull('deleted_at')
            ->first();

        if (!$qualificationTitle) {
            $message = "Qualification Title with name '{$qualificationTitleName}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $qualificationTitle;
    }

    private function calculateTotals(QualificationTitle $qualificationTitle, int $numberOfSlots, int $year): array
    {

        $quali = QualificationTitle::find($qualificationTitle->id);
        $costOfToolkitPcc = $quali->toolkits()->where('year', $year)->first();


        if (!$quali) {
            $message = "Qualification Title with name '{$qualificationTitle->trainingProgram->title}' not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        $step = ScholarshipProgram::where('name', 'STEP')->first();

        $totalCostOfToolkit = 0;
        $totalAmount = $qualificationTitle->pcc * $numberOfSlots;
        if ($quali->scholarship_program_id === $step->id) {
            $totalCostOfToolkit = $costOfToolkitPcc->price_per_toolkit * $numberOfSlots;
            $totalAmount += $totalCostOfToolkit;
        }


        return [
            'total_training_cost_pcc' => $qualificationTitle->training_cost_pcc * $numberOfSlots,
            'total_cost_of_toolkit_pcc' => $totalCostOfToolkit,
            'total_training_support_fund' => $qualificationTitle->training_support_fund * $numberOfSlots,
            'total_assessment_fee' => $qualificationTitle->assessment_fee * $numberOfSlots,
            'total_entrepreneurship_fee' => $qualificationTitle->entrepreneurship_fee * $numberOfSlots,
            'total_new_normal_assisstance' => $qualificationTitle->new_normal_assistance * $numberOfSlots,
            'total_accident_insurance' => $qualificationTitle->accident_insurance * $numberOfSlots,
            'total_book_allowance' => $qualificationTitle->book_allowance * $numberOfSlots,
            'total_uniform_allowance' => $qualificationTitle->uniform_allowance * $numberOfSlots,
            'total_misc_fee' => $qualificationTitle->misc_fee * $numberOfSlots,
            'total_amount' => $totalAmount,
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

        if (!$skillPrograms) {
            $skillPrograms = SkillPrograms::where('training_program_id', $trainingProgramId)
                ->whereHas('skillPriority', function ($query) use ($provinceId, $appropriationYear) {
                    $query->where('province_id', $provinceId)
                        ->where('year', $appropriationYear);
                })
                ->first();
        }

        if(!$skillPrograms) {
            NotificationHandler::handleValidationException('Something went wrong', 'No available skill priority.');
            return;
        }
        
        $skillsPriority = SkillPriority::find($skillPrograms->skill_priority_id);

        if (!$skillsPriority) {
            $trainingProgram = TrainingProgram::where('id', $trainingProgramId)->first();
            $province = Province::where('id', $provinceId)->first();
            $district = District::where('id', $districtId)->first();
        
            if (!$trainingProgram || !$province || !$district) {
                NotificationHandler::handleValidationException('Something went wrong', 'Invalid training program, province, or district.');
                return;
            }
        
            $message = "Skill Priority for {$trainingProgram->title} under District {$district->id} in {$province->name} not found.";
            NotificationHandler::handleValidationException('Something went wrong', $message);
        }

        return $skillsPriority;
    }

    private function logTargetHistory(Target $target, Allocation $allocation, array $totals): void
    {
        TargetHistory::create([
            // 'abscap_id' => $target['abscap_id'],
            'target_id' => $target->id,
            'allocation_id' => $allocation->id,
            'district_id' => $target->district_id,
            'municipality_id' => $target->municipality_id,
            'tvi_id' => $target['tvi_id'],
            'tvi_name' => $target->tvi_name,
            'qualification_title_id' => $target->qualification_title_id,
            'qualification_title_code' => $target->qualification_title_code,
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
