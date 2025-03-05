<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\InstitutionRecognition;
use App\Models\Recognition;
use App\Models\Tvi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Throwable;

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
            $institutionName = Helper::capitalizeWords(trim($row['Institution']));
            $recognitionTitle = Helper::capitalizeWords(trim($row['Recognition']));

            $tviId = $this->getTvi($institutionName);
            $recognitionId = $this->getRecognition($recognitionTitle);
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

        if (Carbon::parse($expirationDate)->lte(Carbon::parse($accreditationDate)) && Carbon::parse($expirationDate)->lte(Carbon::now())) {
            throw new \Exception("The expiration date must be greater than both the accreditation date and today.");
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
