<?php

namespace App\Imports;

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
                $fundSourceId = $this->getFundResource($row['fund_source']);

                if (!$fundSourceId) {
                    throw new \Exception("Validation error: Fund source '{$row['fund_source']}' does not exist.");
                }

                $particularTypeExist = SubParticular::where('name', $row['particular_type'])
                    ->where('fund_source_id', $fundSourceId)
                    ->exists();

                if (!$particularTypeExist) {
                    return new SubParticular([
                        'name' => $row['particular_type'],
                        'fund_source_id' => $fundSourceId,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import SubParticular: ' . $e->getMessage());
                
                throw $e;
            }
        });
    }

    protected function validateRow(array $row) {
        $requiredFields = ['particular_type', 'fund_source'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("Validation error: The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

    protected function getFundResource($fundSourceName) {
        $fundSource = FundSource::where('name', $fundSourceName)->first();

        return $fundSource ? $fundSource->id : null;
    }
}