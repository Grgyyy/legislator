<?php
namespace App\Imports;

use App\Models\ScholarshipProgram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ScholarshipProgramImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model/null
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {

            try {

                $scholarshipProgram = ScholarshipProgram::where('name', $row['scholarship_program'])
                    ->where('code', $row['code'])
                    ->exists();

                if (!$scholarshipProgram) {

                    return new ScholarshipProgram([
                        'code' => $row['code'],
                        'name' => $row['scholarship_program'],
                        'desc' => $row['description']
                    ]);

                }

            } catch (Throwable $e) {

                Log::error('Failed to import training program: ' . $e->getMessage());
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
