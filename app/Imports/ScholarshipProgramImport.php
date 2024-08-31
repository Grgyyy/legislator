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

        foreach (['code', 'scholarship_program', 'description'] as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Required field '{$field}' is empty.");
            }
        }

        return DB::transaction(function () use ($row) {
            try {
                $scholarshipProgram = new ScholarshipProgram([
                    'code' => $row['code'],
                    'name' => $row['scholarship_program'],
                    'desc' => $row['description'],
                ]);

                $scholarshipProgram->save();

                DB::commit();
                return $scholarshipProgram;
            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("An error occurred while importing row: " . json_encode($row) . " Error: " . $e->getMessage());
                throw $e;
            }
        });
    }
}
