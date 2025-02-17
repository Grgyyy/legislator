<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Priority;
use App\Models\ScholarshipProgram;
use App\Models\TrainingProgram;
use App\Models\Tvet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class TrainingProgramsImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $scholarshipProgramId = self::getScholarshipProgramId($row['scholarship_program']);
                $tvetId = self::getTvetId($row['tvet_sector']);
                $priorityId = self::getPriorityId($row['priority_sector']);

                $options = ['Full', 'COC', 'ELE', 'NTR/CS'];

                if (!in_array($row['fullcocele'], $options, true)) {
                    throw new \Exception("Invalid value '{$row['fullcocele']}' for 'fullcocele'. Allowed values are: " . implode(', ', $options));
                }

                $nc_levels = ['NC I', 'NC II', 'NC III', 'NC IV', 'NC V', 'NC VI'];

                if ($row['nc_level'] !== null && !in_array($row['nc_level'], $nc_levels, true)) {
                    throw new \Exception("Invalid value '{$row['nc_level']}' for 'nc_level'. Allowed values are: " . implode(', ', $nc_levels));
                }

                $formattedTitle = Helper::capitalizeWords($row['title']);

                $trainingProgram = TrainingProgram::withTrashed()
                    ->where('soc_code', $row['soc_code'])
                    ->where(DB::raw('LOWER(title)'), strtolower($formattedTitle))
                    ->first();

                if (!$trainingProgram) {
                    $trainingProgram = TrainingProgram::create([
                        'code' => $row['qualification_code'],
                        'soc_code' => $row['soc_code'],
                        'full_coc_ele' => $row['fullcocele'],
                        'nc_level' => $row['nc_level'],
                        'title' => $formattedTitle,
                        'tvet_id' => $tvetId,
                        'priority_id' => $priorityId,
                    ]);
                }

                $trainingProgram->scholarshipPrograms()->syncWithoutDetaching([$scholarshipProgramId]);

                return $trainingProgram;
            } catch (Throwable $e) {
                Log::error('Failed to import training program: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['scholarship_program', 'title', 'tvet_sector', 'priority_sector', 'soc_code'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected static function getScholarshipProgramId(string $scholarshipProgramName)
    {
        $scholarshipProgram = ScholarshipProgram::where('name', $scholarshipProgramName)
            ->whereNull('deleted_at')
            ->first();

        if ($scholarshipProgram === null) {
            throw new \Exception("Scholarship program with name '{$scholarshipProgramName}' not found. No changes were saved.");
        }

        return $scholarshipProgram->id;
    }

    protected static function getTvetId(string $tvet)
    {
        $tvetRecord = Tvet::where('name', $tvet)
            ->whereNull('deleted_at')
            ->first();

        if ($tvetRecord === null) {
            throw new \Exception("Tvet sector with name '{$tvet}' not found. No changes were saved.");
        }

        return $tvetRecord->id;
    }

    protected static function getPriorityId(string $priority)
    {
        $priorityRecord = Priority::where('name', $priority)
            ->whereNull('deleted_at')
            ->first();

        if ($priorityRecord === null) {
            throw new \Exception("Priority with name '{$priority}' not found. No changes were saved.");
        }

        return $priorityRecord->id;
    }
}
