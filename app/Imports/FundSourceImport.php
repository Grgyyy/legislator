<?php
namespace App\Imports;

use App\Helpers\Helper;
use App\Models\FundSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class FundSourceImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $fundSourceName = Helper::capitalizeWords($row['fund_source']);

                $fundSourceIsExist = FundSource::where('name', $fundSourceName)->exists();

                if (!$fundSourceIsExist) {
                    return new FundSource([
                        'name' => $fundSourceName,
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
