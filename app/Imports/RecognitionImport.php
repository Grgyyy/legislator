<?php

namespace App\Imports;

use Throwable;
use App\Models\Recognition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RecognitionImport implements ToModel, WithHeadingRow
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
                $recognitionExist = Recognition::where('name', $row['recognition_title'])
                    ->exists();

                if (!$recognitionExist) {
                    return new Recognition([
                        'name' => $row['recognition_title'],
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
        if (empty($row['recognition_title'])) {
            throw new \Exception("The Recognition Title is required and cannot be null or empty. No changes were saved.");
        }
    }
}
