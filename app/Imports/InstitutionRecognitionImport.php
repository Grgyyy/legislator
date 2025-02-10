<?php

namespace App\Imports;

use Throwable;
use Carbon\Carbon;
use App\Models\Tvi;
use App\Models\Recognition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\InstitutionRecognition;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class InstitutionRecognitionImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        Log::info('Raw Row Data from Excel: ' . json_encode($row));

        if (empty(array_filter($row))) {
            Log::warning('Empty row detected, skipping: ' . json_encode($row));
            return null;
        }

        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            $tviId = $this->getTvi($row['Institution']);
            $recognitionId = $this->getRecognition($row['Recognition']);
            $accreditationDate = $this->convertExcelDate($row['Accreditation Date']);
            $expirationDate = $this->convertExcelDate($row['Expiration Date']);

            $exists = InstitutionRecognition::where([
                'tvi_id' => $tviId,
                'recognition_id' => $recognitionId,
                'accreditation_date' => $accreditationDate,
                'expiration_date' => $expirationDate,
            ])->exists();

            if (!$exists) {
                return InstitutionRecognition::create([
                    'tvi_id' => $tviId,
                    'recognition_id' => $recognitionId,
                    'accreditation_date' => $accreditationDate,
                    'expiration_date' => $expirationDate,
                ]);
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['Institution', 'Recognition', 'Accreditation Date', 'Expiration Date'];

        foreach ($requiredFields as $field) {
            if (!isset($row[$field]) || empty(trim($row[$field]))) {
                throw new \Exception("The field '{$field}' is required and cannot be empty.");
            }
        }

        $accreditationDate = $this->convertExcelDate(trim($row['Accreditation Date']));
        $expirationDate = $this->convertExcelDate(trim($row['Expiration Date']));

        if (Carbon::parse($accreditationDate)->lt(Carbon::today())) {
            throw new \Exception("The accreditation date must be today or a future date.");
        }

        if (Carbon::parse($expirationDate)->lte(Carbon::parse($accreditationDate))) {
            throw new \Exception("The expiration date must be greater than the accreditation date.");
        }
    }

    protected function convertExcelDate($value)
    {
        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    protected function getTvi(string $tviName)
    {
        return Tvi::where('name', $tviName)->firstOrFail()->id;
    }

    protected function getRecognition(string $recognitionName)
    {
        return Recognition::where('name', $recognitionName)->firstOrFail()->id;
    }
}
