<?php

namespace App\Imports;

use App\Models\DeliveryMode;
use App\Models\LearningMode;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DeliveryModeImport implements ToModel, WithHeadingRow
{
    use Importable;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws Throwable
     */
    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {

                $deliveryModeExists = DeliveryMode::where('name', $row['delivery_mode_name'])
                    ->where('acronym', $row['delivery_mode_acronym'])
                    ->exists();

                if (!$deliveryModeExists) {
                    return new DeliveryMode([
                        'acronym' => $row['delivery_mode_acronym'],
                        'name' => $row['delivery_mode_name']
                    ]);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import Delivery Mode: ' . $e->getMessage());
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

