<?php

namespace App\Imports;

use App\Models\Partylist;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Throwable;

class PartylistImport implements ToModel, WithHeadingRow
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

                $partylistExist = Partylist::where('name', $row['partylist_name'])->exists();

                if(!$partylistExist) {
                    return new Partylist([
                        'name' => $row['partylist_name'],
                    ]);
                }

            } catch (Throwable $e) {

                Log::error('Failed to import partylist: ' . $e->getMessage());
                throw $e;

            }
        });
    }

    protected function validateRow(array $row)
    {
        if (empty($row['partylist_name'])) {
            throw new \Exception("Validation error: The field 'Partylist Name' is required and cannot be null or empty. No changes were saved.");
        }
    }
}
