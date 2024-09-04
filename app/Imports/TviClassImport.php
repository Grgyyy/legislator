<?php

namespace App\Imports;

use Throwable;
use App\Models\TviClass;
use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class TviClassImport implements ToModel, WithHeadingRow
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
                $tvi_type_id = $this->getTviTypeId($row['tvi_type']);

                return new TviClass([
                    'name' => $row['institution_class'],
                    'tvi_type_id' => $tvi_type_id,
                ]);
            } catch (Throwable $e) {
                Log::error('Failed to import TviClass: ' . $e->getMessage());
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
        if (empty($row['institution_class'])) {
            throw new \Exception("Validation error: The 'institution_class' field is required and cannot be null or empty. No changes were saved.");
        }
    }

    public function getTviTypeId(string $tviType)
    {
        $tvi = TviType::where('name', $tviType)->first();

        if (!$tvi) {
            throw new \Exception("TVI Type with name '{$tviType}' not found. No changes were saved.");
        }

        return $tvi->id;
    }
}
