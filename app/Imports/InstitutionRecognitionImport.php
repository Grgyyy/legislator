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
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Throwable
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $tviId = $this->getTvi($row['school_id'], $row['institution']);
                $recognitionId = $this->getRecognition($row['recognition']);

                $exists = DB::table('institution_recognitions')
                    ->where('tvi_id', $tviId)
                    ->where('recognition_id', $recognitionId)
                    ->where('year', $row['year'])
                    ->exists();

                if (!$exists) {
                    return Recognition::create([
                        'tvi_id' => $tviId,
                        'recognition_id' => $recognitionId,
                        'year' => $row['year'],
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import Recognition: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['institution', 'recognition', 'year'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be empty. Import aborted.");
            }
        }
        if (!is_numeric($row['year']) || (int) $row['year'] < 1900 || (int) $row['year'] > now()->year) {
            throw new \Exception("The year '{$row['year']}' is invalid. It must be a numeric value between 1900 and the current year.");
        }
    }


    protected function getTvi(int $schoolId, string $tviName)
    {
        $tvi = Tvi::where('name', $tviName)
            ->where('school_id', $schoolId)
            ->first();

        if (!$tvi) {
            throw new \Exception("TVI with name '{$tviName}' and school ID '{$schoolId}' not found.");
        }

        return $tvi->id;
    }


    protected function getRecognition(string $recognitionName)
    {
        $recognition = Recognition::where('name', $recognitionName)->first();

        if (!$recognition) {
            throw new \Exception("Recognition with name '{$recognitionName}' not found.");
        }

        return $recognition->id;
    }
}
