<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\Partylist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class PartylistImport implements ToModel, WithHeadingRow
{
    use Importable;

    public function model(array $row)
    {
        $this->validateRow($row);

        return DB::transaction(function () use ($row) {
            try {
                $partylistName = Helper::capitalizeWords($row['partylist_name']);

                $partylistExist = Partylist::where('name', $partylistName)->exists();

                if (!$partylistExist) {
                    return new Partylist([
                        'name' => $partylistName,
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
