<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\FundSource;
use App\Models\SubParticular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ParticularTypesImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $fundSourceName = Helper::capitalizeWords($row['fund_source']);
                $particularTypeName = Helper::capitalizeWords($row['particular_type']);

                $fundSourceId = $this->getFundResource($fundSourceName);

                if (!$fundSourceId) {
                    throw new \Exception("Validation error: Fund source '{$fundSourceName}' does not exist.");
                }

                $particularTypeExist = SubParticular::where('name', $particularTypeName)
                    ->where('fund_source_id', $fundSourceId)
                    ->exists();

                if (!$particularTypeExist) {
                    return new SubParticular([
                        'name' => $particularTypeName,
                        'fund_source_id' => $fundSourceId,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import SubParticular: ' . $e->getMessage());

                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['particular_type', 'fund_source'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getFundResource($fundSourceName)
    {
        $fundSource = FundSource::where('name', $fundSourceName)->first();

        return $fundSource ? $fundSource->id : null;
    }
}
