<?php

namespace App\Imports;

use App\Helpers\Helper; // Import the Helper class
use App\Models\DeliveryMode;
use App\Models\LearningMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class LearningModeImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $learningModeName = Helper::capitalizeWords($row['learning_mode']);

                $deliveryMode = DeliveryMode::where('acronym', $row['delivery_mode_acronym'])->first();
                if (!$deliveryMode) {
                    throw new \Exception("Delivery Mode with acronym '{$row['delivery_mode_acronym']}' not found.");
                }

                $learningMode = LearningMode::firstOrCreate(
                    ['name' => $learningModeName]
                );

                if (!$learningMode->deliveryMode()->where('delivery_mode_id', $deliveryMode->id)->exists()) {
                    $learningMode->deliveryMode()->attach($deliveryMode->id);
                }

                return $learningMode;
            } catch (Throwable $e) {
                Log::error('Failed to import Learning Modes: ' . $e->getMessage());
                DB::rollBack();
                throw $e;
            }
        });
    }

    protected function validateRow(array $row)
    {
        $requiredFields = ['learning_mode', 'delivery_mode_acronym'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty.");
            }
        }
    }
}
