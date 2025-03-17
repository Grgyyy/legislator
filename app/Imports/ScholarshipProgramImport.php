<?php
namespace App\Imports;

use App\Helpers\Helper;
use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ScholarshipProgramImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {

            try {
                $formattedDescription = Helper::capitalizeWords($row['description']);

                $scholarshipProgram = ScholarshipProgram::where('name', $row['scholarship_program'])
                    ->where('code', $row['code'])
                    ->exists();

                if (!$scholarshipProgram) {

                    return new ScholarshipProgram([
                        'code' => $row['code'],
                        'name' => $row['scholarship_program'],
                        'desc' => $formattedDescription
                    ]);
                }
            } catch (Throwable $e) {
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['code', 'scholarship_program', 'description'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }
}
