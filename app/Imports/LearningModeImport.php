<?php

namespace App\Imports;

use App\Models\DeliveryMode;
use App\Models\LearningMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;
use Maatwebsite\Excel\Concerns\Importable;


class LearningModeImport implements ToModel, WithHeadingRow
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
                // Retrieve or throw error if delivery mode is not found
                $deliveryMode = DeliveryMode::where('name', $row['delivery_mode'])->first();

                if (!$deliveryMode) {
                    throw new \Exception("Delivery Mode with name '{$row['delivery_mode']}' not found.");
                }

                // Check if the LearningMode with the given name already exists or create a new one
                $learningMode = LearningMode::firstOrCreate(
                    ['name' => $row['name']], // Search for an existing record
                    ['name' => $row['name']]  // If not found, create the record with the given name
                );

                // Check if the LearningMode already has the DeliveryMode relationship
                if (!$learningMode->deliveryMode()->where('delivery_mode_id', $deliveryMode->id)->exists()) {
                    // If the relationship doesn't exist, attach the DeliveryMode
                    $learningMode->deliveryMode()->attach($deliveryMode->id);
                }

            } catch (Throwable $e) {
                Log::error('Failed to import Learning Modes: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Validates if the required fields are present in the row.
     *
     * @param array $row
     * @throws \Exception
     */
    protected function validateRow(array $row)
    {
        $requiredFields = ['name', 'delivery_mode'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }
}
