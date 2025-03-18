<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\DeliveryMode;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class DeliveryModeImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $deliveryModeName = Helper::capitalizeWords($row['delivery_mode_name']);

                $deliveryModeExists = DeliveryMode::where('name', $deliveryModeName)
                    ->where('acronym', $row['delivery_mode_acronym'])
                    ->exists();

                if (!$deliveryModeExists) {
                    return new DeliveryMode([
                        'acronym' => $row['delivery_mode_acronym'],
                        'name' => $deliveryModeName
                    ]);
                }

            } catch (Throwable $e) {
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['delivery_mode_acronym', 'delivery_mode_name'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty.");
            }
        }
    }
}
