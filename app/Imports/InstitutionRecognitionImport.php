<?php

namespace App\Imports;

use Throwable;
use App\Models\Recognition;
use App\Models\Tvi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InstitutionRecognitionImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {

                $tviId = $this->getTvi($row['school_id'], $row['institution']);



                $recognitionExist = Recognition::where('name', $row['recognition_title'])
                    ->exists();

                if (!$recognitionExist) {
                    return new Recognition([
                        'institution' => $tviId,
                        'name' => $row['recognition_title'],
                        'year' => $row['year'],
                    ]);
                }
            } catch (Throwable $e) {

                Log::error('Failed to import Recognition Title: ' . $e->getMessage());
                throw $e;

            }
        });
    }

    /**
     *
     * @param array $row
     * @throws \Exception
     */
    protected function validateRow(array $row)
    {
        $requiredFields = ['institution', 'name', 'year'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }


    protected function getTvi(int $schoolId, string $tvi)
    {
        $school = Tvi::where('name', $tvi)
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at')
            ->first();


        if (!$school) {
            throw new \Exception("TVI with name '{$tvi}' not found. No changes were saved.");
        }

        return $school->id;

    }
}
