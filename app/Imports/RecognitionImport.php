<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Recognition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class RecognitionImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $recognitionTitle = Helper::capitalizeWords($row['recognition_title']);

                $recognitionExist = Recognition::where('name', $recognitionTitle)
                    ->exists();

                if (!$recognitionExist) {
                    return new Recognition([
                        'name' => $recognitionTitle,
                    ]);
                }
            } catch (Throwable $e) {

                Log::error('Failed to import Recognition Title: ' . $e->getMessage());
                throw $e;

            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['recognition_title'])) {
            throw new \Exception("The Recognition Title is required and cannot be null or empty. No changes were saved.");
        }
    }
}
