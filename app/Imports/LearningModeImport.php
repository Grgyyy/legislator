<?php

namespace App\Imports;

use App\Models\LearningMode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Throwable;
use App\Models\Recognition;
use App\Models\Tvi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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

                $learningModeExist = LearningMode::where('acronym', $row['acronym'])
                    ->where('name', $row['name'])
                    ->exists();

                if (!$learningModeExist) {
                    return new LearningMode([
                        'acronym' => $row['acronym'],
                        'name' => $row['name'],
                    ]);
                }
            } catch (Throwable $e) {

                Log::error('Failed to import Learning Modes: ' . $e->getMessage());
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
        $requiredFields = ['acronym', 'name'];

        foreach ($requiredFields as $field) {
            if (empty($row[$field])) {
                throw new \Exception("The field '{$field}' is required and cannot be null or empty. No changes were saved.");
            }
        }
    }

}
