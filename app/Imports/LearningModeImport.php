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
                $deliveryMode = DeliveryMode::where('acronym', $row['delivery_mode_acronym'])->first();
                if (!$deliveryMode) {
                    throw new \Exception("Delivery Mode with acronym '{$row['delivery_mode_acronym']}' not found.");
                }

                $learningMode = LearningMode::firstOrCreate(
                    ['name' => $row['learning_mode']]
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
