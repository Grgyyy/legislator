<?php

namespace App\Imports;

use Throwable;
use App\Models\FundSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FundSourceImport implements ToModel, WithHeadingRow
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

                $fundSourceIsExist = FundSource::where('name', $row['fund_source'])
                    ->exists();


                if (!$fundSourceIsExist) {
                    return new FundSource([
                        'name' => $row['fund_source'],
                    ]);
                }

            } catch (Throwable $e) {

                Log::error('Failed to import Fund Source: ' . $e->getMessage());
                throw $e;

            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['fund_source'])) {
            throw new \Exception("Validation error: The field 'Fund Source' is required and cannot be null or empty. No changes were saved.");
        }
    }
}
