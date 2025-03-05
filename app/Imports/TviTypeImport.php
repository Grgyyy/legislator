<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\TviType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class TviTypeImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {

                $tviTypeName = Helper::capitalizeWords($row['institution_type']);

                $typeIsExist = TviType::where('name', $tviTypeName)->exists();

                if (!$typeIsExist) {
                    return new TviType([
                        'name' => $tviTypeName,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('Failed to import TVI Type: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['institution_type'])) {
            throw new \Exception("The TVI Type is required and cannot be null or empty. No changes were saved.");
        }
    }
}
